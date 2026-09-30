<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\FamilyMember;
use App\Models\PersonalExpenseCategory;
use App\Models\User;
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
    }
}
