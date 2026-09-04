<?php

namespace Tests\Feature;

use App\Models\CreditDebt;
use App\Models\Friend;
use App\Models\FriendTransaction;
use App\Models\User;
use App\Services\FinanceLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditDebtSemanticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_means_i_owe_friend_on_sync(): void
    {
        $user = User::factory()->create();
        $friend = Friend::create([
            'user_id' => $user->id,
            'name' => 'Sandeep',
            'role' => 'Friend',
        ]);

        $item = CreditDebt::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'type' => 'credit',
            'amount' => 200,
            'amount_paid' => 0,
            'status' => 'yet_to_pay',
            'date' => now()->toDateString(),
            'description' => 'Lunch',
            'source' => 'manual',
        ]);

        app(FinanceLinkService::class)->syncCreditDebtToFriend($item);

        $tx = FriendTransaction::where('friend_id', $friend->id)->first();
        $this->assertNotNull($tx);
        $this->assertSame('friend_paid_for_me', $tx->type);
        $this->assertEquals(200.0, (float) $tx->my_share);

        $balance = $friend->fresh()->getBalance();
        $this->assertEquals(200.0, $balance['i_owe_friend']);
        $this->assertEquals(0.0, $balance['friend_owes_me']);
    }

    public function test_debt_means_they_owe_me_on_sync(): void
    {
        $user = User::factory()->create();
        $friend = Friend::create([
            'user_id' => $user->id,
            'name' => 'Rajesh',
            'role' => 'Friend',
        ]);

        $item = CreditDebt::create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'type' => 'debt',
            'amount' => 150,
            'amount_paid' => 0,
            'status' => 'yet_to_pay',
            'date' => now()->toDateString(),
            'description' => 'Paid for him',
            'source' => 'manual',
        ]);

        app(FinanceLinkService::class)->syncCreditDebtToFriend($item);

        $tx = FriendTransaction::where('friend_id', $friend->id)->first();
        $this->assertNotNull($tx);
        $this->assertSame('paid_for_friend', $tx->type);
        $this->assertEquals(150.0, (float) $tx->friend_share);

        $balance = $friend->fresh()->getBalance();
        $this->assertEquals(150.0, $balance['friend_owes_me']);
    }
}
