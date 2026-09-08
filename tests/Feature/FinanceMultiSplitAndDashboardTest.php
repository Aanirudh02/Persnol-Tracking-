<?php

namespace Tests\Feature;

use App\Models\CreditDebt;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Friend;
use App\Models\FriendSplit;
use App\Models\LookupOption;
use App\Models\PaymentWallet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceMultiSplitAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Cash', 'UPI', 'Card', 'Bank Transfer', 'Other'] as $index => $method) {
            LookupOption::query()->create([
                'type' => 'payment_method',
                'name' => $method,
                'sort_order' => $index,
                'is_system' => true,
                'is_active' => true,
            ]);
        }
    }

    public function test_multi_friend_split_with_payment_breakdown_matching_user_out_of_pocket(): void
    {
        $user = $this->createUser();
        $sandeep = Friend::query()->create(['user_id' => $user->id, 'name' => 'Sandeep', 'role' => 'Friend']);
        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Snacks', 'is_archived' => false]);

        // ₹90 total: Sandeep share ₹70 (paid 70 by friend), User share ₹20 (paid 20 by me).
        // Grouped breakdown: ₹5 UPI + ₹15 Cash = ₹20 (matches user's ₹20 paid out-of-pocket).
        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 90,
            'gst_amount' => 0,
            'description' => 'Surya Bakery',
            'category_id' => $category->id,
            'payment_method' => 'UPI',
            'date' => '2026-09-08',
            'split_with_friend_id' => $sandeep->id,
            'split_my_share' => 20,
            'split_friend_share' => 70,
            'split_paid_by_type' => 'split',
            'split_paid_by_me_amount' => 20,
            'split_paid_by_friend_amount' => 70,
            'add_group_expense' => '1',
            'group_expenses' => [
                ['amount' => 5, 'payment_method' => 'UPI'],
                ['amount' => 15, 'payment_method' => 'Cash'],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('expenses.index'));

        // Parent expense exists
        $expense = Expense::query()->where('description', 'Surya Bakery')->firstOrFail();
        $this->assertEquals(90.0, (float) $expense->amount);
        $this->assertStringContainsString('UPI', $expense->payment_method);
        $this->assertStringContainsString('Cash', $expense->payment_method);
        $this->assertStringContainsString('Payment breakdown', (string) $expense->notes);
        $this->assertStringContainsString('₹5.00', (string) $expense->notes);
        $this->assertStringContainsString('₹15.00', (string) $expense->notes);

        // Friend split recorded
        $split = FriendSplit::query()->where('expense_id', $expense->id)->firstOrFail();
        $this->assertEquals(20.0, (float) $split->paid_by_me_amount);
        $this->assertEquals(20.0, (float) $split->my_share);
        $this->assertEquals(70.0, (float) $split->friend_share);
        $this->assertEquals(70.0, (float) $split->paid_by_friend_amount);
        $this->assertEquals(0.0, $split->netAmount());
    }

    public function test_credit_amount_paid_can_be_edited_smoothly(): void
    {
        $user = $this->createUser();
        $friend = Friend::query()->create(['user_id' => $user->id, 'name' => 'Rahul', 'role' => 'Friend']);

        $credit = CreditDebt::query()->create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'type' => 'credit',
            'amount' => 500,
            'amount_paid' => 100,
            'date' => '2026-09-01',
            'description' => 'Borrowed for travel',
            'status' => 'partially_paid',
        ]);

        $this->assertEquals(400.0, $credit->remaining());

        // Update amount_paid from 100 to 250
        $response = $this->actingAs($user)->put(route('credits.update', $credit), [
            'type' => 'credit',
            'friend_id' => $friend->id,
            'amount' => 500,
            'amount_paid' => 250,
            'date' => '2026-09-01',
            'description' => 'Borrowed for travel (partially returned)',
            'status' => 'partially_paid',
            'payment_method' => 'UPI',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('credits.index', ['type' => 'credit']));

        $credit->refresh();
        $this->assertEquals(250.0, (float) $credit->amount_paid);
        $this->assertEquals(250.0, $credit->remaining());
    }

    public function test_dashboard_period_filter_and_wallet_balances(): void
    {
        $user = $this->createUser();

        PaymentWallet::query()->create([
            'user_id' => $user->id,
            'payment_method' => 'Cash',
            'is_enabled' => true,
            'opening_balance' => 1000,
            'opening_as_of' => '2026-09-01',
        ]);

        PaymentWallet::query()->create([
            'user_id' => $user->id,
            'payment_method' => 'UPI',
            'is_enabled' => true,
            'opening_balance' => 2000,
            'opening_as_of' => '2026-09-01',
        ]);

        // Today view
        $responseToday = $this->actingAs($user)->get(route('dashboard', ['period' => 'today']));
        $responseToday->assertOk();
        $responseToday->assertSee('Total Current Balance');
        $responseToday->assertSee('Cash');
        $responseToday->assertSee('UPI');

        // Week view
        $responseWeek = $this->actingAs($user)->get(route('dashboard', ['period' => 'week']));
        $responseWeek->assertOk();
        $responseWeek->assertSee('This Week');

        // Month view
        $responseMonth = $this->actingAs($user)->get(route('dashboard', ['period' => 'month']));
        $responseMonth->assertOk();
        $responseMonth->assertSee('This Month');
    }

    public function test_combination_payment_can_record_paid_amounts_alone_without_entering_shares(): void
    {
        $user = $this->createUser();
        $sandeep = Friend::query()->create(['user_id' => $user->id, 'name' => 'Sandeep', 'role' => 'Friend']);
        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Snacks', 'is_archived' => false]);

        // Exact user scenario: Total ₹90.00, Sandeep paid ₹70, User paid ₹20, shares left 0.00
        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 90,
            'gst_amount' => 0,
            'description' => 'Surya Bakery Co-pay',
            'category_id' => $category->id,
            'payment_method' => 'UPI',
            'date' => '2026-09-08',
            'record_as_combination' => '1',
            'split_my_share' => 0,
            'split_paid_by_me_amount' => 20,
            'splits' => [
                [
                    'friend_id' => $sandeep->id,
                    'friend_share' => 0,
                    'paid_by_friend_amount' => 70,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('expenses.index'));

        $expense = Expense::query()->where('description', 'Surya Bakery Co-pay')->firstOrFail();
        $this->assertEquals(90.0, (float) $expense->amount);

        $split = FriendSplit::query()->where('expense_id', $expense->id)->firstOrFail();
        $this->assertEquals(70.0, (float) $split->paid_by_friend_amount);
        $this->assertEquals(70.0, (float) $split->friend_share);
        $this->assertEquals(0.0, $split->netAmount());
    }

    public function test_combination_payment_with_multiple_friends_where_paid_alone_is_enough(): void
    {
        $user = $this->createUser();
        $sandeep = Friend::query()->create(['user_id' => $user->id, 'name' => 'Sandeep', 'role' => 'Friend']);
        $rahul = Friend::query()->create(['user_id' => $user->id, 'name' => 'Rahul', 'role' => 'Friend']);
        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Dinner', 'is_archived' => false]);

        // Total ₹100: User paid ₹20, Sandeep paid ₹50, Rahul paid ₹30. Shares left at 0.
        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 100,
            'gst_amount' => 0,
            'description' => 'Group Dinner Party',
            'category_id' => $category->id,
            'payment_method' => 'UPI',
            'date' => '2026-09-08',
            'split_my_share' => 0,
            'split_paid_by_me_amount' => 20,
            'splits' => [
                [
                    'friend_id' => $sandeep->id,
                    'friend_share' => 0,
                    'paid_by_friend_amount' => 50,
                ],
                [
                    'friend_id' => $rahul->id,
                    'friend_share' => 0,
                    'paid_by_friend_amount' => 30,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('expenses.index'));

        $sandeepSplit = FriendSplit::query()->where('friend_id', $sandeep->id)->firstOrFail();
        $this->assertEquals(50.0, (float) $sandeepSplit->paid_by_friend_amount);
        $this->assertEquals(50.0, (float) $sandeepSplit->friend_share);
        $this->assertEquals(0.0, $sandeepSplit->netAmount());

        $rahulSplit = FriendSplit::query()->where('friend_id', $rahul->id)->firstOrFail();
        $this->assertEquals(30.0, (float) $rahulSplit->paid_by_friend_amount);
        $this->assertEquals(30.0, (float) $rahulSplit->friend_share);
        $this->assertEquals(0.0, $rahulSplit->netAmount());
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'Admin', 'display_name' => 'Admin']);
        $user->roles()->attach($role);

        return $user;
    }
}
