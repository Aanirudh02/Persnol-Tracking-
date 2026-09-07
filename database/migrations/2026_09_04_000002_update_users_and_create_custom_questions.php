<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('profile_photo')->nullable()->after('password');
            $table->string('timezone')->default('Asia/Kolkata')->after('profile_photo');
            $table->string('currency')->default('INR')->after('timezone');
            $table->boolean('is_active')->default(true)->after('currency');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->json('preferences')->nullable()->after('last_login_at');
        });

        Schema::create('custom_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->string('type')->default('text'); // text, number, date
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('custom_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_question_id')->constrained('custom_questions')->cascadeOnDelete();
            $table->text('answer_encrypted');
            $table->timestamps();
            $table->unique(['user_id', 'custom_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_answers');
        Schema::dropIfExists('custom_questions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'profile_photo', 'timezone', 'currency', 'is_active', 'last_login_at', 'preferences',
            ]);
        });
    }
};
