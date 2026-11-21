<?php

namespace Tests\Feature;

use App\Models\CreditDebt;
use App\Models\CreditDebtPayment;
use App\Models\DailyBalance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Friend;
use App\Models\Income;
use App\Models\IncomeExpenseTally;
use App\Models\Lesson;
use App\Models\Mistake;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Models\User;
use App\Services\DailyRegisterService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceClosureBulkEntryAndLessonsTest extends TestCase
{
    use RefreshDatabase;

    private function friendFor(User $user, string $name = 'Ravi'): Friend
    {
        return Friend::create(['user_id' => $user->id, 'name' => $name, 'role' => 'Friend']);
    }

    private function expenseFor(User $user, array $attributes = []): Expense
    {
        return Expense::create([
            'user_id' => $user->id,
            'amount' => 100,
            'gst_amount' => 0,
            'date' => '2026-10-10',
            'description' => 'Tea',
            'payment_method' => 'Cash',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
            ...$attributes,
        ]);
    }

    public function test_closing_a_debt_closes_its_expense_and_reopening_the_debt_reopens_it(): void
    {
        $user = User::factory()->create();
        $friend = $this->friendFor($user);

        $this->actingAs($user)->post(route('credits.store'), [
            'type' => 'debt',
            'friend_id' => $friend->id,
            'amount' => 500,
            'date' => '2026-10-05',
            'payment_method' => 'UPI',
            'link_as_expense' => 1,
        ])->assertRedirect();

        $debt = CreditDebt::firstOrFail();
        $lendingExpense = $debt->linkedExpenses()->firstOrFail();
        $this->assertFalse($lendingExpense->is_closed);

        $this->actingAs($user)->post(route('credits.close', $debt), ['date' => '2026-10-20', 'payment_method' => 'UPI'])->assertRedirect();

        $lendingExpense->refresh();
        $this->assertTrue($lendingExpense->is_closed);
        $this->assertSame('debt_closed', $lendingExpense->closed_reason);

        $this->actingAs($user)->get(route('expenses.index'))->assertOk()->assertSee('Closed (debt closed)');

        // Undo the closing payment: the debt is open again, so its expense reopens
        $closingPayment = CreditDebtPayment::where('credit_debt_id', $debt->id)->firstOrFail();
        $this->actingAs($user)->delete(route('credits.payments.destroy', [$debt, $closingPayment]))->assertRedirect();

        $this->assertFalse($lendingExpense->fresh()->is_closed);
    }

    public function test_reopening_a_debt_keeps_manually_closed_expenses_closed(): void
    {
        $user = User::factory()->create();
        $debt = CreditDebt::create([
            'user_id' => $user->id,
            'friend_id' => $this->friendFor($user)->id,
            'type' => 'debt',
            'amount' => 300,
            'amount_paid' => 0,
            'status' => 'yet_to_pay',
            'date' => '2026-10-01',
            'source' => 'manual',
        ]);
        $expense = $this->expenseFor($user, ['credit_debt_id' => $debt->id]);
        $expense->markClosed();

        $debt->update(['status' => 'partially_paid']);

        $this->assertTrue($expense->fresh()->is_closed);
        $this->assertSame('manual', $expense->fresh()->closed_reason);
    }

    public function test_expense_and_personal_expense_can_be_closed_and_reopened(): void
    {
        $user = User::factory()->create();
        $expense = $this->expenseFor($user);
        $category = PersonalExpenseCategory::create(['user_id' => $user->id, 'name' => 'Gifts']);
        $personal = PersonalExpense::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 50,
            'gst_amount' => 0,
            'date' => '2026-10-10',
            'payment_method' => 'Cash',
        ]);

        $this->actingAs($user)->post(route('expenses.close', $expense))->assertRedirect();
        $this->actingAs($user)->post(route('personal-expenses.close', $personal))->assertRedirect();
        $this->assertTrue($expense->fresh()->is_closed);
        $this->assertTrue($personal->fresh()->is_closed);

        $this->actingAs($user)->get(route('expenses.show', $expense))->assertOk()->assertSee('Settled & closed', false);

        $this->actingAs($user)->post(route('expenses.reopen', $expense))->assertRedirect();
        $this->actingAs($user)->post(route('personal-expenses.reopen', $personal))->assertRedirect();
        $this->assertFalse($expense->fresh()->is_closed);
        $this->assertFalse($personal->fresh()->is_closed);

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)->post(route('expenses.close', $expense))->assertForbidden();
    }

    public function test_each_credit_due_payment_can_be_registered_as_its_own_linked_expense(): void
    {
        $user = User::factory()->create();
        $credit = CreditDebt::create([
            'user_id' => $user->id,
            'friend_id' => $this->friendFor($user)->id,
            'type' => 'credit',
            'amount' => 1000,
            'amount_paid' => 0,
            'status' => 'yet_to_pay',
            'date' => '2026-10-01',
            'source' => 'manual',
        ]);

        $this->actingAs($user)->post(route('credits.payments', $credit), [
            'amount' => 400,
            'paid_on' => '2026-10-05',
            'payment_method' => 'UPI',
            'register_as_expense' => 1,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('credits.payments', $credit), [
            'amount' => 300,
            'paid_on' => '2026-10-15',
            'payment_method' => 'Cash',
        ])->assertRedirect();

        $this->assertSame(1, $credit->linkedExpenses()->count());
        $unregistered = CreditDebtPayment::whereNull('expense_id')->firstOrFail();

        $this->actingAs($user)->post(route('credits.payments.register-expense', [$credit, $unregistered]))->assertRedirect();

        $expenses = $credit->linkedExpenses()->orderBy('date')->get();
        $this->assertCount(2, $expenses);
        $this->assertSame(['UPI', 'Cash'], $expenses->pluck('payment_method')->all());
        $this->assertSame(['2026-10-05', '2026-10-15'], $expenses->map(fn (Expense $e) => $e->date->toDateString())->all());
        $this->assertSame(2, CreditDebtPayment::whereNotNull('expense_id')->count());
        $this->assertEquals(700.0, (float) $credit->fresh()->amount_paid);

        // A payment is filed only once
        $this->actingAs($user)->post(route('credits.payments.register-expense', [$credit, $unregistered->fresh()]));
        $this->assertSame(2, $credit->linkedExpenses()->count());
    }

    public function test_separate_income_shows_on_its_day_but_is_not_carried_to_the_next_day(): void
    {
        $user = User::factory()->create();
        foreach ([[1000, true], [200, false]] as [$amount, $separate]) {
            Income::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'source' => $separate ? 'Stipend' : 'Pocket money',
                'date' => '2026-10-10',
                'payment_method' => 'Cash',
                'keep_separate' => $separate,
            ]);
        }

        $register = app(DailyRegisterService::class)->computeDailyRegisterForMonth(
            $user,
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31'),
            ['Cash', 'UPI'],
            Carbon::parse('2026-10-10')
        );

        $dayTen = $register['days']['2026-10-10']['categories']['Cash'];
        $this->assertEquals(1200.0, $dayTen['inflow']);
        $this->assertEquals(1200.0, $dayTen['closing']);
        $this->assertEquals(1000.0, $dayTen['separate_inflow']);
        $this->assertEquals(200.0, $dayTen['carry_forward']);
        $this->assertEquals(200.0, $register['days']['2026-10-11']['categories']['Cash']['opening']);
    }

    public function test_unlogged_expense_is_listed_separately_and_left_out_of_the_register(): void
    {
        $user = User::factory()->create();
        $this->expenseFor($user, ['description' => 'Forgotten auto fare', 'is_unlogged' => true, 'amount' => 80]);
        $this->expenseFor($user, ['description' => 'Lunch', 'amount' => 120]);

        $register = app(DailyRegisterService::class)->computeDailyRegisterForMonth(
            $user,
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-31'),
            ['Cash'],
            Carbon::parse('2026-10-10')
        );
        $this->assertEquals(120.0, $register['days']['2026-10-10']['categories']['Cash']['outflow']);

        $this->actingAs($user)->get(route('expenses.index', ['unlogged_only' => 1]))
            ->assertOk()
            ->assertSee('Forgotten auto fare')
            ->assertDontSee('>Lunch<', false);
    }

    public function test_register_statement_shows_each_type_with_its_own_balances_tally_and_adjustment_notes(): void
    {
        $user = User::factory()->create();
        $income = Income::create([
            'user_id' => $user->id,
            'amount' => 500,
            'source' => 'Salary advance',
            'date' => '2026-10-02',
            'payment_method' => 'UPI',
        ]);
        $expense = $this->expenseFor($user, ['description' => 'Mobile recharge', 'payment_method' => 'UPI', 'amount' => 199, 'date' => '2026-10-03']);
        IncomeExpenseTally::create(['user_id' => $user->id, 'income_id' => $income->id, 'expense_id' => $expense->id, 'allocated_amount' => 199]);
        DailyBalance::create([
            'user_id' => $user->id,
            'record_date' => '2026-10-04',
            'categories_data' => ['Cash' => ['adjustment' => -20, 'adjustment_note' => 'Counted drawer short']],
        ]);

        $response = $this->actingAs($user)->get(route('daily-balances.statement', ['from' => '2026-10-01', 'to' => '2026-10-05']));

        $response->assertOk()
            ->assertSee('Cash — balance before &amp; after', false)
            ->assertSee('UPI — balance before &amp; after', false)
            ->assertSee('Salary advance')
            ->assertSee('Mobile recharge')
            ->assertSee('Counted drawer short')
            ->assertDontSee('Combined');

        $this->actingAs($user)->get(route('daily-balances.statement', ['from' => '2026-10-05', 'to' => '2026-10-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_bulk_entry_saves_every_row_type_together(): void
    {
        $user = User::factory()->create();
        $expenseCategory = ExpenseCategory::create(['user_id' => $user->id, 'name' => 'Food']);
        $personalCategory = PersonalExpenseCategory::create(['user_id' => $user->id, 'name' => 'Family']);

        $this->actingAs($user)->get(route('bulk-entry.create'))->assertOk()->assertSee('Bulk add expense');

        $this->actingAs($user)->post(route('bulk-entry.store'), [
            'rows' => [
                ['type' => 'expense', 'date' => '2026-10-10', 'description' => 'Tea', 'category_id' => $expenseCategory->id, 'amount' => '20', 'payment_method' => 'Cash'],
                ['type' => 'expense', 'date' => '2026-01-04', 'description' => 'Old bus pass', 'amount' => '300', 'payment_method' => 'Cash', 'is_unlogged' => '1'],
                ['type' => 'personal', 'date' => '2026-10-10', 'description' => 'Gift for mom', 'category_id' => $personalCategory->id, 'amount' => '250', 'payment_method' => 'UPI', 'done_to' => 'Mom', 'add_to_main' => '1'],
                ['type' => 'income', 'date' => '2026-10-10', 'description' => 'Stipend', 'amount' => '5000', 'payment_method' => 'UPI', 'keep_separate' => '1'],
                ['type' => 'expense', 'date' => '2026-10-10', 'description' => '', 'amount' => '', 'payment_method' => 'Cash'],
            ],
        ])->assertRedirect(route('bulk-entry.create'))->assertSessionHas('success');

        $this->assertSame(3, Expense::where('user_id', $user->id)->count()); // 2 expenses + linked normal expense for the personal row
        $this->assertTrue(Expense::where('description', 'Old bus pass')->firstOrFail()->is_unlogged);
        $personal = PersonalExpense::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($personal->expense_id);
        $this->assertSame('Mom', $personal->done_to);
        $income = Income::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Stipend', $income->source);
        $this->assertTrue($income->keep_separate);
    }

    public function test_bulk_entry_saves_nothing_when_any_row_is_invalid(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('bulk-entry.store'), [
            'rows' => [
                ['type' => 'expense', 'date' => '2026-10-10', 'description' => 'Tea', 'amount' => '20', 'payment_method' => 'Cash'],
                ['type' => 'personal', 'date' => '2026-10-10', 'description' => 'No category', 'amount' => '50', 'payment_method' => 'Cash'],
            ],
        ])->assertSessionHasErrors('rows.1.category_id');

        $this->assertSame(0, Expense::count());
        $this->assertSame(0, PersonalExpense::count());
    }

    public function test_lessons_are_registered_separately_and_can_link_to_a_mistake(): void
    {
        $user = User::factory()->create();
        $mistake = Mistake::create([
            'user_id' => $user->id,
            'date' => '2026-10-10',
            'title' => 'Missed the bus',
            'severity' => 'Low',
            'status' => 'Open',
        ]);

        $this->actingAs($user)->post(route('lessons.store'), [
            'title' => 'Leave ten minutes early',
            'lesson' => 'Buffer time beats rushing.',
            'date' => '2026-10-11',
            'mistake_id' => $mistake->id,
        ])->assertRedirect(route('lessons.index'));

        $this->actingAs($user)->post(route('lessons.store'), [
            'title' => 'Standalone lesson',
            'lesson' => 'Write things down.',
            'date' => '2026-10-12',
        ])->assertRedirect(route('lessons.index'));

        $this->assertSame(2, Lesson::where('user_id', $user->id)->count());
        $this->assertSame(1, $mistake->lessons()->count());

        $this->actingAs($user)->get(route('lessons.index'))->assertOk()->assertSee('Standalone lesson')->assertSee('From mistake: Missed the bus');
        $this->actingAs($user)->get(route('mistakes.show', $mistake))->assertOk()->assertSee('Leave ten minutes early');

        $otherUser = User::factory()->create();
        $this->actingAs($otherUser)->post(route('lessons.store'), [
            'title' => 'Sneaky',
            'lesson' => 'Linking to someone else’s mistake',
            'date' => '2026-10-12',
            'mistake_id' => $mistake->id,
        ])->assertSessionHasErrors('mistake_id');
    }

    public function test_payments_page_is_gone_and_navigation_groups_income_with_expenses(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/finance/payments')->assertNotFound();

        $this->actingAs($user)->get(route('income.index'))
            ->assertOk()
            ->assertSee('aria-label="Main"', false)
            ->assertSeeInOrder(['Income &amp; expenses', 'Income', 'Expenses', 'Personal Expenses'], false)
            ->assertSee(route('bulk-entry.create'))
            ->assertSee('Mistakes &amp; Lessons', false);
    }
}
