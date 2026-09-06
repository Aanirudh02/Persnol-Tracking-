<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_top_level_expenses_can_be_grouped_and_ungrouped(): void
    {
        $user = User::factory()->create();
        $first = $this->createExpense($user, 160, 'Cash');
        $second = $this->createExpense($user, 90, 'UPI');

        $response = $this->actingAs($user)->post(route('expenses.group'), [
            'expense_ids' => [$first->id, $second->id],
            'name' => 'Polar Bear Ice Cream',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $group = ExpenseGroup::query()->firstOrFail();
        $this->assertSame(250.0, (float) $group->expenses()->sum('amount'));

        $response = $this->actingAs($user)->put(route('expense-groups.update', $group), [
            'name' => 'Saturday Ice Cream',
        ]);

        $response->assertRedirect();
        $this->assertSame('Saturday Ice Cream', $group->fresh()->name);

        $response = $this->actingAs($user)->put(route('expense-groups.expenses.payment-method', [$group, $second]), [
            'payment_method' => 'UPI',
        ]);

        $response->assertRedirect();
        $this->assertSame('UPI', $second->fresh()->payment_method);

        $response = $this->actingAs($user)->delete(route('expense-groups.destroy', $group));

        $response->assertRedirect();
        $this->assertDatabaseCount('expense_groups', 0);
        $this->assertNull($first->fresh()->expense_group_id);
        $this->assertNull($second->fresh()->expense_group_id);
    }

    private function createExpense(User $user, float $amount, string $paymentMethod): Expense
    {
        return Expense::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'date' => '2026-09-06',
            'description' => 'Polar Bear Ice Cream',
            'payment_method' => $paymentMethod,
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);
    }
}
