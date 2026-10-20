<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GPayAudit;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\PaymentWallet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GPayAuditorTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser(): User
    {
        $role = Role::query()->firstOrCreate(['name' => 'Admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        PaymentWallet::query()->create([
            'user_id' => $user->id,
            'payment_method' => 'UPI',
            'opening_balance' => 500,
            'opening_as_of' => '2026-10-01',
            'is_enabled' => true,
        ]);

        return $user;
    }

    public function test_gpay_auditor_page_loads_for_authenticated_user(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)->get(route('finance.gpay-auditor.index'));
        $response->assertOk();
        $response->assertSee('GPay Screenshot Auditor');
        $response->assertSee('Website UPI Balance');
    }

    public function test_auditor_detects_fake_expense_and_green_credit_income(): void
    {
        $user = $this->createTestUser();
        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Snacks', 'is_archived' => false]);
        $dailyRecord = DailyRecord::query()->create(['user_id' => $user->id, 'record_date' => '2026-10-15']);

        // Website has Spotify (legit) and Coffee Meet (fake/cash entered as UPI)
        $spotify = Expense::query()->create([
            'user_id' => $user->id,
            'daily_record_id' => $dailyRecord->id,
            'category_id' => $category->id,
            'amount' => 69.00,
            'gst_amount' => 0.0,
            'date' => '2026-10-15',
            'description' => 'Spotify Recharge',
            'payment_method' => 'UPI',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);

        $coffee = Expense::query()->create([
            'user_id' => $user->id,
            'daily_record_id' => $dailyRecord->id,
            'category_id' => $category->id,
            'amount' => 50.00,
            'gst_amount' => 0.0,
            'date' => '2026-10-15',
            'description' => 'Coffe meet Break -2',
            'payment_method' => 'UPI',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);

        // Screenshot text only has Spotify and Preethi Nathan green credit
        $rawText = "SPOTIFY INDIA PVT LTD 15 October ₹69\nPreethi Nathan 14 October + ₹70";

        $response = $this->actingAs($user)->post(route('finance.gpay-auditor.audit'), [
            'raw_text' => $rawText,
            'month_year' => '2026-10',
            'gpay_balance' => 824.00,
        ]);

        $response->assertRedirect();
        $audit = GPayAudit::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertCount(2, $audit->extracted_items);
        $this->assertEquals(1, $audit->audit_results['summary']['fake_expenses_count']);
        $this->assertEquals(1, $audit->audit_results['summary']['missing_incomes_count']);
        $this->assertEquals(1, $audit->audit_results['summary']['matched_count']);

        // Coffee should be flagged as unbacked
        $this->assertEquals('Coffe meet Break -2', $audit->audit_results['fake_expenses'][0]['website']['title']);
        $this->assertEquals(50.0, $audit->audit_results['fake_expenses'][0]['website']['amount']);

        // Preethi Nathan should be flagged as missing income
        $this->assertEquals('Preethi Nathan', $audit->audit_results['missing_incomes'][0]['gpay']['merchant']);
        $this->assertEquals(70.0, $audit->audit_results['missing_incomes'][0]['gpay']['amount']);
    }

    public function test_quick_action_converts_fake_expense_to_cash(): void
    {
        $user = $this->createTestUser();
        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Snacks', 'is_archived' => false]);
        $dailyRecord = DailyRecord::query()->create(['user_id' => $user->id, 'record_date' => '2026-10-15']);

        $expense = Expense::query()->create([
            'user_id' => $user->id,
            'daily_record_id' => $dailyRecord->id,
            'category_id' => $category->id,
            'amount' => 50.00,
            'gst_amount' => 0.0,
            'date' => '2026-10-15',
            'description' => 'Coffe meet Break -2',
            'payment_method' => 'UPI',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);

        $response = $this->actingAs($user)->post(route('finance.gpay-auditor.action'), [
            'action' => 'convert_to_cash',
            'expense_id' => $expense->id,
        ]);

        $response->assertRedirect();
        $expense->refresh();
        $this->assertEquals('Cash', $expense->payment_method);
        $this->assertStringContainsString('Switched from UPI to Cash', (string) $expense->notes);
    }

    public function test_quick_action_adds_missing_income_and_expense(): void
    {
        $user = $this->createTestUser();
        IncomeCategory::query()->create(['user_id' => $user->id, 'name' => 'General']);
        ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'General', 'is_archived' => false]);

        // Add income from green credit
        $responseIncome = $this->actingAs($user)->post(route('finance.gpay-auditor.action'), [
            'action' => 'add_income',
            'merchant' => 'Preethi Nathan',
            'amount' => 70.00,
            'date' => '2026-10-14',
        ]);
        $responseIncome->assertRedirect();

        $income = Income::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(70.0, (float) $income->amount);
        $this->assertEquals('Preethi Nathan', $income->source);
        $this->assertEquals('UPI', $income->payment_method);

        // Add expense from GPay debit
        $responseExpense = $this->actingAs($user)->post(route('finance.gpay-auditor.action'), [
            'action' => 'add_expense',
            'merchant' => 'deepaviyash',
            'amount' => 120.00,
            'date' => '2026-10-12',
        ]);
        $responseExpense->assertRedirect();

        $expense = Expense::query()->where('user_id', $user->id)->where('description', 'deepaviyash')->firstOrFail();
        $this->assertEquals(120.0, (float) $expense->amount);
        $this->assertEquals('UPI', $expense->payment_method);
    }
}
