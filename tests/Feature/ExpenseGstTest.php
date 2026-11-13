<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseStatement;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_previous_month_statement_snapshots_only_non_voluntary_expenses(): void
    {
        $this->travelTo(Carbon::parse('2026-03-15 12:00:00'));
        $user = User::factory()->create();
        $regularExpense = Expense::query()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'gst_amount' => 10,
            'date' => '2026-02-10',
            'description' => 'Previous month regular',
            'payment_method' => 'Cash',
            'is_voluntary' => false,
        ]);
        Expense::query()->create([
            'user_id' => $user->id,
            'amount' => 25,
            'date' => '2026-02-12',
            'description' => 'Previous month voluntary',
            'payment_method' => 'Cash',
            'is_voluntary' => true,
        ]);
        Expense::query()->create([
            'user_id' => $user->id,
            'amount' => 50,
            'date' => '2026-03-01',
            'description' => 'Current month regular',
            'payment_method' => 'Cash',
            'is_voluntary' => false,
        ]);

        $response = $this->actingAs($user)->post(route('statements.store'), [
            'type' => 'normal',
            'period_type' => 'previous_month',
        ]);

        $response->assertRedirect();
        $statement = ExpenseStatement::query()->firstOrFail();
        $this->assertSame('2026-02-01', $statement->start_date->toDateString());
        $this->assertSame('2026-02-28', $statement->end_date->toDateString());
        $this->assertSame(110.0, (float) $statement->total_amount);
        $this->assertSame([$regularExpense->id], $statement->custom_expense_ids);

        $showResponse = $this->actingAs($user)->get(route('statements.show', $statement));
        $showResponse->assertSee('Previous month regular');
        $showResponse->assertDontSee('Previous month voluntary');
        $showResponse->assertDontSee('Current month regular');
    }

    public function test_empty_custom_statement_snapshot_does_not_fall_back_to_existing_expenses(): void
    {
        $user = User::factory()->create();
        $existingExpense = Expense::query()->create([
            'user_id' => $user->id,
            'amount' => 100,
            'date' => '2026-09-01',
            'description' => 'Not selected regular expense',
            'payment_method' => 'Cash',
            'is_voluntary' => false,
        ]);
        $voluntaryExpense = Expense::query()->create([
            'user_id' => $user->id,
            'amount' => 25,
            'date' => '2026-09-02',
            'description' => 'Selected voluntary expense',
            'payment_method' => 'Cash',
            'is_voluntary' => true,
        ]);

        $response = $this->actingAs($user)->post(route('statements.store'), [
            'type' => 'normal',
            'period_type' => 'all',
            'custom_expense_ids' => [$voluntaryExpense->id],
        ]);

        $response->assertRedirect();
        $statement = ExpenseStatement::query()->firstOrFail();
        $this->assertSame([], $statement->custom_expense_ids);
        $this->assertSame(0.0, (float) $statement->total_amount);

        $showResponse = $this->actingAs($user)->get(route('statements.show', $statement));
        $showResponse->assertDontSee('Not selected regular expense');
        $showResponse->assertDontSee('Selected voluntary expense');
        $this->assertModelExists($existingExpense);
    }

    public function test_personal_statement_includes_gst_and_excludes_voluntary_expense(): void
    {
        $user = User::factory()->create();
        $category = PersonalExpenseCategory::query()->create([
            'user_id' => $user->id,
            'name' => 'Shopping',
            'color' => '#ec4899',
        ]);
        $regularExpense = PersonalExpense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 100,
            'gst_amount' => 18,
            'date' => '2026-09-01',
            'description' => 'Personal purchase with GST',
            'is_voluntary' => false,
        ]);
        $voluntaryExpense = PersonalExpense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 25,
            'date' => '2026-09-02',
            'description' => 'Personal voluntary purchase',
            'is_voluntary' => true,
        ]);

        $response = $this->actingAs($user)->post(route('statements.store'), [
            'type' => 'personal',
            'period_type' => 'all',
            'custom_expense_ids' => [$regularExpense->id, $voluntaryExpense->id],
        ]);

        $response->assertRedirect();
        $statement = ExpenseStatement::query()->firstOrFail();
        $this->assertSame(118.0, (float) $statement->total_amount);
        $this->assertSame([$regularExpense->id], $statement->custom_expense_ids);

        $showResponse = $this->actingAs($user)->get(route('statements.show', $statement));
        $showResponse->assertSee('Personal purchase with GST');
        $showResponse->assertDontSee('Personal voluntary purchase');
        $this->assertModelExists($voluntaryExpense);
    }

    public function test_expense_breakdown_keeps_voluntary_spending_out_of_expense_totals(): void
    {
        $user = User::factory()->create();
        $category = ExpenseCategory::query()->create([
            'user_id' => $user->id,
            'name' => 'Food',
            'is_archived' => false,
        ]);
        Expense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 100,
            'date' => now()->toDateString(),
            'description' => 'Regular meal',
            'payment_method' => 'Cash',
            'is_voluntary' => false,
        ]);
        Expense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 25,
            'date' => now()->toDateString(),
            'description' => 'Voluntary treat',
            'payment_method' => 'UPI',
            'is_voluntary' => true,
        ]);
        $personalCategory = PersonalExpenseCategory::query()->create([
            'user_id' => $user->id,
            'name' => 'Personal',
            'color' => '#ec4899',
        ]);
        PersonalExpense::query()->create([
            'user_id' => $user->id,
            'category_id' => $personalCategory->id,
            'amount' => 40,
            'gst_amount' => 5,
            'date' => now()->toDateString(),
            'description' => 'Regular personal purchase',
            'is_voluntary' => false,
        ]);
        PersonalExpense::query()->create([
            'user_id' => $user->id,
            'category_id' => $personalCategory->id,
            'amount' => 15,
            'gst_amount' => 2,
            'date' => now()->toDateString(),
            'description' => 'Voluntary personal purchase',
            'is_voluntary' => true,
        ]);

        $expenseIndexResponse = $this->actingAs($user)->get(route('expenses.index'));
        $expenseIndexResponse->assertOk();
        $this->assertSame(100.0, (float) $expenseIndexResponse->viewData('totalAmount'));
        $this->assertSame(1, $expenseIndexResponse->viewData('totalCount'));

        $response = $this->actingAs($user)->get(route('expenses.breakdown'));

        $response->assertOk();
        $this->assertSame(100.0, $response->viewData('totalNormalSpend'));
        $this->assertSame(25.0, $response->viewData('totalVoluntarySpend'));
        $this->assertSame(100.0, $response->viewData('totalBaseSpend'));
        $this->assertSame(100.0, $response->viewData('overallCategoryBreakdown')->sum('total'));
        $this->assertSame(100.0, $response->viewData('overallPaymentBreakdown')->sum('total'));
        $this->assertSame(100.0, $response->viewData('monthlyGroups')->first()['total']);

        $categoryResponse = $this->actingAs($user)->get(route('expenses.calculate-category', ['period' => 'month']));
        $categoryResponse->assertJsonPath('overall_total', 100);
        $categoryResponse->assertJsonPath('transaction_count', 1);

        $allExpensesResponse = $this->actingAs($user)->get(route('all-expenses.index'));
        $allExpensesResponse->assertOk();
        $this->assertSame(100.0, $allExpensesResponse->viewData('normalTotal'));
        $this->assertSame(45.0, $allExpensesResponse->viewData('personalTotal'));
        $this->assertSame(145.0, $allExpensesResponse->viewData('combinedTotal'));
        $this->assertSame(42.0, $allExpensesResponse->viewData('voluntaryTotal'));
    }
}
