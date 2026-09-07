<?php

namespace Database\Seeders;

use App\Models\ActivityCategory;
use App\Models\CustomQuestion;
use App\Models\ExpenseCategory;
use App\Models\FoodCategory;
use App\Models\IncomeCategory;
use App\Models\MistakeCategory;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Expense Categories
        $expenses = [
            ['name' => 'Food', 'icon' => 'utensils', 'color' => '#f97316'],
            ['name' => 'Snacks', 'icon' => 'cookie', 'color' => '#eab308'],
            ['name' => 'Petrol', 'icon' => 'fuel', 'color' => '#ef4444'],
            ['name' => 'Travel', 'icon' => 'bus', 'color' => '#06b6d4'],
            ['name' => 'Shopping', 'icon' => 'shopping-cart', 'color' => '#ec4899'],
            ['name' => 'College', 'icon' => 'graduation-cap', 'color' => '#8b5cf6'],
            ['name' => 'Entertainment', 'icon' => 'film', 'color' => '#3b82f6'],
            ['name' => 'Bills', 'icon' => 'file-text', 'color' => '#64748b'],
            ['name' => 'Health', 'icon' => 'heart', 'color' => '#10b981'],
            ['name' => 'Other', 'icon' => 'more-horizontal', 'color' => '#94a3b8'],
        ];
        foreach ($expenses as $c) {
            ExpenseCategory::updateOrCreate(['name' => $c['name']], $c);
        }

        // Income Categories
        $incomes = [
            ['name' => 'Salary', 'icon' => 'briefcase', 'color' => '#10b981'],
            ['name' => 'Pocket Money', 'icon' => 'wallet', 'color' => '#3b82f6'],
            ['name' => 'Friend Returned Money', 'icon' => 'repeat', 'color' => '#8b5cf6'],
            ['name' => 'Transfer', 'icon' => 'arrow-right-circle', 'color' => '#06b6d4'],
            ['name' => 'Other', 'icon' => 'plus-circle', 'color' => '#64748b'],
        ];
        foreach ($incomes as $c) {
            IncomeCategory::updateOrCreate(['name' => $c['name']], $c);
        }

        // Food Categories
        $foods = [
            ['name' => 'Breakfast', 'icon' => 'sun', 'color' => '#f59e0b'],
            ['name' => 'Lunch', 'icon' => 'utensils', 'color' => '#10b981'],
            ['name' => 'Dinner', 'icon' => 'moon', 'color' => '#6366f1'],
            ['name' => 'Snacks', 'icon' => 'coffee', 'color' => '#ec4899'],
            ['name' => 'Tea / Coffee', 'icon' => 'cup', 'color' => '#d97706'],
            ['name' => 'Fast Food', 'icon' => 'burger', 'color' => '#ef4444'],
            ['name' => 'Sweets', 'icon' => 'cake', 'color' => '#a855f7'],
        ];
        foreach ($foods as $c) {
            FoodCategory::updateOrCreate(['name' => $c['name']], $c);
        }

        // Activity Categories
        $activities = [
            ['name' => 'College', 'icon' => 'graduation-cap', 'color' => '#6366f1'],
            ['name' => 'Study', 'icon' => 'book-open', 'color' => '#3b82f6'],
            ['name' => 'Gym', 'icon' => 'activity', 'color' => '#ef4444'],
            ['name' => 'Project Work', 'icon' => 'code', 'color' => '#10b981'],
            ['name' => 'Shopping', 'icon' => 'shopping-bag', 'color' => '#f59e0b'],
            ['name' => 'Travel', 'icon' => 'navigation', 'color' => '#06b6d4'],
            ['name' => 'Meeting Friend', 'icon' => 'users', 'color' => '#ec4899'],
            ['name' => 'Personal Work', 'icon' => 'user', 'color' => '#8b5cf6'],
            ['name' => 'Movie', 'icon' => 'film', 'color' => '#f43f5e'],
            ['name' => 'Other', 'icon' => 'check-circle', 'color' => '#64748b'],
        ];
        foreach ($activities as $c) {
            ActivityCategory::updateOrCreate(['name' => $c['name']], $c);
        }

        // Mistake Categories
        $mistakes = [
            ['name' => 'College / Academics', 'color' => '#ef4444'],
            ['name' => 'Time Management / Discipline', 'color' => '#f97316'],
            ['name' => 'Finance / Overspending', 'color' => '#eab308'],
            ['name' => 'Health & Sleep', 'color' => '#8b5cf6'],
            ['name' => 'Communication / Social', 'color' => '#06b6d4'],
            ['name' => 'Vehicle / Scooter', 'color' => '#ec4899'],
            ['name' => 'Other', 'color' => '#64748b'],
        ];
        foreach ($mistakes as $c) {
            MistakeCategory::updateOrCreate(['name' => $c['name']], $c);
        }

        // Custom Profile Questions
        $questions = [
            ['question' => "What is your dog's name?", 'type' => 'text', 'sort_order' => 1],
            ['question' => 'Favourite place in Coimbatore?', 'type' => 'text', 'sort_order' => 2],
            ['question' => 'First vehicle registration / nickname?', 'type' => 'text', 'sort_order' => 3],
        ];
        foreach ($questions as $q) {
            CustomQuestion::updateOrCreate(['question' => $q['question']], $q);
        }
    }
}
