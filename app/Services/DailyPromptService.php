<?php

namespace App\Services;

use App\Models\DailyRecord;
use App\Models\FuelEntry;
use App\Models\Setting;
use Carbon\Carbon;

class DailyPromptService
{
    public function getActivePrompts(int $userId): array
    {
        $prompts = [];
        $now = Carbon::now(Setting::getVal('app_timezone', 'Asia/Kolkata'));
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();
        $yesterday = $now->copy()->subDay()->toDateString();

        $dailyRecord = DailyRecord::firstOrCreate(
            ['user_id' => $userId, 'record_date' => $today],
            [
                'wake_up_prompt_dismissed' => false,
                'sleep_prompt_dismissed' => false,
            ]
        );

        $mStart = Setting::getVal('morning_prompt_start', '07:00');
        $mEnd = Setting::getVal('morning_prompt_end', '09:00');

        if ($currentTime >= $mStart && $currentTime <= $mEnd) {
            // Morning: ask previous night's sleep time if missing
            $yesterdayRecord = DailyRecord::firstOrCreate(
                ['user_id' => $userId, 'record_date' => $yesterday],
                ['sleep_prompt_dismissed' => false]
            );

            if (! $yesterdayRecord->sleep_time && ! $yesterdayRecord->sleep_prompt_dismissed) {
                $prompts[] = [
                    'id' => 'morning_sleep',
                    'type' => 'sleep',
                    'icon' => '😴',
                    'title' => 'Good Morning — Sleep time?',
                    'message' => 'What time did you sleep last night?',
                    'currentTime' => '23:00',
                    'date' => $yesterday,
                    'actionUrl' => route('daily.sleep'),
                    'dismissUrl' => route('daily.dismiss', ['type' => 'sleep', 'date' => $yesterday]),
                ];
            }

            if (! $dailyRecord->wake_up_time && ! $dailyRecord->wake_up_prompt_dismissed) {
                $prompts[] = [
                    'id' => 'wakeup',
                    'type' => 'wakeup',
                    'icon' => '☀️',
                    'title' => 'Good Morning!',
                    'message' => 'What time did you wake up today?',
                    'currentTime' => $now->format('H:i'),
                    'date' => $today,
                    'actionUrl' => route('daily.wakeup'),
                    'dismissUrl' => route('daily.dismiss', ['type' => 'wakeup']),
                ];
            }
        }

        $nStart = Setting::getVal('night_prompt_start', '20:50');
        $nEnd = Setting::getVal('night_prompt_end', '22:00');

        if ($currentTime >= $nStart && $currentTime <= $nEnd) {
            if (! $dailyRecord->sleep_time && ! $dailyRecord->sleep_prompt_dismissed) {
                $prompts[] = [
                    'id' => 'sleep',
                    'type' => 'sleep',
                    'icon' => '🌙',
                    'title' => 'Night reminder',
                    'message' => 'Record your sleep time and how the day went.',
                    'currentTime' => $now->format('H:i'),
                    'date' => $today,
                    'actionUrl' => route('daily.sleep'),
                    'dismissUrl' => route('daily.dismiss', ['type' => 'sleep']),
                ];
            }
        }

        $petrolEnabled = filter_var(Setting::getVal('weekly_petrol_reminder_enabled', true), FILTER_VALIDATE_BOOLEAN);
        if ($petrolEnabled) {
            $petrolDay = Setting::getVal('weekly_petrol_reminder_day', 'Sunday');
            if (strtolower($now->format('l')) === strtolower($petrolDay)) {
                $weekStart = $now->copy()->startOfWeek();
                $hasFuelThisWeek = FuelEntry::where('user_id', $userId)
                    ->where('date', '>=', $weekStart->toDateString())
                    ->exists();

                if (! $hasFuelThisWeek) {
                    $prompts[] = [
                        'id' => 'petrol',
                        'type' => 'petrol',
                        'icon' => '⛽',
                        'title' => 'Weekly Petrol Check',
                        'message' => 'Did you fill petrol this week?',
                        'actionUrl' => route('petrol.index'),
                    ];
                }
            }
        }

        return $prompts;
    }
}
