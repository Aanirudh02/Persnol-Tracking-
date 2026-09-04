<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookup_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50);
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'type', 'name']);
            $table->index(['type', 'is_active']);
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('fuel_type')->default('Petrol');
            $table->decimal('default_mileage_kmpl', 8, 2)->default(40);
            $table->decimal('actual_mileage_kmpl', 8, 2)->nullable();
            $table->decimal('tank_capacity_litres', 8, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->unsignedInteger('odometer_start')->nullable();
            $table->boolean('is_default')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method');
            $table->boolean('is_enabled')->default(false);
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->date('opening_as_of')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'payment_method']);
        });

        Schema::create('credit_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debt']);
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('yet_to_pay');
            $table->date('date');
            $table->string('location')->nullable();
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('source')->default('manual');
            $table->foreignId('food_entry_id')->nullable()->constrained('food_entries')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->foreignId('friend_transaction_id')->nullable()->constrained('friend_transactions')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamp('fully_paid_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type', 'status']);
        });

        Schema::create('credit_debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_debt_id')->constrained('credit_debts')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('color');
            $table->boolean('is_voluntary')->default(false)->after('is_archived');
        });

        Schema::table('income_categories', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('color');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('category_id')->constrained('expenses')->nullOnDelete();
            $table->string('paid_by_type')->default('me')->after('paid_by');
            $table->foreignId('paid_by_friend_id')->nullable()->after('paid_by_type')->constrained('friends')->nullOnDelete();
            $table->foreignId('split_with_friend_id')->nullable()->after('friend_person')->constrained('friends')->nullOnDelete();
            $table->decimal('split_my_share', 12, 2)->nullable()->after('split_with_friend_id');
            $table->decimal('split_friend_share', 12, 2)->nullable()->after('split_my_share');
            $table->foreignId('friend_transaction_id')->nullable()->after('split_friend_share')->constrained('friend_transactions')->nullOnDelete();
            $table->boolean('is_voluntary')->default(false)->after('is_locked');
        });

        Schema::table('friends', function (Blueprint $table) {
            $table->string('role')->default('Friend')->after('name');
        });

        Schema::table('friend_transactions', function (Blueprint $table) {
            $table->boolean('paid_by_me')->default(true)->after('type');
        });

        Schema::table('scooter_trips', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('user_id')->constrained('vehicles')->nullOnDelete();
            $table->decimal('one_way_km', 10, 2)->nullable()->after('distance_km');
            $table->string('distance_source')->nullable()->after('one_way_km');
            $table->decimal('estimated_litres', 10, 3)->nullable()->after('distance_source');
            $table->decimal('estimated_fuel_cost', 12, 2)->nullable()->after('estimated_litres');
            $table->string('purpose')->nullable()->after('estimated_fuel_cost');
        });

        Schema::table('fuel_entries', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('user_id')->constrained('vehicles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('fuel_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
        });

        Schema::table('scooter_trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropColumn(['one_way_km', 'distance_source', 'estimated_litres', 'estimated_fuel_cost', 'purpose']);
        });

        Schema::table('friend_transactions', function (Blueprint $table) {
            $table->dropColumn('paid_by_me');
        });

        Schema::table('friends', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('paid_by_friend_id');
            $table->dropConstrainedForeignId('split_with_friend_id');
            $table->dropConstrainedForeignId('friend_transaction_id');
            $table->dropColumn(['paid_by_type', 'split_my_share', 'split_friend_share', 'is_voluntary']);
        });

        Schema::table('income_categories', function (Blueprint $table) {
            $table->dropColumn('is_archived');
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn(['is_archived', 'is_voluntary']);
        });

        Schema::dropIfExists('credit_debt_payments');
        Schema::dropIfExists('credit_debts');
        Schema::dropIfExists('payment_wallets');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('lookup_options');
    }
};
