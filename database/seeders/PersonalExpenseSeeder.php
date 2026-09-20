<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FamilyMember;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Models\PersonalExpenseGroup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PersonalExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        // 1. Seed Family Members
        FamilyMember::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Mother'],
            ['relationship' => 'Mother', 'phone' => null, 'details' => 'Primary family member']
        );
        FamilyMember::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Father'],
            ['relationship' => 'Father', 'phone' => null, 'details' => 'Primary family member']
        );

        // 2. Ensure Personal Expense Categories exist
        $familyCat = PersonalExpenseCategory::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Family'],
            ['icon' => 'heart', 'color' => '#8b5cf6', 'is_archived' => false]
        );

        $personalCat = PersonalExpenseCategory::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Personal'],
            ['icon' => 'user', 'color' => '#3b82f6', 'is_archived' => false]
        );

        $shoppingCat = PersonalExpenseCategory::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Shopping'],
            ['icon' => 'shopping-bag', 'color' => '#ec4899', 'is_archived' => false]
        );

        // 3. Seed Individual Personal Expenses with Done By & Done To
        PersonalExpense::firstOrCreate(
            ['user_id' => $user->id, 'description' => 'Weekly Groceries & Vegetables'],
            [
                'category_id' => $familyCat->id,
                'amount' => 640.00,
                'date' => Carbon::today()->subDays(2)->toDateString(),
                'time' => '10:30',
                'payment_method' => 'UPI',
                'done_by' => 'Me',
                'done_to' => 'Mother',
                'notes' => 'Fresh vegetables for the week',
                'is_voluntary' => false,
                'is_archived' => false,
            ]
        );

        PersonalExpense::firstOrCreate(
            ['user_id' => $user->id, 'description' => 'Books & Skill Development'],
            [
                'category_id' => $personalCat->id,
                'amount' => 450.00,
                'date' => Carbon::today()->subDays(4)->toDateString(),
                'time' => '15:20',
                'payment_method' => 'UPI',
                'done_by' => 'Me',
                'done_to' => 'Self',
                'notes' => 'Study reference material',
                'is_voluntary' => true,
                'is_archived' => false,
            ]
        );

        // 4. Seed Grouped Personal Expenses
        $group = PersonalExpenseGroup::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Weekend Family Outing & Dinner']
        );

        $groupExpense1 = PersonalExpense::firstOrCreate(
            ['user_id' => $user->id, 'description' => 'Family Restaurant Dinner'],
            [
                'category_id' => $familyCat->id,
                'personal_expense_group_id' => $group->id,
                'amount' => 1250.00,
                'date' => Carbon::today()->subDays(1)->toDateString(),
                'time' => '20:15',
                'payment_method' => 'Card',
                'done_by' => 'Me',
                'done_to' => 'Family',
                'notes' => 'Dinner with family at restaurant',
                'is_voluntary' => true,
                'is_archived' => false,
            ]
        );
        $groupExpense1->update(['personal_expense_group_id' => $group->id]);

        $groupExpense2 = PersonalExpense::firstOrCreate(
            ['user_id' => $user->id, 'description' => 'Desserts & Ice Cream Treat'],
            [
                'category_id' => $familyCat->id,
                'personal_expense_group_id' => $group->id,
                'amount' => 320.00,
                'date' => Carbon::today()->subDays(1)->toDateString(),
                'time' => '21:30',
                'payment_method' => 'UPI',
                'done_by' => 'Me',
                'done_to' => 'Family',
                'notes' => 'Post-dinner treats',
                'is_voluntary' => true,
                'is_archived' => false,
            ]
        );
        $groupExpense2->update(['personal_expense_group_id' => $group->id]);

        // 5. Seed Archived / Historical Personal Expense
        PersonalExpense::firstOrCreate(
            ['user_id' => $user->id, 'description' => 'Archived Music Subscription (Old)'],
            [
                'category_id' => $personalCat->id,
                'amount' => 199.00,
                'date' => Carbon::today()->subMonths(2)->toDateString(),
                'time' => '12:00',
                'payment_method' => 'UPI',
                'done_by' => 'Me',
                'done_to' => 'Self',
                'notes' => 'Old cancelled subscription test data',
                'is_voluntary' => true,
                'is_archived' => true,
            ]
        );

        // 6. Seed Archived Normal Expense to allow testing normal expense historical tab
        $generalCat = ExpenseCategory::first();
        if ($generalCat) {
            Expense::firstOrCreate(
                ['user_id' => $user->id, 'description' => 'Previous Semester Lab Record Book (Historical)'],
                [
                    'category_id' => $generalCat->id,
                    'amount' => 280.00,
                    'gst_amount' => 0.00,
                    'date' => Carbon::today()->subMonths(3)->toDateString(),
                    'time' => '11:00',
                    'payment_method' => 'Cash',
                    'paid_by' => 'Me',
                    'notes' => 'Archived test record',
                    'is_voluntary' => false,
                    'is_archived' => true,
                ]
            );
        }
    }
}
