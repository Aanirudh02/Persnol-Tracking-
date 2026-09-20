<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Friend;
use App\Models\FriendSplit;
use App\Models\FuelEntry;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCalculationsAndPetrolTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'Admin'], ['display_name' => 'Admin']);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    public function test_expense_total_matches_sum_of_payment_methods_without_double_deducting_friend_contributions(): void
    {
        $user = $this->createAdminUser();
        $cat = ExpenseCategory::create(['name' => 'Snacks', 'user_id' => $user->id]);
        $friend = Friend::create(['user_id' => $user->id, 'name' => 'Sandeep']);

        // User paid ₹280 cash fully
        Expense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 280.00,
            'gst_amount' => 0.00,
            'date' => Carbon::today()->toDateString(),
            'description' => 'Team juice',
            'payment_method' => 'Cash',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);

        // Bill was ₹90: User paid ₹20, Friend paid ₹70
        $splitExpense = Expense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 20.00,
            'gst_amount' => 0.00,
            'date' => Carbon::today()->toDateString(),
            'description' => 'Bakery break',
            'payment_method' => 'Split',
            'paid_by' => 'Split Payment',
            'paid_by_type' => 'split',
            'split_with_friend_id' => $friend->id,
            'split_my_share' => 20.00,
            'split_friend_share' => 70.00,
        ]);

        FriendSplit::create([
            'user_id' => $user->id,
            'expense_id' => $splitExpense->id,
            'friend_id' => $friend->id,
            'description' => 'Bakery break split',
            'date' => Carbon::today()->toDateString(),
            'total_amount' => 90.00,
            'friend_share' => 70.00,
            'my_share' => 20.00,
            'paid_by_friend_amount' => 70.00,
            'paid_by_me_amount' => 20.00,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index'));
        $response->assertOk();

        // Owned total amount should be 280 + 20 = 300.00 (NOT 300 - 70 = 230)
        $response->assertViewHas('ownedTotalAmount', 300.00);
        $response->assertViewHas('totalFriendPaid', 70.00);

        $expensesByPayment = $response->viewData('expensesByPayment');
        $sumOwnedPayment = array_sum(array_column($expensesByPayment, 'owned'));
        $this->assertEquals(300.00, $sumOwnedPayment);
    }

    public function test_dashboard_monthly_total_and_breakdowns(): void
    {
        $user = $this->createAdminUser();
        $cat = ExpenseCategory::create(['name' => 'Snacks', 'user_id' => $user->id]);

        $now = Carbon::today();
        $startOfMonth = $now->copy()->startOfMonth()->toDateString();

        // 1. Base expense: ₹1620.00
        Expense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 1620.00,
            'date' => $startOfMonth,
            'description' => 'Base routine',
            'payment_method' => 'Cash',
            'paid_by' => 'Me',
            'is_voluntary' => false,
        ]);

        // 2. Voluntary expense: ₹7.50
        Expense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 7.50,
            'date' => $startOfMonth,
            'description' => 'To Friend',
            'payment_method' => 'UPI',
            'paid_by' => 'Me',
            'is_voluntary' => true,
        ]);

        // 3. Past month expense: ₹250.00 (June)
        Expense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 250.00,
            'date' => $now->copy()->subMonths(3)->toDateString(),
            'description' => 'Gift past',
            'payment_method' => 'UPI',
            'paid_by' => 'Me',
            'is_voluntary' => false,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        // Monthly Total Spend = 1620 + 7.50 = 1627.50
        $response->assertViewHas('monthlyExpensesTotal', 1627.50);
        $response->assertViewHas('monthlyBaseExpenses', 1620.00);
        $response->assertViewHas('monthlyVoluntaryExpenses', 7.50);

        $categoryBreakdown = $response->viewData('monthlyCategoryBreakdown');
        $this->assertEquals(1627.50, $categoryBreakdown->sum('total'));

        $paymentBreakdown = $response->viewData('monthlyPaymentBreakdown');
        $this->assertEquals(1627.50, $paymentBreakdown->sum('total'));
    }

    public function test_soft_deleted_and_archived_expense_resolves_without_404(): void
    {
        $user = $this->createAdminUser();
        $cat = ExpenseCategory::create(['name' => 'Petrol', 'user_id' => $user->id]);

        $expense = Expense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 300.00,
            'date' => Carbon::today()->toDateString(),
            'description' => 'Petrol fuel fill',
            'payment_method' => 'UPI',
            'paid_by' => 'Me',
            'is_archived' => true,
        ]);

        // 1. Archived expense resolves
        $response = $this->actingAs($user)->get(route('expenses.show', $expense->id));
        $response->assertOk();
        $response->assertSee('This expense is archived under Historical records');

        // 2. Soft-deleted expense also resolves without 404
        $expense->delete();
        $this->assertTrue($expense->trashed());

        $responseTrashed = $this->actingAs($user)->get(route('expenses.show', $expense->id));
        $responseTrashed->assertOk();
        $responseTrashed->assertSee('This expense was soft-deleted');

        // 3. Restore action restores it
        $restoreResp = $this->actingAs($user)->post(route('expenses.restore', $expense->id));
        $restoreResp->assertRedirect();
        $expense->refresh();
        $this->assertFalse($expense->trashed());
        $this->assertFalse($expense->is_archived);
    }

    public function test_breakdown_hub_and_quick_petrol_linking(): void
    {
        $user = $this->createAdminUser();
        $vehicle = Vehicle::create([
            'user_id' => $user->id,
            'name' => 'TVS Pep+',
            'make' => 'TVS',
            'model' => 'Scooty Pep+',
            'default_mileage_kmpl' => 45,
            'fuel_type' => 'Petrol',
            'is_default' => true,
        ]);
        $cat = ExpenseCategory::create(['name' => 'Petrol', 'user_id' => $user->id]);

        $linkedExpense = Expense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 50.00,
            'date' => Carbon::today()->toDateString(),
            'description' => 'Petrol fill',
            'payment_method' => 'Cash',
            'paid_by' => 'Me',
        ]);

        // Fuel entry 1: Linked
        FuelEntry::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'expense_id' => $linkedExpense->id,
            'date' => Carbon::today()->toDateString(),
            'amount' => 50.00,
            'litres' => 0.46,
            'price_per_litre' => 108.70,
            'payment_method' => 'Cash',
        ]);

        // Fuel entry 2: Unassociated
        $unassociatedFuel = FuelEntry::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'expense_id' => null,
            'date' => Carbon::today()->toDateString(),
            'amount' => 100.00,
            'litres' => 0.91,
            'price_per_litre' => 109.89,
            'payment_method' => 'Cash',
        ]);

        // Check Breakdown page
        $response = $this->actingAs($user)->get(route('expenses.breakdown'));
        $response->assertOk();
        $response->assertViewHas('associatedTotal', 50.00);
        $response->assertViewHas('unassociatedTotal', 100.00);

        // 1-Click Link Expense
        $linkResponse = $this->actingAs($user)->post(route('petrol.link-expense', $unassociatedFuel->id));
        $linkResponse->assertRedirect();

        $unassociatedFuel->refresh();
        $this->assertNotNull($unassociatedFuel->expense_id);
        $this->assertDatabaseHas('expenses', [
            'id' => $unassociatedFuel->expense_id,
            'amount' => 100.00,
            'payment_method' => 'Cash',
        ]);
    }
}
