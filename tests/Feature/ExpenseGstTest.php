<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseGstTest extends TestCase
{
    use RefreshDatabase;

    public function test_gst_is_added_to_the_expense_gross_total(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 250,
            'gst_amount' => 30,
            'description' => 'Ice Cream',
            'payment_method' => 'Cash',
            'date' => '2026-09-06',
        ]);

        $response->assertRedirect(route('expenses.index'));
        $expense = Expense::query()->firstOrFail();
        $this->assertSame(250.0, (float) $expense->amount);
        $this->assertSame(30.0, (float) $expense->gst_amount);
        $this->assertSame(280.0, $expense->totalAmount());
    }

    public function test_grouped_payment_rows_share_the_expense_gst_without_double_counting(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 250,
            'gst_amount' => 30,
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
        $expenses = Expense::query()->orderBy('id')->get();
        $this->assertCount(2, $expenses);
        $this->assertSame(250.0, (float) $expenses->sum('amount'));
        $this->assertSame(30.0, (float) $expenses->sum('gst_amount'));
        $this->assertSame(280.0, $expenses->sum(fn (Expense $expense): float => $expense->totalAmount()));
    }
}
