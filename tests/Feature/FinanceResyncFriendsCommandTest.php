<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseGroup;
use App\Models\Friend;
use App\Models\FriendSplit;
use App\Models\FriendTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceResyncFriendsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_changes_without_mutating_data(): void
    {
        [$user, $friend] = $this->makeUserAndFriend();
        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Food', 'is_archived' => false, 'is_voluntary' => false]);

        Expense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 100,
            'gst_amount' => 0,
            'date' => '2026-09-07',
            'description' => 'Legacy equal split',
            'payment_method' => 'Cash',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
            'split_with_friend_id' => $friend->id,
            'split_my_share' => 50,
            'split_friend_share' => 50,
        ]);

        $this->artisan('finance:resync-friends --dry-run')
            ->expectsOutputToContain('Created splits: 1')
            ->expectsOutputToContain('Dry run complete. No data was changed.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('friend_splits', 0);
        $this->assertDatabaseCount('friend_transactions', 0);
    }

    public function test_resync_creates_authoritative_splits_and_projection_rows_and_reports_ambiguity(): void
    {
        [$user, $friend] = $this->makeUserAndFriend();
        $category = ExpenseCategory::query()->create(['user_id' => $user->id, 'name' => 'Travel', 'is_archived' => false, 'is_voluntary' => false]);

        Expense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 90,
            'gst_amount' => 10,
            'date' => '2026-09-07',
            'description' => 'Legacy friend paid',
            'payment_method' => 'Cash',
            'paid_by' => $friend->name,
            'paid_by_type' => 'friend',
            'paid_by_friend_id' => $friend->id,
        ]);

        $group = ExpenseGroup::query()->create([
            'user_id' => $user->id,
            'name' => 'Legacy Group',
        ]);

        Expense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 200,
            'gst_amount' => 0,
            'date' => '2026-09-07',
            'description' => 'Ambiguous grouped split',
            'payment_method' => 'Cash',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
            'split_with_friend_id' => $friend->id,
            'split_my_share' => 100,
            'split_friend_share' => 100,
            'expense_group_id' => $group->id,
        ]);

        FriendTransaction::query()->create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'type' => 'shared_expense',
            'paid_by_me' => true,
            'total_amount' => 60,
            'my_share' => 20,
            'friend_share' => 40,
            'description' => 'Legacy manual tx',
            'date' => '2026-09-07',
            'payment_method' => 'UPI',
            'is_settled' => false,
        ]);

        $this->artisan('finance:resync-friends')
            ->expectsOutputToContain('Created splits: 2')
            ->expectsOutputToContain('Ambiguous records:')
            ->expectsOutputToContain('grouped expense carries legacy friend split fields')
            ->expectsOutputToContain('Friend resync complete.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('friend_splits', 2);
        $this->assertDatabaseCount('friend_transactions', 3);

        $legacyExpenseSplit = FriendSplit::query()->where('expense_id', 1)->firstOrFail();
        $this->assertSame(-100.0, $legacyExpenseSplit->netAmount());

        $manualSplit = FriendSplit::query()->where('legacy_friend_transaction_id', 1)->firstOrFail();
        $this->assertSame(40.0, $manualSplit->netAmount());
    }

    /**
     * @return array{0: User, 1: Friend}
     */
    private function makeUserAndFriend(): array
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'Admin', 'display_name' => 'Admin']);
        $user->roles()->attach($role);
        $friend = Friend::query()->create([
            'user_id' => $user->id,
            'name' => 'Meera',
            'role' => 'Friend',
        ]);

        return [$user, $friend];
    }
}
