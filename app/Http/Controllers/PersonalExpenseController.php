<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonalExpenseController extends Controller
{
    public function index(Request $request, OptionsService $options): View
    {
        $user = $request->user();
        $query = PersonalExpense::query()
            ->where('user_id', $user->id)
            ->with(['category', 'linkedExpense']);

        // Date range filtering
        $period = $request->get('period', 'all');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        [$startDate, $endDate] = $this->resolveDates($period, $fromDate, $toDate);
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method'));
        }

        if ($request->boolean('voluntary_only')) {
            $query->where('is_voluntary', true);
        }

        $expenses = (clone $query)->orderByDesc('date')->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Metrics calculations
        $totalAmount = (float) (clone $query)->sum('amount');
        $monthStart = Carbon::today()->startOfMonth()->toDateString();
        $monthEnd = Carbon::today()->endOfMonth()->toDateString();
        $monthAmount = (float) PersonalExpense::where('user_id', $user->id)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');
        $voluntaryAmount = (float) (clone $query)->where('is_voluntary', true)->sum('amount');

        // Dynamic categories & Payment methods (reused from standard expenses)
        $categories = PersonalExpenseCategory::query()
            ->where('is_archived', false)
            ->orderBy('name')
            ->get();

        // Ensure default seeded personal categories if none exist
        if ($categories->isEmpty()) {
            $defaults = [
                ['name' => 'Shopping', 'icon' => 'shopping-bag', 'color' => '#ec4899'],
                ['name' => 'Weekend Snacks', 'icon' => 'cookie', 'color' => '#f59e0b'],
                ['name' => 'Tour', 'icon' => 'compass', 'color' => '#06b6d4'],
                ['name' => 'Family', 'icon' => 'heart', 'color' => '#8b5cf6'],
                ['name' => 'Personal', 'icon' => 'user', 'color' => '#3b82f6'],
            ];
            foreach ($defaults as $cat) {
                PersonalExpenseCategory::create([
                    'user_id' => $user->id,
                    'name' => $cat['name'],
                    'icon' => $cat['icon'],
                    'color' => $cat['color'],
                ]);
            }
            $categories = PersonalExpenseCategory::query()->where('is_archived', false)->orderBy('name')->get();
        }

        $paymentMethods = $options->names('payment_method');

        return view('finance.personal_expenses.index', compact(
            'expenses',
            'categories',
            'paymentMethods',
            'totalAmount',
            'monthAmount',
            'voluntaryAmount',
            'period',
            'fromDate',
            'toDate'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category_id' => 'required|exists:personal_expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'time' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_voluntary' => 'nullable|boolean',
            'record_as_normal_expense' => 'nullable|boolean',
        ]);

        $personalExpense = PersonalExpense::create([
            'user_id' => $user->id,
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'time' => $validated['time'] ?? now()->format('H:i'),
            'description' => $validated['description'] ?? null,
            'payment_method' => $validated['payment_method'] ?? 'Cash',
            'notes' => $validated['notes'] ?? null,
            'is_voluntary' => $request->boolean('is_voluntary'),
        ]);

        if ($request->boolean('record_as_normal_expense')) {
            $this->syncNormalExpense($personalExpense);
        }

        return redirect()->route('personal-expenses.index')->with('success', 'Personal expense recorded successfully!');
    }

    public function update(Request $request, PersonalExpense $personalExpense): RedirectResponse
    {
        abort_if($personalExpense->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'category_id' => 'required|exists:personal_expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'time' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'is_voluntary' => 'nullable|boolean',
            'record_as_normal_expense' => 'nullable|boolean',
        ]);

        $personalExpense->update([
            'category_id' => $validated['category_id'],
            'amount' => $validated['amount'],
            'date' => $validated['date'],
            'time' => $validated['time'] ?? $personalExpense->time ?? now()->format('H:i'),
            'description' => $validated['description'] ?? null,
            'payment_method' => $validated['payment_method'] ?? 'Cash',
            'notes' => $validated['notes'] ?? null,
            'is_voluntary' => $request->boolean('is_voluntary'),
        ]);

        if ($request->boolean('record_as_normal_expense')) {
            $this->syncNormalExpense($personalExpense);
        } else {
            if ($personalExpense->linkedExpense) {
                $personalExpense->linkedExpense->delete();
                $personalExpense->update(['expense_id' => null]);
            }
        }

        return redirect()->route('personal-expenses.index')->with('success', 'Personal expense updated.');
    }

    public function convertToNormal(PersonalExpense $personalExpense): RedirectResponse
    {
        abort_if($personalExpense->user_id !== auth()->id(), 403);

        $this->syncNormalExpense($personalExpense);

        return back()->with('success', 'Personal expense recorded as a normal expense successfully!');
    }

    public function destroy(PersonalExpense $personalExpense): RedirectResponse
    {
        abort_if($personalExpense->user_id !== auth()->id(), 403);

        if ($personalExpense->linkedExpense) {
            $personalExpense->linkedExpense->delete();
        }

        $personalExpense->delete();

        return redirect()->route('personal-expenses.index')->with('success', 'Personal expense deleted.');
    }

    private function syncNormalExpense(PersonalExpense $personalExpense): void
    {
        $categoryName = $personalExpense->category?->name ?? 'Personal';
        $normalCategory = ExpenseCategory::firstOrCreate(
            ['name' => $categoryName, 'user_id' => null],
            ['icon' => 'user', 'color' => '#3b82f6', 'is_archived' => false]
        );

        $dayRecord = DailyRecord::firstOrCreate([
            'user_id' => $personalExpense->user_id,
            'record_date' => $personalExpense->date->toDateString(),
        ]);

        $desc = '[Personal] '.($personalExpense->description ?: $categoryName);

        if ($personalExpense->linkedExpense) {
            $personalExpense->linkedExpense->update([
                'category_id' => $normalCategory->id,
                'amount' => $personalExpense->amount,
                'date' => $personalExpense->date->toDateString(),
                'time' => $personalExpense->time ?: now()->format('H:i'),
                'description' => $desc,
                'payment_method' => $personalExpense->payment_method ?: 'Cash',
                'notes' => $personalExpense->notes,
                'is_voluntary' => $personalExpense->is_voluntary,
            ]);
        } else {
            $expense = Expense::create([
                'user_id' => $personalExpense->user_id,
                'daily_record_id' => $dayRecord->id,
                'category_id' => $normalCategory->id,
                'amount' => $personalExpense->amount,
                'date' => $personalExpense->date->toDateString(),
                'time' => $personalExpense->time ?: now()->format('H:i'),
                'description' => $desc,
                'payment_method' => $personalExpense->payment_method ?: 'Cash',
                'notes' => $personalExpense->notes,
                'is_voluntary' => $personalExpense->is_voluntary,
            ]);

            $personalExpense->update(['expense_id' => $expense->id]);
        }
    }

    private function resolveDates(string $periodType, ?string $start, ?string $end): array
    {
        $today = Carbon::today();

        return match ($periodType) {
            'day' => [$today->toDateString(), $today->toDateString()],
            'week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'year' => [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()],
            'custom' => [
                $start ?: $today->copy()->startOfMonth()->toDateString(),
                $end ?: $today->toDateString(),
            ],
            default => [null, null],
        };
    }
}
