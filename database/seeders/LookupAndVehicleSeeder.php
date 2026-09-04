<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LookupOption;
use App\Models\ExpenseCategory;
use App\Models\IncomeCategory;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\PaymentWallet;

class LookupAndVehicleSeeder extends Seeder
{
    public function run(): void
    {
        $lookups = [
            'payment_method' => ['UPI', 'Cash', 'Card', 'Bank Transfer', 'Other'],
            'paid_by' => ['Me', 'Parent'],
            'friend_role' => ['Parent', 'Friend', 'Sibling', 'Colleague', 'Other'],
            'credit_status' => [
                'yet_to_pay' => 'Yet to pay',
                'partially_paid' => 'Partially paid',
                'fully_paid' => 'Fully paid',
                'paid_late' => 'Paid late',
                'failed_to_pay' => 'Failed to pay',
            ],
            'trip_purpose' => ['College', 'Office', 'Errand', 'Personal', 'Other'],
        ];

        $sort = 0;
        foreach ($lookups['payment_method'] as $name) {
            LookupOption::updateOrCreate(
                ['user_id' => null, 'type' => 'payment_method', 'name' => $name],
                ['sort_order' => $sort++, 'is_system' => true, 'is_active' => true]
            );
        }
        $sort = 0;
        foreach ($lookups['paid_by'] as $name) {
            LookupOption::updateOrCreate(
                ['user_id' => null, 'type' => 'paid_by', 'name' => $name],
                ['sort_order' => $sort++, 'is_system' => true, 'is_active' => true]
            );
        }
        $sort = 0;
        foreach ($lookups['friend_role'] as $name) {
            LookupOption::updateOrCreate(
                ['user_id' => null, 'type' => 'friend_role', 'name' => $name],
                ['sort_order' => $sort++, 'is_system' => true, 'is_active' => true]
            );
        }
        $sort = 0;
        foreach ($lookups['credit_status'] as $key => $label) {
            LookupOption::updateOrCreate(
                ['user_id' => null, 'type' => 'credit_status', 'name' => $key],
                ['sort_order' => $sort++, 'is_system' => true, 'is_active' => true, 'icon' => $label]
            );
        }
        $sort = 0;
        foreach ($lookups['trip_purpose'] as $name) {
            LookupOption::updateOrCreate(
                ['user_id' => null, 'type' => 'trip_purpose', 'name' => $name],
                ['sort_order' => $sort++, 'is_system' => true, 'is_active' => true]
            );
        }

        ExpenseCategory::updateOrCreate(
            ['name' => 'Archived / Historical', 'user_id' => null],
            ['icon' => 'archive', 'color' => '#94a3b8', 'is_archived' => true, 'is_voluntary' => false]
        );
        ExpenseCategory::updateOrCreate(
            ['name' => 'Voluntary', 'user_id' => null],
            ['icon' => 'heart', 'color' => '#f472b6', 'is_archived' => false, 'is_voluntary' => true]
        );
        IncomeCategory::updateOrCreate(
            ['name' => 'Archived / Historical', 'user_id' => null],
            ['icon' => 'archive', 'color' => '#94a3b8', 'is_archived' => true]
        );

        Setting::updateOrCreate(
            ['key' => 'morning_prompt_start'],
            ['value' => '07:00', 'type' => 'string', 'group' => 'prompts', 'description' => 'Morning prompt window start']
        );
        Setting::updateOrCreate(
            ['key' => 'morning_prompt_end'],
            ['value' => '09:00', 'type' => 'string', 'group' => 'prompts', 'description' => 'Morning prompt window end']
        );
        Setting::updateOrCreate(
            ['key' => 'night_prompt_start'],
            ['value' => '20:50', 'type' => 'string', 'group' => 'prompts', 'description' => 'Night prompt window start']
        );
        Setting::updateOrCreate(
            ['key' => 'night_prompt_end'],
            ['value' => '22:00', 'type' => 'string', 'group' => 'prompts', 'description' => 'Night prompt window end']
        );
        Setting::updateOrCreate(
            ['key' => 'debit_wallet_for_voluntary'],
            ['value' => 'false', 'type' => 'boolean', 'group' => 'finance', 'description' => 'Debit wallets for voluntary spend']
        );

        foreach (User::all() as $user) {
            Vehicle::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'TVS Pep+'],
                [
                    'make' => 'TVS',
                    'model' => 'Pep+',
                    'year' => 2006,
                    'fuel_type' => 'Petrol',
                    'default_mileage_kmpl' => 40,
                    'is_default' => true,
                ]
            );

            foreach (['UPI', 'Cash'] as $method) {
                PaymentWallet::firstOrCreate(
                    ['user_id' => $user->id, 'payment_method' => $method],
                    ['is_enabled' => false, 'opening_balance' => 0, 'opening_as_of' => now()->toDateString()]
                );
            }
        }
    }
}
