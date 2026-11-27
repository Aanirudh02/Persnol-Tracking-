<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Friend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensePaymentBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_an_expense_records_and_removes_a_payment_breakdown(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpense($user, 1500);

        $this->actingAs($user)->put(route('expenses.update', $expense), $this->payload([
            'add_group_expense' => '1',
            'group_expenses' => [
                ['amount' => 1200, 'payment_method' => 'UPI'],
                ['amount' => 300, 'payment_method' => 'Cash'],
            ],
        ]))->assertRedirect(route('expenses.index'));

        $expense->refresh();
        $this->assertDatabaseCount('expenses', 1);
        $this->assertSame('UPI + Cash', $expense->payment_method);
        $this->assertSame(['UPI' => 1200.0, 'Cash' => 300.0], $expense->paymentBreakdownAmounts());

        $this->actingAs($user)->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertSee('name="add_group_expense" value="1" checked', false);

        $this->actingAs($user)->put(route('expenses.update', $expense), $this->payload([
            'notes' => $expense->notes,
        ]))->assertRedirect(route('expenses.index'));

        $expense->refresh();
        $this->assertSame('Cash', $expense->payment_method);
        $this->assertSame(['Cash' => 1500.0], $expense->paymentBreakdownAmounts());
    }

    public function test_edit_breakdown_must_add_up_to_the_amount_paid(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpense($user, 1500);

        $this->actingAs($user)->put(route('expenses.update', $expense), $this->payload([
            'add_group_expense' => '1',
            'group_expenses' => [
                ['amount' => 1000, 'payment_method' => 'UPI'],
                ['amount' => 100, 'payment_method' => 'Cash'],
            ],
        ]))->assertSessionHasErrors('group_expenses');

        $this->assertSame('Cash', $expense->refresh()->payment_method);
    }

    public function test_friend_pays_part_and_my_part_is_broken_down_by_method(): void
    {
        $user = User::factory()->create();
        $friend = Friend::create(['user_id' => $user->id, 'name' => 'Rajesh']);
        $payload = $this->payload([
            'amount' => 300,
            'payment_method' => 'Split',
            'record_as_combination' => '1',
            'split_paid_by_me_amount' => 200,
            'splits' => [
                ['friend_id' => $friend->id, 'paid_by_friend_amount' => 100, 'payment_method' => 'UPI'],
            ],
            'add_group_expense' => '1',
            'group_expenses' => [
                ['amount' => 100, 'payment_method' => 'UPI'],
                ['amount' => 100, 'payment_method' => 'GPay'],
            ],
        ]);

        $this->actingAs($user)->post(route('expenses.store'), $payload)->assertSessionHasNoErrors();

        $expense = Expense::query()->sole();
        $this->assertSame(200.0, $expense->totalAmount());
        $this->assertSame(['UPI' => 100.0, 'GPay' => 100.0], $expense->paymentBreakdownAmounts());

        $this->actingAs($user)->put(route('expenses.update', $expense), $payload)->assertSessionHasNoErrors();

        $expense = Expense::query()->sole();
        $this->assertSame(200.0, $expense->totalAmount());
        $this->assertSame('UPI + GPay', $expense->payment_method);
        $this->assertSame(['UPI' => 100.0, 'GPay' => 100.0], $expense->paymentBreakdownAmounts());
        $this->assertSame(300.0, $expense->originalBillTotal());
    }

    private function createExpense(User $user, float $amount): Expense
    {
        $this->actingAs($user)->post(route('expenses.store'), $this->payload(['amount' => $amount]));

        return Expense::query()->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'amount' => 1500,
            'description' => 'Team treat',
            'payment_method' => 'Cash',
            'date' => now()->toDateString(),
            'reason' => 'Forgot details',
        ], $overrides);
    }
}
