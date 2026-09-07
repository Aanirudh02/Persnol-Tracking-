<?php

namespace App\Http\Controllers;

use App\Models\ActivityCategory;
use App\Models\ExpenseCategory;
use App\Models\FoodCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Store a new expense category
     */
    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:20',
        ]);

        ExpenseCategory::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#6366f1',
            'icon' => $validated['icon'] ?? '💸',
        ]);

        return back()->with('success', "Category '{$validated['name']}' added!");
    }

    public function destroyExpense(Request $request, ExpenseCategory $category)
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    /**
     * Store a new food category
     */
    public function storeFood(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:20',
        ]);

        FoodCategory::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#f59e0b',
            'icon' => $validated['icon'] ?? '🍽️',
        ]);

        return back()->with('success', "Food category '{$validated['name']}' added!");
    }

    public function destroyFood(Request $request, FoodCategory $category)
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }
        $category->delete();

        return back()->with('success', 'Food category deleted.');
    }

    /**
     * Store a new activity category
     */
    public function storeActivity(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:20',
        ]);

        ActivityCategory::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'color' => $validated['color'] ?? '#8b5cf6',
            'icon' => $validated['icon'] ?? '🎯',
        ]);

        return back()->with('success', "Activity category '{$validated['name']}' added!");
    }

    public function destroyActivity(Request $request, ActivityCategory $category)
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }
        $category->delete();

        return back()->with('success', 'Activity category deleted.');
    }
}
