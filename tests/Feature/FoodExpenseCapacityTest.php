<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\FoodEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodExpenseCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_food_can_fill_remaining_parent_expense_amount(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpense($user, 280);
        $this->createFood($user, $expense, 200, 'Mario meal');
        $newFood = $this->createFood($user, null, 80, 'Mario dessert');

        $response = $this->actingAs($user)->post(route('expenses.link-food', $expense), [
            'food_entry_ids' => [$newFood->id],
        ]);

        $response->assertRedirect();
        $this->assertSame(0.0, $expense->fresh()->remainingAmount());
        $this->assertSame($expense->id, $newFood->fresh()->expense_id);
    }

    public function test_food_cannot_exceed_parent_expense_amount(): void
    {
        $user = User::factory()->create();
        $expense = $this->createExpense($user, 280);
        $this->createFood($user, $expense, 280, 'Mario meal');
        $newFood = $this->createFood($user, null, 1, 'Extra dessert');

        $response = $this->actingAs($user)->post(route('expenses.link-food', $expense), [
            'food_entry_ids' => [$newFood->id],
        ]);

        $response->assertStatus(422);
        $this->assertNull($newFood->fresh()->expense_id);
        $this->assertSame(0.0, $expense->fresh()->remainingAmount());
    }

    private function createExpense(User $user, float $amount): Expense
    {
        return Expense::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'date' => '2026-09-06',
            'description' => 'Mario',
            'payment_method' => 'Cash',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);
    }

    private function createFood(User $user, ?Expense $expense, float $amount, string $name): FoodEntry
    {
        return FoodEntry::create([
            'user_id' => $user->id,
            'expense_id' => $expense?->id,
            'item_name' => $name,
            'amount' => $amount,
            'date' => '2026-09-06',
            'paid_by' => 'Me',
        ]);
    }
}
