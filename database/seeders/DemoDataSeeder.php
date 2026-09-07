<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FoodCategory;
use App\Models\FoodEntry;
use App\Models\Friend;
use App\Models\FriendTransaction;
use App\Models\FuelEntry;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\Mistake;
use App\Models\MistakeCategory;
use App\Models\Note;
use App\Models\Payment;
use App\Models\ScooterTrip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', env('ADMIN_EMAIL', 'aanirudhch@gmail.com'))->first();
        if (! $user) {
            return;
        }

        // Friends
        $rahul = Friend::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Rahul'],
            ['phone' => '9876543210', 'email' => 'rahul@example.com', 'notes' => 'College friend']
        );
        $priya = Friend::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Priya'],
            ['phone' => '9876543211', 'email' => 'priya@example.com', 'notes' => 'Project partner']
        );
        $arun = Friend::updateOrCreate(
            ['user_id' => $user->id, 'name' => 'Arun'],
            ['phone' => '9876543212', 'email' => 'arun@example.com', 'notes' => 'Gym buddy']
        );

        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        // Yesterday Daily Record (with sleep time 23:38)
        $yesterdayRecord = DailyRecord::updateOrCreate(
            ['user_id' => $user->id, 'record_date' => $yesterday],
            [
                'wake_up_time' => '07:15:00',
                'sleep_time' => '23:38:00',
                'sleep_quality' => 4,
                'day_rating' => 4,
                'sleep_notes' => 'Slept peacefully after reading',
                'day_summary' => 'Good productive Thursday',
            ]
        );

        // Today Daily Record (Wake up 07:42 AM)
        $todayRecord = DailyRecord::updateOrCreate(
            ['user_id' => $user->id, 'record_date' => $today],
            [
                'wake_up_time' => '07:42:00',
                'sleep_time' => null, // Not slept yet
                'sleep_quality' => 5,
                'day_rating' => 5,
                'sleep_duration_hours' => 8.07, // from 23:38 to 07:42
                'day_summary' => 'Active Friday',
            ]
        );

        // Categories map
        $expCatFood = ExpenseCategory::where('name', 'Food')->first();
        $expCatSnacks = ExpenseCategory::where('name', 'Snacks')->first();
        $expCatPetrol = ExpenseCategory::where('name', 'Petrol')->first();
        $expCatCollege = ExpenseCategory::where('name', 'College')->first();

        $incCatPocket = IncomeCategory::where('name', 'Pocket Money')->first();

        $foodCatSnacks = FoodCategory::where('name', 'Snacks')->first();
        $foodCatLunch = FoodCategory::where('name', 'Lunch')->first();
        $foodCatTea = FoodCategory::where('name', 'Tea / Coffee')->first();

        $actCatCollege = ActivityCategory::where('name', 'College')->first();
        $actCatStudy = ActivityCategory::where('name', 'Study')->first();
        $actCatGym = ActivityCategory::where('name', 'Gym')->first();
        $actCatProject = ActivityCategory::where('name', 'Project Work')->first();
        $actCatFriend = ActivityCategory::where('name', 'Meeting Friend')->first();

        $mistCatCollege = MistakeCategory::where('name', 'College / Academics')->first();

        // Income today: ₹1,500 Pocket Money
        Income::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today, 'amount' => 1500],
            [
                'daily_record_id' => $todayRecord->id,
                'category_id' => $incCatPocket?->id,
                'source' => 'Pocket Money',
                'time' => '09:00:00',
                'payment_method' => 'UPI',
                'description' => 'Monthly pocket allowance received',
            ]
        );

        // Expenses today: totaling ₹430 (120 lunch + 15 tea + 45 snacks + 250 materials)
        Expense::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today, 'description' => 'College Lunch'],
            [
                'daily_record_id' => $todayRecord->id,
                'category_id' => $expCatFood?->id,
                'amount' => 120.00,
                'time' => '13:15:00',
                'payment_method' => 'UPI',
                'paid_by' => 'Me',
            ]
        );
        Expense::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today, 'description' => 'Evening Tea & Puffs'],
            [
                'daily_record_id' => $todayRecord->id,
                'category_id' => $expCatSnacks?->id,
                'amount' => 35.00,
                'time' => '17:30:00',
                'payment_method' => 'Cash',
                'paid_by' => 'Me',
            ]
        );
        Expense::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today, 'description' => 'Stationery & Printouts'],
            [
                'daily_record_id' => $todayRecord->id,
                'category_id' => $expCatCollege?->id,
                'amount' => 275.00,
                'time' => '15:30:00',
                'payment_method' => 'UPI',
                'paid_by' => 'Me',
            ]
        );

        // Food & Snacks entries
        FoodEntry::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today, 'item_name' => 'Tea'],
            [
                'daily_record_id' => $todayRecord->id,
                'category_id' => $foodCatTea?->id,
                'is_snack' => true,
                'quantity' => 1,
                'amount' => 15.00,
                'time' => '16:30:00',
                'location' => 'Campus Canteen',
            ]
        );
        FoodEntry::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today, 'item_name' => 'Veg Puffs'],
            [
                'daily_record_id' => $todayRecord->id,
                'category_id' => $foodCatSnacks?->id,
                'is_snack' => true,
                'quantity' => 1,
                'amount' => 20.00,
                'time' => '17:30:00',
                'location' => 'Bakery',
            ]
        );
        FoodEntry::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today, 'item_name' => 'Biryani Meal'],
            [
                'daily_record_id' => $todayRecord->id,
                'category_id' => $foodCatLunch?->id,
                'is_snack' => false,
                'quantity' => 1,
                'amount' => 120.00,
                'time' => '13:15:00',
                'location' => 'Food Street',
            ]
        );

        // Activities today (5 activities)
        $acts = [
            ['title' => 'Morning Gym Workout', 'category_id' => $actCatGym?->id, 'start_time' => '06:15:00', 'end_time' => '07:15:00', 'duration_minutes' => 60, 'location' => 'FitGym'],
            ['title' => 'College Lectures', 'category_id' => $actCatCollege?->id, 'start_time' => '09:00:00', 'end_time' => '13:00:00', 'duration_minutes' => 240, 'location' => 'CIT Campus'],
            ['title' => 'Library Study Session', 'category_id' => $actCatStudy?->id, 'start_time' => '14:00:00', 'end_time' => '16:00:00', 'duration_minutes' => 120, 'location' => 'Central Library'],
            ['title' => 'Project Coding & Review', 'category_id' => $actCatProject?->id, 'start_time' => '16:30:00', 'end_time' => '18:00:00', 'duration_minutes' => 90, 'location' => 'Lab 3'],
            ['title' => 'Coffee with Rahul', 'category_id' => $actCatFriend?->id, 'start_time' => '18:30:00', 'end_time' => '19:30:00', 'duration_minutes' => 60, 'location' => 'Cafe Coffee Day'],
        ];
        foreach ($acts as $a) {
            Activity::updateOrCreate(
                ['user_id' => $user->id, 'date' => $today, 'title' => $a['title']],
                array_merge($a, ['daily_record_id' => $todayRecord->id])
            );
        }

        // Scooter Trips (3 trips)
        $trips = [
            [
                'title' => 'Home to CIT Campus',
                'start_time' => '08:15:00',
                'end_time' => '08:45:00',
                'start_latitude' => 11.0168,
                'start_longitude' => 76.9558,
                'start_address' => 'Gandhipuram, Coimbatore',
                'end_latitude' => 11.0286,
                'end_longitude' => 77.0275,
                'end_address' => 'CIT Campus, Civil Aerodrome Post',
                'distance_km' => 8.4,
                'duration_minutes' => 30,
                'odometer_reading' => 12410,
                'status' => 'completed',
            ],
            [
                'title' => 'College to Library & Bakery',
                'start_time' => '16:00:00',
                'end_time' => '16:15:00',
                'start_latitude' => 11.0286,
                'start_longitude' => 77.0275,
                'start_address' => 'CIT Campus',
                'end_latitude' => 11.0180,
                'end_longitude' => 76.9850,
                'end_address' => 'Peelamedu, Coimbatore',
                'distance_km' => 4.2,
                'duration_minutes' => 15,
                'odometer_reading' => 12414,
                'status' => 'completed',
            ],
            [
                'title' => 'Cafe to Home',
                'start_time' => '19:40:00',
                'end_time' => '20:10:00',
                'start_latitude' => 11.0180,
                'start_longitude' => 76.9850,
                'start_address' => 'Avinashi Road',
                'end_latitude' => 11.0168,
                'end_longitude' => 76.9558,
                'end_address' => 'Home, Gandhipuram',
                'distance_km' => 5.1,
                'duration_minutes' => 30,
                'odometer_reading' => 12430,
                'status' => 'completed',
            ],
        ];
        foreach ($trips as $t) {
            ScooterTrip::updateOrCreate(
                ['user_id' => $user->id, 'date' => $today, 'title' => $t['title']],
                array_merge($t, ['daily_record_id' => $todayRecord->id])
            );
        }

        // Petrol record: ₹500 (4.85 L @ 103.09/L)
        FuelEntry::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today, 'amount' => 500.00],
            [
                'daily_record_id' => $todayRecord->id,
                'time' => '08:30:00',
                'litres' => 4.85,
                'price_per_litre' => 103.09,
                'odometer' => 12415,
                'petrol_station' => 'Indian Oil Peelamedu',
                'payment_method' => 'UPI',
                'notes' => 'Tank filled for the week',
            ]
        );

        // Mistakes: 1 mistake
        Mistake::updateOrCreate(
            ['user_id' => $user->id, 'title' => 'Forgot to submit lab assignment on time'],
            [
                'daily_record_id' => $todayRecord->id,
                'category_id' => $mistCatCollege?->id,
                'date' => $today,
                'time' => '11:00:00',
                'what_happened' => 'Remembered only after reaching college that deadline was 10 AM.',
                'why_happened' => 'Did not add the reminder to my daily task list the previous evening.',
                'what_should_have_done' => 'Record deadlines immediately when announced.',
                'lesson_learned' => 'Always double check assignment submission portals before bed.',
                'prevention_plan' => 'Set phone calendar alert 24 hours prior to deadline.',
                'severity' => 'High',
                'status' => 'Resolved',
                'tags' => 'college,deadline,time-management',
            ]
        );

        // Payment: ₹500 to Rahul (UPI, Pending)
        Payment::updateOrCreate(
            ['user_id' => $user->id, 'paid_to' => 'Rahul', 'amount' => 500.00],
            [
                'date' => $today,
                'time' => '19:00:00',
                'paid_by' => 'Me',
                'purpose' => 'Shared dinner & snacks',
                'category' => 'Food',
                'payment_method' => 'UPI',
                'reference' => 'UPI/REF/984210',
                'status' => 'Pending',
                'notes' => 'Awaiting confirmation from Rahul',
            ]
        );

        // Friend Transaction: Shared snacks (Total ₹120, My share ₹60, Rahul share ₹60)
        FriendTransaction::updateOrCreate(
            ['user_id' => $user->id, 'friend_id' => $rahul->id, 'description' => 'Evening snacks at cafe'],
            [
                'type' => 'shared_expense',
                'total_amount' => 120.00,
                'my_share' => 60.00,
                'friend_share' => 60.00,
                'date' => $today,
                'payment_method' => 'UPI',
                'is_settled' => false,
            ]
        );

        // Notes
        Note::updateOrCreate(
            ['user_id' => $user->id, 'title' => 'Final Year Project Architecture Ideas'],
            [
                'content' => "Key ideas for architecture:\n1. Modular design pattern\n2. Geolocation caching\n3. Daily analytics charts with Chart.js\n4. Dark/light mode theme support",
                'date' => $today,
                'tags' => 'project,ideas,architecture',
                'is_pinned' => true,
            ]
        );
    }
}
