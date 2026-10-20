<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;

class GPayAuditorService
{
    /**
     * Parse raw text extracted via OCR or pasted by the user into structured transactions.
     *
     * @return array<int, array{merchant: string, date: string, date_display: string, amount: float, type: string, raw: string}>
     */
    public function parseRawText(string $rawText, ?int $defaultYear = null): array
    {
        $year = $defaultYear ?: (int) date('Y');
        $lines = preg_split('/\r\n|\r|\n/', trim($rawText));
        $cleanedLines = [];

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);
            if ($trimmed !== '') {
                $cleanedLines[] = $trimmed;
            }
        }

        $items = [];
        $count = count($cleanedLines);
        $i = 0;

        while ($i < $count) {
            $line = $cleanedLines[$i];

            // Ignore header lines like "Search transactions", "Indian Bank", "Status", "Date", "October"
            if (preg_match('/^(search|indian bank|status|date|october|\d{2}:\d{2}|volte|lte|\d+%$)/i', $line)) {
                $i++;

                continue;
            }

            // Pattern A: Single line with Merchant, Date, and Amount
            // E.g. "SRI MAHALAKSHMI BAKERY 13 October ₹10" or "Preethi Nathan 14 October + ₹70"
            if (preg_match('/^(.*?)\s+(\d{1,2}\s+(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*)\s+(\+?\s*(?:₹|Rs\.?|INR)?\s*[0-9,]+(?:\.[0-9]{1,2})?)$/iu', $line, $m)) {
                $item = $this->buildItem(trim($m[1]), trim($m[2]), trim($m[3]), $year, $line);
                if ($item) {
                    $items[] = $item;
                }
                $i++;

                continue;
            }

            // Pattern B: Merchant on line 1, Amount on right or next to it, Date on line 2
            // Or Merchant line, Date line, Amount line
            // Check if current line contains merchant and amount: e.g. "SRI MAHALAKSHMI BAKERY ₹10"
            if (preg_match('/^(.*?)\s+(\+?\s*(?:₹|Rs\.?|INR)?\s*[0-9,]+(?:\.[0-9]{1,2})?)$/u', $line, $m) && ! preg_match('/^\d{1,2}\s+[a-z]+/i', $line)) {
                $merchant = trim($m[1]);
                $amountRaw = trim($m[2]);

                // Next line might be date, e.g. "13 October"
                $dateRaw = null;
                if ($i + 1 < $count && preg_match('/^\d{1,2}\s+[a-z]+/i', $cleanedLines[$i + 1])) {
                    $dateRaw = $cleanedLines[$i + 1];
                    $i++; // consumed date line
                }

                $item = $this->buildItem($merchant, $dateRaw ?: date('d F'), $amountRaw, $year, $line);
                if ($item) {
                    $items[] = $item;
                }
                $i++;

                continue;
            }

            // Pattern C: Merchant line, next line Date, next line Amount (or Date + Amount)
            if ($i + 1 < $count && preg_match('/^\d{1,2}\s+[a-z]+/i', $cleanedLines[$i + 1])) {
                $merchant = $line;
                $dateRaw = $cleanedLines[$i + 1];
                $amountRaw = null;

                if ($i + 2 < $count && preg_match('/^(\+?\s*(?:₹|Rs\.?|INR)?\s*[0-9,]+(?:\.[0-9]{1,2})?)$/u', $cleanedLines[$i + 2])) {
                    $amountRaw = $cleanedLines[$i + 2];
                    $i += 2;
                } else {
                    $i++;
                }

                if ($amountRaw) {
                    $item = $this->buildItem($merchant, $dateRaw, $amountRaw, $year, $line);
                    if ($item) {
                        $items[] = $item;
                    }
                }
                $i++;

                continue;
            }

            $i++;
        }

        return $items;
    }

    /**
     * Build structured transaction item.
     *
     * @return array{merchant: string, date: string, date_display: string, amount: float, type: string, raw: string}|null
     */
    private function buildItem(string $merchant, string $dateRaw, string $amountRaw, int $year, string $rawLine): ?array
    {
        $isCredit = str_contains($amountRaw, '+');
        $cleanedAmount = preg_replace('/[^\d.]/', '', $amountRaw);
        $amount = (float) $cleanedAmount;

        if ($amount <= 0) {
            return null;
        }

        // Clean merchant name
        $merchant = preg_replace('/\s+/', ' ', trim($merchant));
        $merchant = trim($merchant, "-:\t ");

        // Parse date
        try {
            $parsedDate = Carbon::parse("{$dateRaw} {$year}");
        } catch (\Throwable) {
            $parsedDate = Carbon::now();
        }

        return [
            'merchant' => $merchant ?: 'Unknown Payee',
            'date' => $parsedDate->toDateString(),
            'date_display' => $parsedDate->format('d M Y'),
            'amount' => round($amount, 2),
            'type' => $isCredit ? 'credit' : 'debit',
            'raw' => $rawLine,
        ];
    }

    /**
     * Audit parsed GPay items against the user's recorded Expenses and Incomes.
     *
     * @param  array<int, array{merchant: string, date: string, date_display: string, amount: float, type: string, raw: string}>  $gpayItems
     * @return array<string, mixed>
     */
    public function audit(User $user, array $gpayItems, ?string $monthYear = null): array
    {
        if (empty($gpayItems)) {
            return [
                'summary' => [
                    'total_gpay_debits' => 0.0,
                    'total_gpay_credits' => 0.0,
                    'total_website_upi_expenses' => 0.0,
                    'total_website_upi_incomes' => 0.0,
                    'discrepancy' => 0.0,
                ],
                'matched' => [],
                'fake_expenses' => [],
                'missing_expenses' => [],
                'missing_incomes' => [],
                'amount_diffs' => [],
            ];
        }

        // Determine date range for comparison
        $dates = collect($gpayItems)->pluck('date')->sort()->values();
        $minDate = Carbon::parse($dates->first())->startOfMonth();
        $maxDate = Carbon::parse($dates->last())->endOfMonth();

        if ($monthYear && preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
            $minDate = Carbon::parse($monthYear.'-01')->startOfMonth();
            $maxDate = Carbon::parse($monthYear.'-01')->endOfMonth();
        }

        // 1. Fetch website UPI expenses in range
        $websiteExpenses = Expense::query()
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->where(fn ($q) => $q->whereNull('paid_by_type')->orWhere('paid_by_type', '!=', 'friend'))
            ->whereDate('date', '>=', $minDate->toDateString())
            ->whereDate('date', '<=', $maxDate->toDateString())
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%UPI%')
                    ->orWhere('payment_method', 'like', '%GPay%')
                    ->orWhere('notes', 'like', '%Payment breakdown%');
            })
            ->with(['category', 'friendSplits'])
            ->get();

        // 2. Fetch website UPI incomes in range
        $websiteIncomes = Income::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', $minDate->toDateString())
            ->whereDate('date', '<=', $maxDate->toDateString())
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%UPI%')
                    ->orWhere('payment_method', 'like', '%GPay%')
                    ->orWhereNull('payment_method');
            })
            ->with('category')
            ->get();

        $matched = [];
        $amountDiffs = [];
        $missingExpenses = [];
        $missingIncomes = [];

        $usedExpenseIds = [];
        $usedIncomeIds = [];

        // Check each GPay item against website records
        foreach ($gpayItems as $gItem) {
            $isCredit = $gItem['type'] === 'credit';
            $amt = (float) $gItem['amount'];
            $gDate = $gItem['date'];
            $gMerchant = strtolower(trim($gItem['merchant']));

            if ($isCredit) {
                // Match against Income
                $matchingIncome = $websiteIncomes
                    ->reject(fn (Income $inc) => in_array($inc->id, $usedIncomeIds, true))
                    ->first(function (Income $inc) use ($amt, $gDate) {
                        return abs((float) $inc->amount - $amt) < 0.01 &&
                            abs(Carbon::parse($inc->date)->diffInDays(Carbon::parse($gDate))) <= 2;
                    });

                if ($matchingIncome) {
                    $usedIncomeIds[] = $matchingIncome->id;
                    $matched[] = [
                        'type' => 'income',
                        'gpay' => $gItem,
                        'website' => [
                            'id' => $matchingIncome->id,
                            'title' => $matchingIncome->source ?: 'Income',
                            'date' => Carbon::parse($matchingIncome->date)->toDateString(),
                            'amount' => (float) $matchingIncome->amount,
                            'category' => $matchingIncome->category?->name ?? 'General',
                        ],
                    ];
                } else {
                    $missingIncomes[] = [
                        'gpay' => $gItem,
                        'question' => "Found green credit of +₹{$amt} from '{$gItem['merchant']}' on {$gItem['date_display']}. Is this money received (Income)?",
                    ];
                }
            } else {
                // Debit: Match against Expenses
                // Exact amount & close date match
                $matchingExpense = $websiteExpenses
                    ->reject(fn (Expense $exp) => in_array($exp->id, $usedExpenseIds, true))
                    ->first(function (Expense $exp) use ($amt, $gDate) {
                        $expAmount = (float) $exp->totalAmount();

                        return abs($expAmount - $amt) < 0.01 &&
                            abs(Carbon::parse($exp->date)->diffInDays(Carbon::parse($gDate))) <= 2;
                    });

                // If not found, check if there's a close date match with small decimal/paise difference (< ₹2.00)
                if (! $matchingExpense) {
                    $diffExpense = $websiteExpenses
                        ->reject(fn (Expense $exp) => in_array($exp->id, $usedExpenseIds, true))
                        ->first(function (Expense $exp) use ($amt, $gDate) {
                            $expAmount = (float) $exp->totalAmount();

                            return abs($expAmount - $amt) > 0.009 &&
                                abs($expAmount - $amt) <= 2.00 &&
                                abs(Carbon::parse($exp->date)->diffInDays(Carbon::parse($gDate))) <= 1;
                        });

                    if ($diffExpense) {
                        $usedExpenseIds[] = $diffExpense->id;
                        $amountDiffs[] = [
                            'gpay' => $gItem,
                            'website' => [
                                'id' => $diffExpense->id,
                                'title' => $diffExpense->description ?: 'Expense',
                                'date' => Carbon::parse($diffExpense->date)->toDateString(),
                                'amount' => (float) $diffExpense->totalAmount(),
                                'category' => $diffExpense->category?->name ?? 'General',
                            ],
                            'diff' => round($amt - (float) $diffExpense->totalAmount(), 2),
                            'question' => "GPay was ₹{$amt} for '{$gItem['merchant']}' but website has ₹{$diffExpense->totalAmount()} for '{$diffExpense->description}'. Sync the difference?",
                        ];

                        continue;
                    }
                }

                if ($matchingExpense) {
                    $usedExpenseIds[] = $matchingExpense->id;
                    $matched[] = [
                        'type' => 'expense',
                        'gpay' => $gItem,
                        'website' => [
                            'id' => $matchingExpense->id,
                            'title' => $matchingExpense->description ?: 'Expense',
                            'date' => Carbon::parse($matchingExpense->date)->toDateString(),
                            'amount' => (float) $matchingExpense->totalAmount(),
                            'category' => $matchingExpense->category?->name ?? 'General',
                        ],
                    ];
                } else {
                    $missingExpenses[] = [
                        'gpay' => $gItem,
                        'question' => "Found GPay debit of ₹{$amt} to '{$gItem['merchant']}' on {$gItem['date_display']}. Is this an expense?",
                    ];
                }
            }
        }

        // Remaining website UPI expenses that were NEVER found on GPay (Potential "Fake" or Cash expenses)
        $fakeExpenses = [];
        foreach ($websiteExpenses as $wExp) {
            if (! in_array($wExp->id, $usedExpenseIds, true)) {
                $fakeExpenses[] = [
                    'website' => [
                        'id' => $wExp->id,
                        'title' => $wExp->description ?: 'Expense',
                        'date' => Carbon::parse($wExp->date)->toDateString(),
                        'date_display' => Carbon::parse($wExp->date)->format('d M Y'),
                        'amount' => (float) $wExp->totalAmount(),
                        'payment_method' => $wExp->payment_method,
                        'category' => $wExp->category?->name ?? 'General',
                    ],
                    'question' => "Logged on website as UPI for ₹{$wExp->totalAmount()} ('{$wExp->description}'), but NOT debited on GPay. Was this paid in Cash?",
                ];
            }
        }

        $totGpayDebits = collect($gpayItems)->where('type', 'debit')->sum('amount');
        $totGpayCredits = collect($gpayItems)->where('type', 'credit')->sum('amount');
        $totWebExpenses = $websiteExpenses->sum(fn ($e) => (float) $e->totalAmount());
        $totWebIncomes = $websiteIncomes->sum('amount');

        return [
            'summary' => [
                'total_gpay_debits' => round((float) $totGpayDebits, 2),
                'total_gpay_credits' => round((float) $totGpayCredits, 2),
                'total_website_upi_expenses' => round((float) $totWebExpenses, 2),
                'total_website_upi_incomes' => round((float) $totWebIncomes, 2),
                'fake_expenses_count' => count($fakeExpenses),
                'missing_expenses_count' => count($missingExpenses),
                'missing_incomes_count' => count($missingIncomes),
                'amount_diffs_count' => count($amountDiffs),
                'matched_count' => count($matched),
            ],
            'matched' => $matched,
            'fake_expenses' => $fakeExpenses,
            'missing_expenses' => $missingExpenses,
            'missing_incomes' => $missingIncomes,
            'amount_diffs' => $amountDiffs,
        ];
    }
}
