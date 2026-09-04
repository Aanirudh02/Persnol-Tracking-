<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class DefaultSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Edit Windows (in days; 0 = no editing after creation)
            ['key' => 'payment_edit_window_days', 'value' => '7', 'type' => 'integer', 'group' => 'edit_windows', 'description' => 'Allowed window in days to edit a payment record'],
            ['key' => 'expense_edit_window_days', 'value' => '7', 'type' => 'integer', 'group' => 'edit_windows', 'description' => 'Allowed window in days to edit an expense record'],
            ['key' => 'income_edit_window_days', 'value' => '7', 'type' => 'integer', 'group' => 'edit_windows', 'description' => 'Allowed window in days to edit an income record'],
            ['key' => 'petrol_edit_window_days', 'value' => '7', 'type' => 'integer', 'group' => 'edit_windows', 'description' => 'Allowed window in days to edit a fuel record'],

            // General & Localization
            ['key' => 'app_timezone', 'value' => 'Asia/Kolkata', 'type' => 'string', 'group' => 'general', 'description' => 'Default system timezone'],
            ['key' => 'app_currency', 'value' => 'INR', 'type' => 'string', 'group' => 'general', 'description' => 'Currency code (INR, USD, etc.)'],
            ['key' => 'currency_symbol', 'value' => '₹', 'type' => 'string', 'group' => 'general', 'description' => 'Currency display symbol'],

            // Prompts
            ['key' => 'morning_prompt_start', 'value' => '07:00', 'type' => 'string', 'group' => 'prompts', 'description' => 'Morning wake-up prompt start time'],
            ['key' => 'morning_prompt_end', 'value' => '09:00', 'type' => 'string', 'group' => 'prompts', 'description' => 'Morning wake-up prompt end time'],
            ['key' => 'night_prompt_start', 'value' => '20:50', 'type' => 'string', 'group' => 'prompts', 'description' => 'Night sleep prompt start time'],
            ['key' => 'night_prompt_end', 'value' => '22:00', 'type' => 'string', 'group' => 'prompts', 'description' => 'Night sleep prompt end time'],

            // Petrol Reminders
            ['key' => 'weekly_petrol_reminder_enabled', 'value' => 'true', 'type' => 'boolean', 'group' => 'petrol', 'description' => 'Enable weekly check for fuel fill-up'],
            ['key' => 'weekly_petrol_reminder_day', 'value' => 'Sunday', 'type' => 'string', 'group' => 'petrol', 'description' => 'Day of the week for fuel check'],
            ['key' => 'weekly_petrol_reminder_time', 'value' => '18:00', 'type' => 'string', 'group' => 'petrol', 'description' => 'Time of day for fuel reminder'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(
                ['key' => $s['key']],
                [
                    'value' => $s['value'],
                    'type' => $s['type'],
                    'group' => $s['group'],
                    'description' => $s['description'],
                ]
            );
        }
    }
}
