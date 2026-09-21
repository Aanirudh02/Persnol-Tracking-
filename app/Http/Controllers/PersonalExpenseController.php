<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FamilyMember;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Models\PersonalExpenseGroup;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersonalExpenseController extends Controller
{
    public function index(Request $request, OptionsService $options): View
    {
        $user = $request->user();

        $status = $request->get('status', 'active');
        $activeCount = PersonalExpense::where('user_id', $user->id)
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->count();
        $archivedCount = PersonalExpense::where('user_id', $user->id)
            ->where('is_archived', true)
            ->count();

        $query = PersonalExpense::query()
            ->where('user_id', $user->id)
            ->with([
                'category',
                'linkedExpense',
                'personalExpenseGroup.personalExpenses.category',
            ]);

        if ($status === 'archived') {
            $query->where('is_archived', true);
        } else {
            $query->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false));
        }

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

        if ($request->filled('done_by')) {
            $query->where('done_by', 'like', "%{$request->string('done_by')}%");
        }

        if ($request->filled('done_to')) {
            $query->where('done_to', 'like', "%{$request->string('done_to')}%");
        }

        if ($request->boolean('voluntary_only')) {
            $query->where('is_voluntary', true);
        }

        $expenses = (clone $query)->orderByDesc('date')->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Metrics calculations (based on active expenses in filter)
        $metricQuery = (clone $query);
        $totalAmount = (float) (clone $metricQuery)->sum('amount');
        $monthStart = Carbon::today()->startOfMonth()->toDateString();
        $monthEnd = Carbon::today()->endOfMonth()->toDateString();
        $monthAmount = (float) PersonalExpense::where('user_id', $user->id)
            ->where(fn ($q) => $q->whereNull('is_archived')->orWhere('is_archived', false))
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->sum('amount');
        $voluntaryAmount = (float) (clone $metricQuery)->where('is_voluntary', true)->sum('amount');

        // Dynamic categories & Payment methods
        $categories = PersonalExpenseCategory::query()
            ->where('is_archived', false)
            ->orderBy('name')
            ->get();

        // Ensure default seeded personal categories including 'Me'
        if ($categories->isEmpty()) {
            $defaults = [
                ['name' => 'Me', 'icon' => 'user-check', 'color' => '#6366f1'],
                ['name' => 'Personal', 'icon' => 'user', 'color' => '#3b82f6'],
                ['name' => 'Family', 'icon' => 'heart', 'color' => '#8b5cf6'],
                ['name' => 'Shopping', 'icon' => 'shopping-bag', 'color' => '#ec4899'],
                ['name' => 'Weekend Snacks', 'icon' => 'cookie', 'color' => '#f59e0b'],
                ['name' => 'Tour', 'icon' => 'compass', 'color' => '#06b6d4'],
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

        // Ensure category "Me" exists
        if (! $categories->contains('name', 'Me')) {
            PersonalExpenseCategory::create([
                'user_id' => $user->id,
                'name' => 'Me',
                'icon' => 'user-check',
                'color' => '#6366f1',
            ]);
            $categories = PersonalExpenseCategory::query()->where('is_archived', false)->orderBy('name')->get();
        }

        $paymentMethods = $options->names('payment_method');
        $familyMembers = FamilyMember::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return view('finance.personal_expenses.index', compact(
            'expenses',
            'categories',
            'paymentMethods',
            'familyMembers',
            'totalAmount',
            'monthAmount',
            'voluntaryAmount',
            'period',
            'fromDate',
            'toDate',
            'status',
            'activeCount',
            'archivedCount'
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
            'done_by' => 'nullable|string|max:100',
            'done_to' => 'nullable|string|max:100',
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
            'done_by' => ! empty($validated['done_by']) ? $validated['done_by'] : 'Me',
            'done_to' => $validated['done_to'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_voluntary' => $request->boolean('is_voluntary'),
            'is_archived' => false,
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
            'done_by' => 'nullable|string|max:100',
            'done_to' => 'nullable|string|max:100',
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
            'done_by' => ! empty($validated['done_by']) ? $validated['done_by'] : 'Me',
            'done_to' => $validated['done_to'] ?? null,
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

    public function group(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_ids' => 'required|array|min:2',
            'expense_ids.*' => 'integer|exists:personal_expenses,id',
            'name' => 'nullable|string|max:255',
        ]);

        $expenses = PersonalExpense::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('personal_expense_group_id')
            ->whereIn('id', $validated['expense_ids'])
            ->get();

        if ($expenses->count() !== count(array_unique($validated['expense_ids']))) {
            return back()->withInput()->with('error', 'Select at least two of your own personal expenses that are not already in a group.');
        }

        DB::transaction(function () use ($request, $validated, $expenses): PersonalExpenseGroup {
            $group = PersonalExpenseGroup::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'] ?? ($expenses->first()->description ?: 'Personal Expense Group'),
            ]);
            $expenses->each->update(['personal_expense_group_id' => $group->id]);

            return $group;
        });

        return redirect()->route('personal-expenses.index')->with('success', 'Personal expenses grouped successfully.');
    }

    public function ungroup(Request $request, PersonalExpenseGroup $personalExpenseGroup): RedirectResponse
    {
        abort_if($personalExpenseGroup->user_id !== $request->user()->id, 403);

        DB::transaction(function () use ($personalExpenseGroup): void {
            $personalExpenseGroup->personalExpenses()->update(['personal_expense_group_id' => null]);
            $personalExpenseGroup->delete();
        });

        return back()->with('success', 'Personal expense group removed. The individual expense rows were kept.');
    }

    public function renameGroup(Request $request, PersonalExpenseGroup $personalExpenseGroup): RedirectResponse
    {
        abort_if($personalExpenseGroup->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $personalExpenseGroup->update(['name' => $validated['name']]);

        return back()->with('success', 'Personal expense group name updated.');
    }

    public function detachFromGroup(Request $request, PersonalExpenseGroup $personalExpenseGroup, PersonalExpense $personalExpense): RedirectResponse
    {
        if ($personalExpenseGroup->user_id !== $request->user()->id
            || $personalExpense->user_id !== $request->user()->id
            || $personalExpense->personal_expense_group_id !== $personalExpenseGroup->id) {
            abort(403);
        }

        $personalExpense->update(['personal_expense_group_id' => null]);

        if ($personalExpenseGroup->personalExpenses()->count() <= 1) {
            $personalExpenseGroup->personalExpenses()->update(['personal_expense_group_id' => null]);
            $personalExpenseGroup->delete();

            return back()->with('success', 'Expense detached from group. Group dissolved as fewer than 2 items remain.');
        }

        return back()->with('success', 'Expense detached from group.');
    }

    public function archive(Request $request, PersonalExpense $personalExpense): RedirectResponse
    {
        abort_if($personalExpense->user_id !== $request->user()->id, 403);

        $personalExpense->update(['is_archived' => true]);

        return back()->with('success', 'Personal expense moved to Archived/Historical.');
    }

    public function restore(Request $request, PersonalExpense $personalExpense): RedirectResponse
    {
        abort_if($personalExpense->user_id !== $request->user()->id, 403);

        $personalExpense->update(['is_archived' => false]);

        return back()->with('success', 'Personal expense restored to Active.');
    }

    public function convertToNormal(PersonalExpense $personalExpense): RedirectResponse
    {
        abort_if($personalExpense->user_id !== auth()->id(), 403);

        $this->syncNormalExpense($personalExpense);

        return back()->with('success', 'Personal expense recorded as a normal expense successfully!');
    }

    public function destroy(Request $request, PersonalExpense $personalExpense): RedirectResponse
    {
        abort_if($personalExpense->user_id !== auth()->id(), 403);

        if ($request->input('action') === 'archive') {
            return $this->archive($request, $personalExpense);
        }

        if ($personalExpense->linkedExpense) {
            $personalExpense->linkedExpense->delete();
        }

        if ($request->boolean('permanent') || $request->input('action') === 'delete_permanent') {
            $personalExpense->forceDelete();

            return back()->with('success', 'Personal expense permanently deleted.');
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
