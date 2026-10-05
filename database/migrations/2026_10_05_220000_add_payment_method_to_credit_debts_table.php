<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_debts', function (Blueprint $table) {
            if (! Schema::hasColumn('credit_debts', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('amount_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('credit_debts', function (Blueprint $table) {
            if (Schema::hasColumn('credit_debts', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};
