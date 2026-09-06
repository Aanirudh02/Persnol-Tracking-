<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_can_be_recorded_as_separate_grouped_payment_rows(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 250,
            'description' => 'Polar Bear Ice Cream',
            'payment_method' => 'Cash',
            'date' => '2026-09-06',
            'add_group_expense' => '1',
            'group_expenses' => [
                ['amount' => 160, 'payment_method' => 'Cash'],
                ['amount' => 90, 'payment_method' => 'UPI'],
            ],
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseCount('expenses', 2);
        $this->assertDatabaseCount('expense_groups', 1);
        $this->assertDatabaseHas('expenses', [
            'amount' => 160,
            'payment_method' => 'Cash',
            'description' => 'Polar Bear Ice Cream',
        ]);
        $this->assertDatabaseHas('expenses', [
            'amount' => 90,
            'payment_method' => 'UPI',
            'description' => 'Polar Bear Ice Cream',
        ]);

        $group = ExpenseGroup::query()->firstOrFail();
        $this->assertSame($user->id, $group->user_id);
        $this->assertSame(2, $group->expenses()->count());
        $this->assertSame($group->id, Expense::query()->firstOrFail()->expense_group_id);
    }

    public function test_grouped_payment_lines_must_match_total(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('expenses.create'))->post(route('expenses.store'), [
            'amount' => 250,
            'description' => 'Polar Bear Ice Cream',
            'payment_method' => 'Cash',
            'date' => '2026-09-06',
            'add_group_expense' => '1',
            'group_expenses' => [
                ['amount' => 160, 'payment_method' => 'Cash'],
                ['amount' => 80, 'payment_method' => 'UPI'],
            ],
        ]);

        $response->assertRedirect(route('expenses.create'));
        $response->assertSessionHasErrors('group_expenses');
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('expense_groups', 0);
    }
}
