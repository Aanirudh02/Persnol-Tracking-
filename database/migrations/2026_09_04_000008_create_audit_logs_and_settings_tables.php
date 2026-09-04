<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('module'); // payment, expense, income, fuel
            $table->unsignedBigInteger('record_id');
            $table->string('action'); // created, updated, deleted, reconciled
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            $table->index(['module', 'record_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json
            $table->string('group')->default('general'); // general, edit_windows, prompts, petrol, reminders
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // wakeup, sleep, petrol, payment, general
            $table->string('title');
            $table->text('message');
            $table->string('action_url')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
    }
};
