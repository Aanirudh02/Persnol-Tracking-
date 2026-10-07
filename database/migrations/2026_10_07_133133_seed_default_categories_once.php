<?php

use App\Models\PersonalExpenseCategory;
use App\Services\OptionsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * One-time versions of data that GET pages used to create on every visit
     * (Expenses → create, Personal Expenses, Petrol), so viewing a page no longer
     * writes rows or brings back things the user deleted.
     */
    public function up(): void
    {
        $now = now();

        // Shared "Snacks" expense category (was created by the Add Expense page)
        $hasSnacks = DB::table('expense_categories')->whereRaw('LOWER(name) = ?', ['snacks'])->exists();
        if (! $hasSnacks) {
            DB::table('expense_categories')->insert([
                'user_id' => null,
                'name' => 'Snacks',
                'icon' => 'cookie',
                'color' => '#eab308',
                'is_archived' => false,
                'is_voluntary' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (DB::table('users')->pluck('id') as $userId) {
            // Starter personal categories + "Me" (was created by the Personal Expenses page)
            PersonalExpenseCategory::ensureDefaultsFor((int) $userId);

            // Expense classifications (were created by the Classification / Settings pages)
            app(OptionsService::class)->ensureClassifications((int) $userId);

            // Petrol rows without a vehicle (the Petrol page created "TVS Pep+" and reassigned them on every visit)
            if (! DB::table('fuel_entries')->where('user_id', $userId)->whereNull('vehicle_id')->exists()) {
                continue;
            }

            $vehicleId = DB::table('vehicles')->where('user_id', $userId)->where('is_default', true)->value('id')
                ?? DB::table('vehicles')->where('user_id', $userId)->orderBy('id')->value('id');

            if (! $vehicleId) {
                $vehicleId = DB::table('vehicles')->insertGetId([
                    'user_id' => $userId,
                    'name' => 'TVS Pep+',
                    'make' => 'TVS',
                    'model' => 'Scooty Pep+',
                    'default_mileage_kmpl' => 45,
                    'fuel_type' => 'Petrol',
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('fuel_entries')->where('user_id', $userId)->whereNull('vehicle_id')->update(['vehicle_id' => $vehicleId]);
        }
    }

    public function down(): void
    {
        // Data seeding only; nothing to reverse.
    }
};
