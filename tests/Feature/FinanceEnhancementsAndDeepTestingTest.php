<?php

namespace Tests\Feature;

use App\Models\CreditDebt;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Friend;
use App\Models\FuelEntry;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\IncomeExpenseTally;
use App\Models\Role;
use App\Models\Saving;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinanceEnhancementsAndDeepTestingTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'Admin'], ['display_name' => 'Administrator']);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_credit_can_be_recorded_as_expense(): void
    {
        $user = $this->createAdminUser();
        $friend = Friend::create([
            'user_id' => $user->id,
            'name' => 'Kiran',
            'role' => 'Friend',
        ]);
        $cat = ExpenseCategory::create([
            'user_id' => $user->id,
            'name' => 'Repayment',
            'icon' => 'banknotes',
            'color' => '#10b981',
        ]);

        $credit = CreditDebt::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'type' => 'credit',
            'amount' => 200,
            'amount_paid' => 0,
            'status' => 'yet_to_pay',
            'date' => '2026-09-30',
            'description' => 'Borrowed for dinner',
            'source' => 'manual',
        ]);

        $response = $this->actingAs($user)->post(route('credits.record-as-expense', $credit), [
            'amount' => 200,
            'date' => '2026-09-30',
            'payment_method' => 'UPI',
        ]);

        $response->assertRedirect();
        $credit->refresh();
        $this->assertSame('fully_paid', $credit->status);
        $this->assertEquals(200.0, (float) $credit->amount_paid);
        $this->assertNotNull($credit->linked_expense_id);

        $expense = Expense::find($credit->linked_expense_id);
        $this->assertNotNull($expense);
        $this->assertEquals(200.0, (float) $expense->amount);
        $this->assertSame('UPI', $expense->payment_method);
    }

    public function test_debit_can_be_recorded_as_income(): void
    {
        $user = $this->createAdminUser();
        $friend = Friend::create([
            'user_id' => $user->id,
            'name' => 'Varun',
            'role' => 'Friend',
        ]);
        $cat = IncomeCategory::create([
            'user_id' => $user->id,
            'name' => 'Debt Returned',
            'icon' => 'currency-rupee',
            'color' => '#6366f1',
        ]);

        $debit = CreditDebt::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'type' => 'debt',
            'amount' => 350,
            'amount_paid' => 0,
            'status' => 'yet_to_pay',
            'date' => '2026-09-30',
            'description' => 'Lent for cab',
            'source' => 'manual',
        ]);

        $response = $this->actingAs($user)->post(route('credits.record-as-income', $debit), [
            'amount' => 350,
            'date' => '2026-09-30',
            'payment_method' => 'Cash',
        ]);

        $response->assertRedirect();
        $debit->refresh();
        $this->assertSame('fully_paid', $debit->status);
        $this->assertEquals(350.0, (float) $debit->amount_paid);
        $this->assertNotNull($debit->linked_income_id);

        $income = Income::find($debit->linked_income_id);
        $this->assertNotNull($income);
        $this->assertEquals(350.0, (float) $income->amount);
        $this->assertSame('Cash', $income->payment_method);
    }

    public function test_credit_can_be_settled_with_discount_or_forgiveness(): void
    {
        $user = $this->createAdminUser();
        $friend = Friend::create([
            'user_id' => $user->id,
            'name' => 'Ravi',
            'role' => 'Friend',
        ]);

        $credit = CreditDebt::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'type' => 'credit',
            'amount' => 200,
            'amount_paid' => 0,
            'status' => 'yet_to_pay',
            'date' => '2026-09-30',
            'description' => 'Dinner split',
            'source' => 'manual',
        ]);

        $response = $this->actingAs($user)->post(route('credits.settle-discounted', $credit), [
            'settled_amount' => 150,
            'discount_amount' => 50,
            'paid_on' => '2026-09-30',
            'notes' => 'Settled mutually for ₹150',
        ]);

        $response->assertRedirect();
        $credit->refresh();
        $this->assertSame('fully_paid', $credit->status);
        $this->assertTrue((bool) $credit->is_settled_discounted);
        $this->assertEquals(150.0, (float) $credit->amount_paid);
        $this->assertEquals(50.0, (float) $credit->settled_discount_amount);
        $this->assertStringContainsString('Settlement Note', $credit->notes);
    }

    public function test_savings_module_full_lifecycle(): void
    {
        $user = $this->createAdminUser();

        // 1. Create a saving record
        $response = $this->actingAs($user)->post(route('savings.store'), [
            'amount' => 10000,
            'source' => 'Bank Transfer',
            'goal_or_category' => 'Emergency Fund',
            'saved_date' => '2026-09-30',
            'notes' => 'Initial allocation',
        ]);
        $response->assertRedirect();

        $saving = Saving::where('user_id', $user->id)->first();
        $this->assertNotNull($saving);
        $this->assertEquals(10000.0, (float) $saving->amount);

        // 2. Withdraw from saving fund
        $response = $this->actingAs($user)->post(route('savings.withdraw', $saving), [
            'withdraw_amount' => 2500,
            'notes' => 'Medical test',
        ]);
        $response->assertRedirect();
        $saving->refresh();
        $this->assertEquals(2500.0, (float) $saving->withdrawn_amount);
        $this->assertEquals(7500.0, $saving->netAvailable());

        // 3. View savings page
        $response = $this->actingAs($user)->get(route('savings.index'));
        $response->assertOk();
        $response->assertSee('Emergency Fund');
    }

    public function test_income_expense_tallying(): void
    {
        $user = $this->createAdminUser();
        $cat = ExpenseCategory::create([
            'user_id' => $user->id,
            'name' => 'Groceries',
            'icon' => 'shopping-cart',
            'color' => '#10b981',
        ]);
        $incomeCat = IncomeCategory::create([
            'user_id' => $user->id,
            'name' => 'Monthly Salary',
            'icon' => 'briefcase',
            'color' => '#6366f1',
        ]);

        $income = Income::create([
            'user_id' => $user->id,
            'category_id' => $incomeCat->id,
            'amount' => 20000,
            'source' => 'Salary',
            'date' => '2026-09-01',
            'description' => 'September Pay',
            'payment_method' => 'Bank',
        ]);

        $expense = Expense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 3000,
            'date' => '2026-09-05',
            'description' => 'Supermarket items',
            'payment_method' => 'UPI',
        ]);

        // Tally expense against income
        $response = $this->actingAs($user)->post(route('income.tally', $income), [
            'expense_id' => $expense->id,
            'allocated_amount' => 3000,
            'notes' => 'Funded by salary',
        ]);
        $response->assertRedirect();

        $this->assertEquals(3000.0, $income->talliedAmount());
        $this->assertEquals(17000.0, $income->untalliedAmount());

        $tally = IncomeExpenseTally::where('income_id', $income->id)->first();
        $this->assertNotNull($tally);

        // Untally expense
        $response = $this->actingAs($user)->delete(route('income.untally', [$income, $tally]));
        $response->assertRedirect();
        $this->assertEquals(0.0, $income->talliedAmount());
    }

    public function test_daily_balance_manual_adjustment(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('daily-balances.update'), [
            'date' => now()->toDateString(),
            'manual_adjustment' => 250,
            'notes' => 'Found extra cash in wallet',
        ]);
        $response->assertRedirect();

        $response = $this->actingAs($user)->get(route('daily-balances.index'));
        $response->assertOk();
    }

    public function test_dashboard_chart_data_endpoint(): void
    {
        $user = $this->createAdminUser();

        foreach (['7d', 'week', 'month', 'year'] as $range) {
            $response = $this->actingAs($user)->get(route('dashboard.chart-data', ['range' => $range]));
            $response->assertOk();
            $response->assertJsonStructure(['labels', 'data', 'range', 'total']);
        }
    }

    public function test_petrol_statement_page(): void
    {
        $user = $this->createAdminUser();
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'name' => 'Activa 6G',
            'type' => 'scooter',
        ]);

        FuelEntry::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'date' => '2026-09-15',
            'amount' => 500,
            'litres' => 4.8,
            'price_per_litre' => 104.16,
            'odometer_reading' => 12500,
            'payment_method' => 'UPI',
        ]);

        $response = $this->actingAs($user)->get(route('petrol.statement', [
            'from_date' => '2026-09-01',
            'to_date' => '2026-09-30',
        ]));
        $response->assertOk();
        $response->assertSee('Petrol &amp; Fuel Statement', false);
    }

    public function test_cloudinary_tester_endpoint(): void
    {
        $user = $this->createAdminUser();

        Http::fake([
            'api.cloudinary.com/*' => Http::response([
                'secure_url' => 'https://res.cloudinary.com/dh5wd8etl/image/upload/v1/test_connection/test.png',
                'public_id' => 'test_connection/test',
            ], 200),
        ]);

        config([
            'services.cloudinary.cloud_name' => 'test_cloud',
            'services.cloudinary.api_key' => '123456',
            'services.cloudinary.api_secret' => 'abcdef',
        ]);

        $response = $this->actingAs($user)->post(route('settings.test-cloudinary'));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
