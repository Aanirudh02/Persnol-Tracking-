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
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFriendsOverhaulTest extends TestCase
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

        foreach (['Pocket Money', 'Salary', 'Other'] as $index => $source) {
            LookupOption::query()->create([
                'type' => 'income_source',
                'name' => $source,
                'sort_order' => $index,
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        foreach (['yet_to_pay', 'partially_paid', 'fully_paid', 'paid_late', 'failed_to_pay'] as $index => $status) {
            LookupOption::query()->create([
                'type' => 'credit_status',
                'name' => $status,
                'icon' => ucfirst(str_replace('_', ' ', $status)),
                'sort_order' => $index,
                'is_system' => true,
                'is_active' => true,
            ]);
        }

        Setting::setVal('expense_edit_window_days', 7, 'integer');
        Setting::setVal('income_edit_window_days', 7, 'integer');
        Setting::setVal('payment_edit_window_days', 7, 'integer');
        Setting::setVal('finance_dashboard_sections', [
            'show_wallet_balances' => true,
            'show_total_expense' => true,
            'show_current_balance' => true,
            'show_expense_by_payment_type' => true,
            'show_expense_by_category' => true,
            'show_friend_overview' => true,
        ], 'json');
    }

    public function test_expense_splits_support_equal_unequal_friend_paid_and_split_payment(): void
    {
        $user = $this->createUser();
        $friend = Friend::query()->create(['user_id' => $user->id, 'name' => 'Rahul', 'role' => 'Friend']);
        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Food', 'is_archived' => false, 'is_voluntary' => false]);

        $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 120,
            'gst_amount' => 0,
            'description' => 'Equal split meal',
            'payment_method' => 'Cash',
            'category_id' => $category->id,
            'date' => '2026-09-07',
            'split_with_friend_id' => $friend->id,
            'split_my_share' => 60,
            'split_friend_share' => 60,
            'split_paid_by_type' => 'me',
        ])->assertRedirect(route('expenses.index'));

        $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 150,
            'gst_amount' => 0,
            'description' => 'Unequal split',
            'payment_method' => 'UPI',
            'category_id' => $category->id,
            'date' => '2026-09-07',
            'split_with_friend_id' => $friend->id,
            'split_my_share' => 50,
            'split_friend_share' => 100,
            'split_paid_by_type' => 'me',
        ])->assertRedirect(route('expenses.index'));

        $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 90,
            'gst_amount' => 0,
            'description' => 'Friend paid fully',
            'payment_method' => 'Cash',
            'category_id' => $category->id,
            'date' => '2026-09-07',
            'split_with_friend_id' => $friend->id,
            'split_my_share' => 90,
            'split_friend_share' => 0,
            'split_paid_by_type' => 'friend',
        ])->assertRedirect(route('expenses.index'));

        $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 100,
            'gst_amount' => 0,
            'description' => 'Split payment cab',
            'payment_method' => 'Cash',
            'category_id' => $category->id,
            'date' => '2026-09-07',
            'split_with_friend_id' => $friend->id,
            'split_my_share' => 50,
            'split_friend_share' => 50,
            'split_paid_by_type' => 'split',
            'split_paid_by_me_amount' => 30,
            'split_paid_by_friend_amount' => 70,
        ])->assertRedirect(route('expenses.index'));

        $splits = FriendSplit::query()->where('friend_id', $friend->id)->orderBy('id')->get();
        $this->assertCount(4, $splits);
        $this->assertSame(60.0, $splits[0]->netAmount());
        $this->assertSame(100.0, $splits[1]->netAmount());
        $this->assertSame(-90.0, $splits[2]->netAmount());
        $this->assertSame(-20.0, $splits[3]->netAmount());
        $this->assertSame(50.0, $friend->fresh()->getBalance()['net']);
    }

    public function test_credit_crud_payments_and_settlements_recalculate_balance(): void
    {
        $user = $this->createUser();
        $friend = Friend::query()->create(['user_id' => $user->id, 'name' => 'Asha', 'role' => 'Friend']);

        $response = $this->actingAs($user)->post(route('credits.store'), [
            'type' => 'debt',
            'friend_id' => $friend->id,
            'amount' => 200,
            'amount_paid' => 50,
            'payment_method' => 'UPI',
            'date' => '2026-09-07',
            'description' => 'Asha lunch share',
            'status' => 'yet_to_pay',
        ]);
        $response->assertRedirect();

        $debt = CreditDebt::query()->firstOrFail();
        $this->assertSame(150.0, $debt->remaining());
        $this->assertSame(150.0, $friend->fresh()->getBalance()['net']);

        $this->actingAs($user)->post(route('credits.payments', $debt), [
            'amount' => 30,
            'paid_on' => '2026-09-08',
            'payment_method' => 'Cash',
        ])->assertRedirect();

        $debt->refresh();
        $this->assertSame(120.0, $debt->remaining());

        $this->actingAs($user)->put(route('credits.update', $debt), [
            'type' => 'credit',
            'friend_id' => $friend->id,
            'amount' => 150,
            'amount_paid' => 80,
            'date' => '2026-09-07',
            'description' => 'Actually I owed Asha',
            'status' => 'yet_to_pay',
        ])->assertRedirect(route('credits.index', ['type' => 'credit']));

        $debt->refresh();
        $this->assertSame('credit', $debt->type);
        $this->assertSame(150.0, (float) $debt->amount);
        $this->assertSame(80.0, (float) $debt->amount_paid);
        $this->assertSame(70.0, $debt->remaining());
        $this->assertSame(-70.0, $friend->fresh()->getBalance()['net']);

        $this->actingAs($user)->post(route('friends.settle', $friend), [
            'amount' => 20,
            'direction' => 'i_paid_friend',
            'payment_method' => 'Cash',
        ])->assertRedirect(route('friends.index'));

        $this->assertSame(-50.0, $friend->fresh()->getBalance()['net']);

        $this->actingAs($user)->delete(route('credits.destroy', $debt))->assertRedirect(route('credits.index', ['type' => 'credit']));
        $this->assertSame(20.0, $friend->fresh()->getBalance()['net']);
    }

    public function test_updating_and_deleting_expense_split_rebuilds_balance_without_double_counting(): void
    {
        $user = $this->createUser();
        $friend = Friend::query()->create(['user_id' => $user->id, 'name' => 'Kiran', 'role' => 'Friend']);
        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Travel', 'is_archived' => false, 'is_voluntary' => false]);

        $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 80,
            'gst_amount' => 20,
            'description' => 'Cab',
            'payment_method' => 'Cash',
            'category_id' => $category->id,
            'date' => '2026-09-07',
            'split_with_friend_id' => $friend->id,
            'split_my_share' => 40,
            'split_friend_share' => 60,
            'split_paid_by_type' => 'me',
        ])->assertRedirect(route('expenses.index'));

        $expense = Expense::query()->with('friendSplit')->firstOrFail();
        $this->assertSame(60.0, $expense->friendSplit->netAmount());
        $this->assertSame(100.0, (float) $expense->totalAmount());

        $wallet = PaymentWallet::query()->firstOrCreate([
            'user_id' => $user->id,
            'payment_method' => 'Cash',
        ], [
            'is_enabled' => true,
            'opening_balance' => 500,
            'opening_as_of' => '2026-09-01',
        ]);

        $this->actingAs($user)->get(route('finance.index'))->assertOk()->assertSee('₹400.00');

        $this->actingAs($user)->put(route('expenses.update', $expense), [
            'amount' => 80,
            'gst_amount' => 20,
            'description' => 'Cab revised',
            'payment_method' => 'Cash',
            'category_id' => $category->id,
            'date' => '2026-09-07',
            'time' => '10:00',
            'split_with_friend_id' => $friend->id,
            'split_my_share' => 50,
            'split_friend_share' => 50,
            'split_paid_by_type' => 'split',
            'split_paid_by_me_amount' => 45,
            'split_paid_by_friend_amount' => 55,
            'reason' => 'Fix actual payer split',
        ])->assertRedirect(route('expenses.index'));

        $expense->refresh()->load('friendSplit');
        $this->assertSame(-5.0, $expense->friendSplit->netAmount());
        $this->assertSame(-5.0, $friend->fresh()->getBalance()['net']);

        $this->actingAs($user)->delete(route('expenses.destroy', $expense), [
            'reason' => 'Wrong entry',
        ])->assertRedirect(route('expenses.index'));

        $this->assertDatabaseCount('friend_splits', 0);
        $this->assertSame(0.0, $friend->fresh()->getBalance()['net']);
        $this->assertNotNull($wallet->fresh());
    }

    public function test_dashboard_and_dynamic_payment_types_are_lookup_driven(): void
    {
        $user = $this->createUser();
        LookupOption::query()->create([
            'type' => 'payment_method',
            'name' => 'WalletX',
            'sort_order' => 99,
            'is_system' => false,
            'is_active' => true,
            'user_id' => $user->id,
        ]);

        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Bills', 'is_archived' => false, 'is_voluntary' => false]);
        PaymentWallet::query()->create([
            'user_id' => $user->id,
            'payment_method' => 'WalletX',
            'is_enabled' => true,
            'opening_balance' => 300,
            'opening_as_of' => '2026-09-01',
        ]);

        Expense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 120,
            'gst_amount' => 0,
            'date' => '2026-09-07',
            'description' => 'Hostel utility',
            'payment_method' => 'WalletX',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);

        $this->actingAs($user)->get(route('payments.create'))
            ->assertOk()
            ->assertSee('WalletX');

        $this->actingAs($user)->get(route('finance.index'))
            ->assertOk()
            ->assertSee('Expense By Payment Type')
            ->assertSee('Expense By Category')
            ->assertSee('Current Balance')
            ->assertSee('WalletX')
            ->assertSee('₹120.00')
            ->assertSee('₹180.00');
    }

    private function createUser(): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'Admin', 'display_name' => 'Admin']);
        $user->roles()->attach($role);

        return $user;
    }
}
