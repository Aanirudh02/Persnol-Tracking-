<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\IncomeExpenseTally;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IncomeTallyMonthPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::firstOrCreate(['name' => 'Admin'], ['display_name' => 'Administrator']);
        $user->roles()->attach($role);

        return $user;
    }

    private function createIncome(User $user, float $amount = 1000, string $date = '2026-09-10', string $source = 'September salary'): Income
    {
        return Income::query()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'source' => $source,
            'date' => $date,
            'payment_method' => 'UPI',
        ]);
    }

    private function createExpense(User $user, string $date, string $description, float $amount = 50, string $paymentMethod = 'UPI'): Expense
    {
        return Expense::query()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'date' => $date,
            'description' => $description,
            'payment_method' => $paymentMethod,
        ]);
    }

    private function tally(User $user, Income $income, Expense $expense, float $amount): void
    {
        IncomeExpenseTally::query()->create([
            'user_id' => $user->id,
            'income_id' => $income->id,
            'expense_id' => $expense->id,
            'allocated_amount' => $amount,
        ]);
    }

    public function test_tally_page_defaults_to_the_income_month_with_previous_and_next_links(): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user);
        $this->createExpense($user, '2026-09-03', 'September groceries');
        $this->createExpense($user, '2026-08-20', 'August groceries');
        $this->createExpense($user, '2026-10-01', 'October groceries');

        $response = $this->actingAs($user)->get(route('income.show', $income));

        $response->assertSee('Expenses paid via UPI · September 2026');
        $response->assertSee('September groceries');
        $response->assertDontSee('August groceries');
        $response->assertDontSee('October groceries');
        $response->assertSee('month=2026-08', false);
        $response->assertSee('month=2026-10', false);
    }

    public function test_tally_page_lists_the_expenses_of_the_requested_month(): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user);
        $this->createExpense($user, '2026-09-03', 'September groceries');
        $this->createExpense($user, '2026-08-20', 'August groceries');

        $response = $this->actingAs($user)->get(route('income.show', [$income, 'month' => '2026-08']));

        $response->assertSee('Expenses paid via UPI · August 2026');
        $response->assertSee('August groceries');
        $response->assertDontSee('September groceries');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidMonthQueries(): array
    {
        return [
            'month 13' => ['month=2026-13'],
            'text' => ['month=september'],
            'array' => ['month[]=2026-08'],
        ];
    }

    #[DataProvider('invalidMonthQueries')]
    public function test_invalid_month_falls_back_to_the_income_month_with_a_notice(string $query): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user);
        $this->createExpense($user, '2026-09-03', 'September groceries');
        $this->createExpense($user, '2026-08-20', 'August groceries');

        $response = $this->actingAs($user)->get(route('income.show', $income).'?'.$query);

        $response->assertSee('That month was not valid, so September 2026 is shown.');
        $response->assertSee('September groceries');
        $response->assertDontSee('August groceries');
    }

    public function test_month_lists_every_expense_and_reaches_expenses_older_than_the_latest_sixty(): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user);
        foreach (range(1, 65) as $number) {
            $this->createExpense($user, '2026-09-'.str_pad((string) (($number % 28) + 1), 2, '0', STR_PAD_LEFT), 'September item '.$number, 5);
        }
        $oldExpense = $this->createExpense($user, '2026-06-15', 'June rent share', 120);

        $septemberPage = $this->actingAs($user)->get(route('income.show', $income));
        $junePage = $this->actingAs($user)->get(route('income.show', [$income, 'month' => '2026-06']));
        $tallyResponse = $this->actingAs($user)->post(route('income.tally', $income), [
            'expense_ids' => [$oldExpense->id],
            'month' => '2026-06',
        ]);

        $this->assertSame(65, substr_count($septemberPage->getContent(), 'name="expense_ids[]"'));
        $junePage->assertSee('June rent share');
        $junePage->assertSee('value="'.$oldExpense->id.'"', false);
        $tallyResponse->assertRedirect(route('income.show', [$income, 'month' => '2026-06']));
        $this->assertDatabaseHas('income_expense_tallies', [
            'income_id' => $income->id,
            'expense_id' => $oldExpense->id,
            'allocated_amount' => 120,
        ]);
    }

    public function test_fully_tallied_expenses_are_hidden_until_the_toggle_is_on(): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user);
        $otherIncome = $this->createIncome($user, 500, '2026-09-01', 'Gift money');
        $this->createExpense($user, '2026-09-03', 'Unpaid snacks');
        $partlyTallied = $this->createExpense($user, '2026-09-04', 'Partly tallied fuel', 100);
        $fullyTallied = $this->createExpense($user, '2026-09-05', 'Fully tallied books', 80);
        $this->tally($user, $otherIncome, $partlyTallied, 30);
        $this->tally($user, $otherIncome, $fullyTallied, 80);

        $defaultPage = $this->actingAs($user)->get(route('income.show', $income));
        $togglePage = $this->actingAs($user)->get(route('income.show', [$income, 'show_tallied' => 1]));

        $defaultPage->assertSee('Unpaid snacks');
        $defaultPage->assertSee('Partly tallied fuel');
        $defaultPage->assertSee('₹70.00 remaining');
        $defaultPage->assertDontSee('Fully tallied books');
        $defaultPage->assertSee('Show fully tallied (1)');
        $defaultPage->assertSee('aria-checked="false"', false);

        $togglePage->assertSee('Unpaid snacks');
        $togglePage->assertSee('Partly tallied fuel');
        $togglePage->assertSee('Fully tallied books');
        $togglePage->assertSee('Fully tallied under Gift money (₹80.00)');
        $togglePage->assertSee('aria-checked="true"', false);
        $togglePage->assertSeeInOrder(['value="'.$fullyTallied->id.'"', 'disabled', 'aria-label="Already tallied expense"'], false);
        $this->assertSame(2, substr_count($togglePage->getContent(), 'name="expense_ids[]"'));
    }

    public function test_fully_tallied_expense_cannot_be_tallied_again(): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user);
        $otherIncome = $this->createIncome($user, 500, '2026-09-01', 'Gift money');
        $fullyTallied = $this->createExpense($user, '2026-09-05', 'Fully tallied books', 80);
        $this->tally($user, $otherIncome, $fullyTallied, 80);

        $response = $this->actingAs($user)->post(route('income.tally', $income), [
            'expense_ids' => [$fullyTallied->id],
            'confirm_partial' => 1,
        ]);

        $response->assertInvalid(['expense_ids' => 'A selected expense does not have enough untallied amount available.']);
        $this->assertDatabaseMissing('income_expense_tallies', ['income_id' => $income->id]);
    }

    public function test_tally_page_counts_unpaid_expenses_in_other_months_with_links_to_them(): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user);
        $otherIncome = $this->createIncome($user, 500, '2026-07-01', 'Gift money');
        $this->createExpense($user, '2026-08-02', 'August unpaid one');
        $this->createExpense($user, '2026-08-09', 'August unpaid two');
        $this->tally($user, $otherIncome, $this->createExpense($user, '2026-08-12', 'August fully tallied', 40), 40);
        $this->tally($user, $otherIncome, $this->createExpense($user, '2026-07-05', 'July partly tallied', 90), 30);
        $this->tally($user, $income, $this->createExpense($user, '2026-10-02', 'October tallied by this income', 90), 30);
        $this->createExpense($user, '2026-05-05', 'May cash expense', 25, 'Cash');

        $response = $this->actingAs($user)->get(route('income.show', $income));

        $response->assertSee('3 expenses in other months still have an unpaid amount:');
        $response->assertSeeInOrder(['Jul 2026 · 1', 'Aug 2026 · 2']);
        $response->assertDontSee('Oct 2026 · 1');
        $response->assertDontSee('May 2026 · 1');
        $response->assertSee('month=2026-07', false);
    }

    public function test_selected_expenses_from_other_months_stay_selected_and_ineligible_ones_are_dropped(): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user);
        $otherIncome = $this->createIncome($user, 500, '2026-07-01', 'Gift money');
        $augustExpense = $this->createExpense($user, '2026-08-20', 'August groceries', 60);
        $fullyTallied = $this->createExpense($user, '2026-07-10', 'July fully tallied', 40);
        $this->tally($user, $otherIncome, $fullyTallied, 40);
        $otherUsersExpense = $this->createExpense($this->createAdminUser(), '2026-08-21', 'Someone else lunch');
        $septemberExpense = $this->createExpense($user, '2026-09-03', 'September groceries');
        $selected = implode(',', [$augustExpense->id, $fullyTallied->id, $otherUsersExpense->id, $septemberExpense->id]);

        $response = $this->actingAs($user)->get(route('income.show', [$income, 'month' => '2026-09', 'selected' => $selected]));

        $response->assertSeeInOrder(['Selected from other months (1)', 'value="'.$augustExpense->id.'"', 'checked', 'August groceries'], false);
        $response->assertDontSee('July fully tallied');
        $response->assertDontSee('Someone else lunch');
        $response->assertSeeInOrder(['value="'.$septemberExpense->id.'"', 'checked'], false);
    }

    public function test_tally_across_months_covers_the_oldest_first_and_returns_to_the_viewed_month(): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user, 100);
        $septemberExpense = $this->createExpense($user, '2026-09-03', 'September groceries', 70);
        $augustExpense = $this->createExpense($user, '2026-08-20', 'August groceries', 60);

        $response = $this->actingAs($user)->post(route('income.tally', $income), [
            'expense_ids' => [$septemberExpense->id, $augustExpense->id],
            'confirm_partial' => 1,
            'month' => '2026-09',
        ]);

        $response->assertRedirect(route('income.show', [$income, 'month' => '2026-09']));
        $response->assertSessionHas('success', '2 expenses tallied against income for ₹100.00.');
        $this->assertSame(0.0, $augustExpense->fresh()->untalliedAmount());
        $this->assertSame(30.0, $septemberExpense->fresh()->untalliedAmount());
    }

    public function test_failed_tally_keeps_the_selection_from_other_months(): void
    {
        $user = $this->createAdminUser();
        $income = $this->createIncome($user, 100);
        $septemberExpense = $this->createExpense($user, '2026-09-03', 'September groceries', 70);
        $augustExpense = $this->createExpense($user, '2026-08-20', 'August groceries', 60);
        $pageUrl = route('income.show', [$income, 'month' => '2026-09']);

        $response = $this->actingAs($user)->from($pageUrl)->followingRedirects()->post(route('income.tally', $income), [
            'expense_ids' => [$septemberExpense->id, $augustExpense->id],
            'month' => '2026-09',
        ]);

        $response->assertSee('The selected amount is greater than the income amount still available to tally.');
        $response->assertSeeInOrder(['Selected from other months (1)', 'value="'.$augustExpense->id.'"', 'checked'], false);
        $response->assertSeeInOrder(['value="'.$septemberExpense->id.'"', 'checked'], false);
        $this->assertDatabaseMissing('income_expense_tallies', ['income_id' => $income->id]);
    }
}
