<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GPayAudit;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\PaymentWallet;
use App\Services\CloudinaryService;
use App\Services\GPayAuditorService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GPayAuditorController extends Controller
{
    public function __construct(
        private readonly GPayAuditorService $auditorService,
        private readonly CloudinaryService $cloudinary,
        private readonly WalletService $walletService
    ) {}

    /**
     * Show the GPay Screenshot Auditor page.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $auditId = $request->input('audit_id');
        $selectedAudit = null;

        if ($auditId) {
            $selectedAudit = GPayAudit::query()
                ->where('user_id', $user->id)
                ->where('id', $auditId)
                ->first();
        }

        if (! $selectedAudit) {
            $selectedAudit = GPayAudit::query()
                ->where('user_id', $user->id)
                ->latest()
                ->first();
        }

        $history = GPayAudit::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(8)
            ->get();

        $upiWallet = PaymentWallet::query()
            ->where('user_id', $user->id)
            ->where('payment_method', 'UPI')
            ->first();

        $upiBalance = $upiWallet ? $this->walletService->balanceFor($user->id, $upiWallet) : 0.0;

        return view('finance.gpay-auditor.index', [
            'audit' => $selectedAudit,
            'history' => $history,
            'upiBalance' => $upiBalance,
        ]);
    }

    /**
     * Upload screenshot and run audit against logged expenses and incomes.
     */
    public function audit(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'screenshot' => ['nullable', 'image', 'max:10240'],
            'raw_text' => ['required_without:screenshot', 'nullable', 'string'],
            'gpay_balance' => ['nullable', 'numeric'],
            'month_year' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            try {
                $screenshotPath = $this->cloudinary->upload($request->file('screenshot'), 'gpay_audits');
            } catch (\Throwable $e) {
                $screenshotPath = $request->file('screenshot')->store('gpay_audits', 'public');
            }
        }

        $rawText = (string) ($validated['raw_text'] ?? '');
        $items = $this->auditorService->parseRawText($rawText);

        if (empty($items)) {
            return back()
                ->withInput()
                ->with('error', '⚠️ No transactions could be identified from the text. Please verify the text format (Merchant, Date, Amount) or paste text directly.');
        }

        $results = $this->auditorService->audit($user, $items, $validated['month_year'] ?? null);

        $audit = GPayAudit::create([
            'user_id' => $user->id,
            'screenshot_path' => $screenshotPath,
            'month_year' => $validated['month_year'] ?? Carbon::now()->format('Y-m'),
            'raw_text' => $rawText,
            'extracted_items' => $items,
            'audit_results' => $results,
            'gpay_balance' => $validated['gpay_balance'] ?? null,
            'status' => 'completed',
        ]);

        return redirect()
            ->route('finance.gpay-auditor.index', ['audit_id' => $audit->id])
            ->with('success', '🎯 Screenshot audited successfully! Found '.count($items).' transactions. Review the breakdown and questions below.');
    }

    /**
     * Handle quick actions from questions (Switch to Cash, Quick Add Expense, Quick Add Income, Sync Amount).
     */
    public function quickAction(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'action' => ['required', 'string', 'in:convert_to_cash,add_expense,add_income,sync_amount,delete_expense'],
            'expense_id' => ['nullable', 'integer', 'exists:expenses,id'],
            'income_id' => ['nullable', 'integer', 'exists:incomes,id'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'merchant' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'audit_id' => ['nullable', 'integer'],
        ]);

        $auditId = $validated['audit_id'] ?? null;

        return DB::transaction(function () use ($user, $validated, $auditId): RedirectResponse {
            $action = $validated['action'];

            if ($action === 'convert_to_cash') {
                $expense = Expense::query()
                    ->where('user_id', $user->id)
                    ->findOrFail($validated['expense_id']);

                $oldMethod = $expense->payment_method;
                $expense->update([
                    'payment_method' => 'Cash',
                    'notes' => trim(($expense->notes ?? '')."\n[Auditor: Switched from {$oldMethod} to Cash - not debited on GPay]"),
                ]);

                return redirect()
                    ->route('finance.gpay-auditor.index', ['audit_id' => $auditId])
                    ->with('success', "✅ '{$expense->description}' (₹{$expense->totalAmount()}) switched from UPI to Cash! Your website GPay balance has increased.");
            }

            if ($action === 'sync_amount') {
                $expense = Expense::query()
                    ->where('user_id', $user->id)
                    ->findOrFail($validated['expense_id']);

                $oldAmount = $expense->totalAmount();
                $newAmount = (float) $validated['amount'];

                $expense->update([
                    'amount' => $newAmount,
                    'notes' => trim(($expense->notes ?? '')."\n[Auditor: Synced amount from ₹{$oldAmount} to ₹{$newAmount} to match GPay]"),
                ]);

                return redirect()
                    ->route('finance.gpay-auditor.index', ['audit_id' => $auditId])
                    ->with('success', "✅ '{$expense->description}' amount adjusted from ₹{$oldAmount} to ₹{$newAmount} to match GPay exactly.");
            }

            if ($action === 'delete_expense') {
                $expense = Expense::query()
                    ->where('user_id', $user->id)
                    ->findOrFail($validated['expense_id']);

                $desc = $expense->description;
                $amt = $expense->totalAmount();
                $expense->delete();

                return redirect()
                    ->route('finance.gpay-auditor.index', ['audit_id' => $auditId])
                    ->with('success', "🗑️ Fake/Unbacked expense '{$desc}' (₹{$amt}) deleted successfully.");
            }

            if ($action === 'add_expense') {
                $date = $validated['date'] ?? Carbon::today()->toDateString();
                $dailyRecord = DailyRecord::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'record_date' => $date,
                ]);

                // General category fallback
                $category = ExpenseCategory::query()
                    ->where('user_id', $user->id)
                    ->where('is_archived', false)
                    ->first();

                $expense = Expense::create([
                    'user_id' => $user->id,
                    'daily_record_id' => $dailyRecord->id,
                    'category_id' => $category?->id,
                    'amount' => $validated['amount'],
                    'gst_amount' => 0.0,
                    'date' => $date,
                    'time' => Carbon::now()->format('H:i'),
                    'description' => $validated['merchant'] ?: 'GPay Expense',
                    'payment_method' => 'UPI',
                    'paid_by' => 'Me',
                    'paid_by_type' => 'me',
                    'notes' => '[Created via GPay Screenshot Auditor]',
                    'classification' => 'necessary',
                ]);

                return redirect()
                    ->route('finance.gpay-auditor.index', ['audit_id' => $auditId])
                    ->with('success', "✅ Added '{$expense->description}' (₹{$expense->amount}) to your Expenses under UPI.");
            }

            if ($action === 'add_income') {
                $date = $validated['date'] ?? Carbon::today()->toDateString();

                $category = IncomeCategory::query()
                    ->where('user_id', $user->id)
                    ->first();

                $income = Income::create([
                    'user_id' => $user->id,
                    'category_id' => $category?->id,
                    'amount' => $validated['amount'],
                    'date' => $date,
                    'time' => Carbon::now()->format('H:i'),
                    'source' => $validated['merchant'] ?: 'GPay Credit',
                    'payment_method' => 'UPI',
                    'notes' => '[Created via GPay Screenshot Auditor]',
                ]);

                return redirect()
                    ->route('finance.gpay-auditor.index', ['audit_id' => $auditId])
                    ->with('success', "✅ Logged '{$income->source}' (+₹{$income->amount}) as an Income under UPI.");
            }

            return redirect()->route('finance.gpay-auditor.index', ['audit_id' => $auditId]);
        });
    }
}
