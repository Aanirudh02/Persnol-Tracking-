<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FoodCategory;
use App\Models\FoodEntry;
use App\Models\Setting;
use App\Services\OptionsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoodController extends Controller
{
    public function index(Request $request, OptionsService $options)
    {
        $user = $request->user();
        $today = Carbon::today()->toDateString();
        $startOfWeek = Carbon::today()->startOfWeek()->toDateString();
        $startOfMonth = Carbon::today()->startOfMonth()->toDateString();

        $todaySnacks = FoodEntry::where('user_id', $user->id)
            ->where('date', $today)
            ->where('is_snack', true)
            ->count();

        $weeklySnackSpend = (float) FoodEntry::where('user_id', $user->id)
            ->where('date', '>=', $startOfWeek)
            ->where('is_snack', true)
            ->sum(DB::raw('amount + gst_amount'));

        $monthlySnackSpend = (float) FoodEntry::where('user_id', $user->id)
            ->where('date', '>=', $startOfMonth)
            ->where('is_snack', true)
            ->sum(DB::raw('amount + gst_amount'));

        $frequentSnacks = FoodEntry::where('user_id', $user->id)
            ->where('is_snack', true)
            ->select('item_name', DB::raw('count(*) as count'), DB::raw('sum(amount + gst_amount) as total_spent'))
            ->groupBy('item_name')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        $query = FoodEntry::where('user_id', $user->id)->with(['category', 'expense.expenseGroup']);
        if ($request->filled('is_snack')) {
            $query->where('is_snack', $request->boolean('is_snack'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $entries = $query->orderByDesc('date')->orderByDesc('created_at')->paginate(15)->withQueryString();
        $categories = FoodCategory::orderBy('name')->get();
        $paymentMethods = $options->names('payment_method');
        $parentExpenses = Expense::where('user_id', $user->id)
            ->whereNull('parent_id')
            ->withSum('subItems as sub_items_total', 'amount')
            ->withSum('subItems as sub_items_gst_total', 'gst_amount')
            ->withSum('foodEntries as food_entries_total', 'amount')
            ->withSum('foodEntries as food_entries_gst_total', 'gst_amount')
            ->latest()
            ->take(40)
            ->get();
        $parentExpenses->each(function (Expense $expense): void {
            $expense->remaining_amount = round(max(0, $expense->totalAmount() - (float) ($expense->sub_items_total ?? 0) - (float) ($expense->sub_items_gst_total ?? 0) - (float) ($expense->food_entries_total ?? 0) - (float) ($expense->food_entries_gst_total ?? 0)), 2);
        });

        return view('food.index', compact(
            'entries',
            'categories',
            'todaySnacks',
            'weeklySnackSpend',
            'monthlySnackSpend',
            'frequentSnacks',
            'paymentMethods',
            'parentExpenses'
        ));
    }

    public function store(Request $request, OptionsService $options)
    {
        $validated = $request->validate([
            'item_count' => 'nullable|integer|min:1|max:4',
            'items' => 'required|array|min:1|max:4',
            'items.*.item_name' => 'nullable|string|max:100',
            'items.*.category_id' => 'nullable|exists:food_categories,id',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.amount' => 'nullable|numeric|min:0',
            'gst_amount' => 'nullable|numeric|min:0',
            'is_snack' => 'nullable|boolean',
            'date' => 'required|date',
            'time' => 'nullable',
            'location' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'expense_mode' => 'nullable|in:none,separate,sub_item,voluntary',
            'parent_expense_id' => 'nullable|exists:expenses,id',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $items = collect($validated['items'])
            ->map(fn ($row) => [
                'item_name' => trim((string) ($row['item_name'] ?? '')),
                'category_id' => $row['category_id'] ?? null,
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
                'amount' => (float) ($row['amount'] ?? 0),
            ])
            ->filter(fn ($row) => $row['item_name'] !== '')
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Add at least one item name.');
        }

        $dailyRecord = DailyRecord::firstOrCreate([
            'user_id' => $request->user()->id,
            'record_date' => $validated['date'],
        ]);

        $parentExpenseId = filled($validated['parent_expense_id'] ?? null) ? (int) $validated['parent_expense_id'] : null;
        $mode = $validated['expense_mode'] ?? 'none';
        if ($parentExpenseId) {
            $mode = 'sub_item';
        }

        $totalAmount = (float) $items->sum('amount');
        $gstAmount = round((float) ($validated['gst_amount'] ?? 0), 2);
        $names = $items->pluck('item_name')->implode(', ');
        $expenseId = null;
        $autoCreated = false;
        $allocatedGst = $items->map(function (array $row, int $index) use ($items, $totalAmount, $gstAmount): array {
            $row['gst_amount'] = $index === $items->count() - 1
                ? 0
                : ($totalAmount > 0 ? round($gstAmount * ((float) $row['amount'] / $totalAmount), 2) : 0);

            return $row;
        })->values();
        if ($allocatedGst->isNotEmpty()) {
            $allocatedGst = $allocatedGst->map(function (array $row, int $index) use ($allocatedGst, $gstAmount): array {
                if ($index === $allocatedGst->count() - 1) {
                    $row['gst_amount'] = round($gstAmount - (float) $allocatedGst->slice(0, $index)->sum('gst_amount'), 2);
                }

                return $row;
            });
        }

        if ($mode === 'sub_item' && $parentExpenseId) {
            $parent = Expense::where('user_id', $request->user()->id)->whereNull('parent_id')->findOrFail($parentExpenseId);
            if ($totalAmount + $gstAmount > $parent->remainingAmount()) {
                return back()->withInput()->with('error', 'The food items exceed the remaining amount of the selected expense.');
            }
            $expenseId = $parent->id;
        } elseif (in_array($mode, ['separate', 'voluntary'], true) && $totalAmount + $gstAmount > 0) {
            $expCat = null;
            if ($mode === 'voluntary') {
                $expCat = ExpenseCategory::where('is_voluntary', true)->first()
                    ?? ExpenseCategory::whereRaw('LOWER(name) = ?', ['voluntary'])->first();
            } else {
                $settingKey = $request->boolean('is_snack')
                    ? 'snack_default_expense_category_id'
                    : 'food_default_expense_category_id';
                $categoryId = Setting::getVal($settingKey);
                if ($categoryId) {
                    $expCat = ExpenseCategory::query()
                        ->where('is_archived', false)
                        ->find($categoryId);
                }
            }

            $defaultMethod = $validated['payment_method']
                ?? ($options->names('payment_method')[0] ?? 'Cash');

            $expense = Expense::create([
                'user_id' => $request->user()->id,
                'daily_record_id' => $dailyRecord->id,
                'category_id' => $expCat?->id,
                'parent_id' => null,
                'description' => $names,
                'amount' => $totalAmount,
                'gst_amount' => $gstAmount,
                'date' => $validated['date'],
                'time' => $validated['time'] ?? Carbon::now()->format('H:i'),
                'payment_method' => $defaultMethod,
                'notes' => '(From Food / Snack log)',
                'is_voluntary' => $mode === 'voluntary',
                'paid_by' => 'Me',
                'paid_by_type' => 'me',
            ]);
            $expenseId = $expense->id;
            $autoCreated = true;
        }

        foreach ($allocatedGst as $row) {
            FoodEntry::create([
                'user_id' => $request->user()->id,
                'daily_record_id' => $dailyRecord->id,
                'category_id' => $row['category_id'],
                'expense_id' => $expenseId,
                'auto_create_expense' => $autoCreated,
                'item_name' => $row['item_name'],
                'is_snack' => $request->boolean('is_snack'),
                'quantity' => $row['quantity'],
                'amount' => $row['amount'],
                'gst_amount' => $row['gst_amount'],
                'date' => $validated['date'],
                'time' => $validated['time'] ?? Carbon::now()->format('H:i'),
                'location' => $validated['location'] ?? null,
                'paid_by' => 'Me',
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        $msg = $items->count().' food/snack item(s) recorded!';
        if ($mode === 'sub_item' && $expenseId) {
            $msg .= ' Linked under existing expense.';
        } elseif ($autoCreated) {
            $msg .= ' Linked expense ₹'.number_format($totalAmount, 2).'.';
        }

        $redirect = $request->boolean('is_snack')
            ? route('food.index', ['is_snack' => 1])
            : route('food.index');

        return redirect($redirect)->with('success', $msg);
    }

    public function update(Request $request, FoodEntry $food)
    {
        if ($food->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'item_name' => 'required|string|max:100',
            'category_id' => 'nullable|exists:food_categories,id',
            'quantity' => 'nullable|integer|min:1',
            'amount' => 'nullable|numeric|min:0',
            'gst_amount' => 'nullable|numeric|min:0',
            'date' => 'required|date',
            'time' => 'nullable',
            'location' => 'nullable|string|max:100',
            'is_snack' => 'nullable|boolean',
            'parent_expense_id' => 'nullable|exists:expenses,id',
            'notes' => 'nullable|string',
        ]);

        $expenseId = null;
        if (filled($validated['parent_expense_id'] ?? null)) {
            $parent = Expense::where('user_id', $request->user()->id)
                ->whereNull('parent_id')
                ->findOrFail($validated['parent_expense_id']);
            $expenseId = $parent->id;
        }

        $currentAmount = (float) $food->amount;
        $currentGst = (float) $food->gst_amount;
        $newAmount = (float) ($validated['amount'] ?? 0);
        $newGst = (float) ($validated['gst_amount'] ?? 0);
        if ($expenseId) {
            $remaining = $parent->remainingAmount();
            if ($food->expense_id === $expenseId) {
                $remaining += $currentAmount + $currentGst;
            }
            if ($newAmount + $newGst > $remaining) {
                return back()->withInput()->with('error', 'The food amount exceeds the remaining amount of the selected expense.');
            }
        }

        $food->update([
            'item_name' => $validated['item_name'],
            'category_id' => $validated['category_id'] ?? null,
            'quantity' => max(1, (int) ($validated['quantity'] ?? 1)),
            'amount' => (float) ($validated['amount'] ?? 0),
            'gst_amount' => $newGst,
            'date' => $validated['date'],
            'time' => $validated['time'] ?? $food->time,
            'location' => $validated['location'] ?? null,
            'is_snack' => $request->boolean('is_snack'),
            'expense_id' => $expenseId,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Food/snack item updated.');
    }

    public function destroy(Request $request, FoodEntry $food)
    {
        if ($food->user_id !== auth()->id()) {
            abort(403);
        }
        $food->delete();

        return redirect()->route('food.index')->with('success', 'Food entry deleted.');
    }
}
