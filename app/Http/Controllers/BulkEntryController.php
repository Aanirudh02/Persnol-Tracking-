<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FamilyMember;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Services\AuditService;
use App\Services\DailyRegisterService;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorInstance;
use Illuminate\View\View;

/**
 * One table to add many expenses, personal expenses and incomes at once.
 * Every row is saved together, or none are.
 */
class BulkEntryController extends Controller
{
    public const ROW_TYPES = ['expense', 'personal', 'income'];

    public function create(Request $request, OptionsService $options, DailyRegisterService $registerService): View
    {
        $user = $request->user();

        $categoriesByType = [
            'expense' => ExpenseCategory::query()
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
                ->where(fn ($query) => $query->whereNull('is_archived')->orWhere('is_archived', false))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (ExpenseCategory $category): array => ['id' => $category->id, 'name' => $category->name])
                ->all(),
            'personal' => PersonalExpenseCategory::query()
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
                ->where('is_archived', false)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (PersonalExpenseCategory $category): array => ['id' => $category->id, 'name' => $category->name])
                ->all(),
            'income' => IncomeCategory::query()
                ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (IncomeCategory $category): array => ['id' => $category->id, 'name' => $category->name])
                ->all(),
        ];

        $paymentMethods = array_values(array_filter(
            $options->names('payment_method'),
            fn (string $method): bool => $method !== 'Split'
        )) ?: ['Cash', 'UPI'];

        $familyMembers = FamilyMember::query()->where('user_id', $user->id)->orderBy('name')->pluck('name')->all();

        $today = $registerService->getRegisterForDate($user, Carbon::today());
        $currentBalances = collect($today['categories'] ?? [])
            ->map(fn (array $category): float => (float) ($category['carry_forward'] ?? $category['closing']))
            ->all();

        return view('finance.bulk_entry.create', compact('categoriesByType', 'paymentMethods', 'familyMembers', 'currentBalances'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        /** @var Collection<int|string, array<string, mixed>> $rows */
        $rows = collect($request->input('rows', []))
            ->filter(fn (mixed $row): bool => is_array($row) && filled($row['amount'] ?? null));

        if ($rows->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one row with an amount.');
        }

        $validator = Validator::make(['rows' => $rows->all()], [
            'rows.*.type' => ['required', Rule::in(self::ROW_TYPES)],
            'rows.*.date' => ['required', 'date'],
            'rows.*.time' => ['nullable', 'date_format:H:i'],
            'rows.*.description' => ['nullable', 'string', 'max:255'],
            'rows.*.category_id' => ['nullable', 'integer'],
            'rows.*.amount' => ['required', 'numeric', 'min:0.01'],
            'rows.*.payment_method' => ['required', 'string', 'max:50'],
            'rows.*.done_by' => ['nullable', 'string', 'max:100'],
            'rows.*.done_to' => ['nullable', 'string', 'max:100'],
            'rows.*.is_unlogged' => ['nullable', 'boolean'],
            'rows.*.keep_separate' => ['nullable', 'boolean'],
            'rows.*.add_to_main' => ['nullable', 'boolean'],
        ], [], [
            'rows.*.amount' => 'amount',
            'rows.*.date' => 'date',
            'rows.*.payment_method' => 'method',
        ]);

        $validator->after(fn (ValidatorInstance $validator) => $this->validateCategories($validator, $rows, $user->id));

        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator)->with('error', 'Nothing was saved — fix the highlighted rows and save again.');
        }

        $counts = ['expense' => 0, 'personal' => 0, 'income' => 0];
        $totals = ['expense' => 0.0, 'personal' => 0.0, 'income' => 0.0];

        DB::transaction(function () use ($rows, $user, &$counts, &$totals): void {
            foreach ($rows as $row) {
                $type = $row['type'];
                match ($type) {
                    'expense' => $this->storeExpenseRow($row, $user->id),
                    'personal' => $this->storePersonalRow($row, $user->id),
                    'income' => $this->storeIncomeRow($row, $user->id),
                };
                $counts[$type]++;
                $totals[$type] += (float) $row['amount'];
            }
        });

        $summary = collect([
            'expense' => 'expense(s)',
            'personal' => 'personal expense(s)',
            'income' => 'income(s)',
        ])->filter(fn (string $label, string $type): bool => $counts[$type] > 0)
            ->map(fn (string $label, string $type): string => $counts[$type].' '.$label.' (₹'.number_format($totals[$type], 2).')')
            ->implode(', ');

        return redirect()->route('bulk-entry.create')->with('success', 'Saved '.$summary.'.');
    }

    /**
     * @param  Collection<int|string, array<string, mixed>>  $rows
     */
    private function validateCategories(ValidatorInstance $validator, Collection $rows, int $userId): void
    {
        foreach ($rows as $index => $row) {
            $type = $row['type'] ?? null;
            $categoryId = filled($row['category_id'] ?? null) ? (int) $row['category_id'] : null;
            $key = "rows.{$index}.category_id";

            if ($type === 'personal' && $categoryId === null) {
                $validator->errors()->add($key, 'Pick a category for this personal expense.');

                continue;
            }

            if ($categoryId === null) {
                continue;
            }

            $exists = match ($type) {
                'expense' => ExpenseCategory::query()->whereKey($categoryId)->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))->exists(),
                'personal' => PersonalExpenseCategory::query()->whereKey($categoryId)->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))->exists(),
                'income' => IncomeCategory::query()->whereKey($categoryId)->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', $userId))->exists(),
                default => true,
            };

            if (! $exists) {
                $validator->errors()->add($key, 'That category does not belong to this row type.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function storeExpenseRow(array $row, int $userId): Expense
    {
        $category = filled($row['category_id'] ?? null) ? ExpenseCategory::query()->find($row['category_id']) : null;

        $expense = Expense::create([
            'user_id' => $userId,
            'daily_record_id' => $this->dailyRecordId($userId, $row['date']),
            'category_id' => $category?->id,
            'amount' => round((float) $row['amount'], 2),
            'gst_amount' => 0,
            'date' => $row['date'],
            'time' => $this->rowTime($row),
            'description' => filled($row['description'] ?? null) ? $row['description'] : ($category?->name ?? 'Expense'),
            'payment_method' => $row['payment_method'],
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
            'is_voluntary' => (bool) ($category?->is_voluntary ?? false),
            'is_unlogged' => filter_var($row['is_unlogged'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'classification' => 'necessary',
        ]);

        AuditService::log('expense', $expense->id, 'created', null, $expense->toArray(), 'Expense created from bulk entry');

        return $expense;
    }

    /**
     * Saved like the Personal Expense page; "add to main" also files the linked normal
     * expense so the Daily Cash Register sees the money leave.
     *
     * @param  array<string, mixed>  $row
     */
    private function storePersonalRow(array $row, int $userId): PersonalExpense
    {
        $personalExpense = PersonalExpense::create([
            'user_id' => $userId,
            'category_id' => (int) $row['category_id'],
            'amount' => round((float) $row['amount'], 2),
            'gst_amount' => 0,
            'date' => $row['date'],
            'time' => $this->rowTime($row),
            'description' => $row['description'] ?? null,
            'payment_method' => $row['payment_method'],
            'done_by' => filled($row['done_by'] ?? null) ? $row['done_by'] : 'Me',
            'done_to' => $row['done_to'] ?? null,
            'classification' => 'necessary',
            'is_voluntary' => false,
            'is_archived' => false,
        ]);

        if (filter_var($row['add_to_main'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $personalExpense->syncLinkedNormalExpense();
        }

        return $personalExpense;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function storeIncomeRow(array $row, int $userId): Income
    {
        $category = filled($row['category_id'] ?? null) ? IncomeCategory::query()->find($row['category_id']) : null;

        $income = Income::create([
            'user_id' => $userId,
            'daily_record_id' => $this->dailyRecordId($userId, $row['date']),
            'category_id' => $category?->id,
            'amount' => round((float) $row['amount'], 2),
            'source' => mb_substr(filled($row['description'] ?? null) ? $row['description'] : ($category?->name ?? 'Income'), 0, 100),
            'date' => $row['date'],
            'time' => $this->rowTime($row),
            'payment_method' => $row['payment_method'],
            'tally_mode' => 'separate',
            'keep_separate' => filter_var($row['keep_separate'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);

        AuditService::log('income', $income->id, 'created', null, $income->toArray(), 'Income created from bulk entry');

        return $income;
    }

    private function dailyRecordId(int $userId, string $date): int
    {
        return DailyRecord::forUserOnDate($userId, $date)->id;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function rowTime(array $row): string
    {
        return filled($row['time'] ?? null) ? substr((string) $row['time'], 0, 5) : Carbon::now()->format('H:i');
    }
}
