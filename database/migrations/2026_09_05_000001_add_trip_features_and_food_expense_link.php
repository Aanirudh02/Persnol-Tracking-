<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Scooter trip: Add to-and-fro toggle and stops JSON column
        Schema::table('scooter_trips', function (Blueprint $table) {
            $table->boolean('to_and_fro')->default(false)->after('end_address');
            $table->json('stops')->nullable()->after('to_and_fro'); // [{label, lat, lng}]
            $table->string('from_label')->nullable()->after('stops');   // Friendly source name
            $table->string('to_label')->nullable()->after('from_label'); // Friendly destination name
        });

        // Food entries: Link to expense record
        Schema::table('food_entries', function (Blueprint $table) {
            $table->foreignId('expense_id')
                ->nullable()
                ->after('category_id')
                ->constrained('expenses')
                ->nullOnDelete();
            $table->boolean('auto_create_expense')->default(false)->after('expense_id');
        });
    }

    public function down(): void
    {
        Schema::table('food_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
            $table->dropColumn('auto_create_expense');
        });

        Schema::table('scooter_trips', function (Blueprint $table) {
            $table->dropColumn(['to_and_fro', 'stops', 'from_label', 'to_label']);
        });
    }
};
