<?php

namespace Tests\Feature;

use App\Models\CreditDebt;
use App\Models\CreditDebtPayment;
use App\Models\DailyRecord;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FoodEntry;
use App\Models\Friend;
use App\Models\Income;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditLinkSyncAndDataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'Admin'], ['display_name' => 'Admin']);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function createCredit(User $user, string $type = 'credit', float $amount = 200, bool $linkAsExpense = false): CreditDebt
    {
        $friend = Friend::firstOrCreate(['user_id' => $user->id, 'name' => 'Sandeep'], ['role' => 'Friend']);

        $this->actingAs($user)->post(route('credits.store'), [
            'type' => $type,
            'friend_id' => $friend->id,
            'amount' => $amount,
            'date' => '2026-10-01',
            'payment_method' => 'UPI',
            'description' => 'Lunch',
            'link_as_expense' => $linkAsExpense ? 1 : 0,
        ])->assertSessionHasNoErrors();

        return CreditDebt::latest('id')->firstOrFail();
    }

    // ── H4: "your spend" ──────────────────────────────────────────────

    public function test_split_row_that_already_stores_my_share_is_not_reduced_again(): void
    {
        $user = $this->createAdminUser();
        $netRow = Expense::create([
            'user_id' => $user->id,
            'amount' => 20,
            'gst_amount' => 0,
            'date' => '2026-10-01',
            'description' => 'Bakery',
            'payment_method' => 'UPI',
            'paid_by' => 'Split',
            'paid_by_type' => 'split',
            'paid_by_friend_id' => null,
            'notes' => 'Total bill: ₹90.00 (You paid: ₹20.00, Friend: ₹70.00)',
        ]);
        $netRow->friendSplits()->create([
            'user_id' => $user->id,
            'friend_id' => Friend::create(['user_id' => $user->id, 'name' => 'Ravi'])->id,
            'description' => 'Bakery',
            'date' => '2026-10-01',
            'total_amount' => 90,
            'friend_share' => 70,
            'my_share' => 20,
            'paid_by_friend_amount' => 70,
            'paid_by_me_amount' => 20,
        ]);

        $this->assertEquals(20.0, $netRow->fresh()->myShareAmount());
    }

    public function test_old_full_bill_split_row_still_subtracts_friend_payment_once(): void
    {
        $user = $this->createAdminUser();
        $legacyRow = Expense::create([
            'user_id' => $user->id,
            'amount' => 90,
            'gst_amount' => 0,
            'date' => '2026-10-01',
            'description' => 'Bakery',
            'payment_method' => 'UPI',
            'paid_by' => 'Split',
            'paid_by_type' => 'split',
            'split_my_share' => 20,
            'split_friend_share' => 70,
        ]);
        $legacyRow->friendSplits()->create([
            'user_id' => $user->id,
            'friend_id' => Friend::create(['user_id' => $user->id, 'name' => 'Ravi'])->id,
            'description' => 'Bakery',
            'date' => '2026-10-01',
            'total_amount' => 90,
            'friend_share' => 70,
            'my_share' => 20,
            'paid_by_friend_amount' => 70,
            'paid_by_me_amount' => 20,
        ]);

        $this->assertEquals(20.0, $legacyRow->fresh()->myShareAmount());
    }

    // ── H5: closing a credit that was already expensed ────────────────

    public function test_closing_a_credit_already_linked_as_expense_does_not_expense_it_twice(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'credit', 200, linkAsExpense: true);

        $this->assertEquals(200.0, $credit->expensedAmount());

        $this->actingAs($user)->post(route('credits.close', $credit), ['payment_method' => 'Cash'])->assertSessionHasNoErrors();

        $credit->refresh();
        $this->assertSame('fully_paid', $credit->status);
        $this->assertEquals(200.0, (float) $credit->amount_paid);
        $this->assertSame(1, Expense::where('credit_debt_id', $credit->id)->count());
        $this->assertEquals(200.0, $credit->expensedAmount());
    }

    public function test_partial_expense_then_close_files_only_the_rest(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'credit', 200);

        $this->actingAs($user)->post(route('credits.record-as-expense', $credit), [
            'amount' => 50,
            'date' => '2026-10-02',
            'payment_method' => 'UPI',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post(route('credits.close', $credit), ['payment_method' => 'Cash']);

        $this->assertEquals(200.0, $credit->fresh()->expensedAmount());
        $this->assertSame(2, Expense::where('credit_debt_id', $credit->id)->count());
    }

    public function test_cannot_file_more_than_the_unexpensed_amount(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'credit', 200, linkAsExpense: true);

        $this->actingAs($user)->post(route('credits.record-as-expense', $credit), [
            'amount' => 50,
            'date' => '2026-10-02',
            'payment_method' => 'UPI',
        ])->assertSessionHas('error');

        $this->assertSame(1, Expense::where('credit_debt_id', $credit->id)->count());
    }

    // ── H6: editing a credit and its linked records ──────────────────

    private function updateCredit(User $user, CreditDebt $credit, array $changes, array $syncLinked): void
    {
        $this->actingAs($user)->put(route('credits.update', $credit), array_merge([
            'type' => $credit->type,
            'friend_id' => $credit->friend_id,
            'amount' => (float) $credit->amount,
            'amount_paid' => (float) $credit->amount_paid,
            'date' => $credit->date->toDateString(),
            'payment_method' => $credit->payment_method,
            'description' => $credit->description,
            'status' => $credit->status,
        ], $changes, ['sync_linked' => $syncLinked]))->assertSessionHasNoErrors();
    }

    public function test_changing_payment_method_updates_linked_records_only_when_confirmed(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'debt', 300);
        $this->actingAs($user)->post(route('credits.record-as-income', $credit), [
            'amount' => 100,
            'date' => '2026-10-03',
            'payment_method' => 'UPI',
        ]);
        $income = Income::where('credit_debt_id', $credit->id)->firstOrFail();

        // "Only this record"
        $this->updateCredit($user, $credit->fresh(), ['payment_method' => 'Cash'], []);
        $this->assertSame('UPI', $income->fresh()->payment_method);
        $this->assertSame('UPI', CreditDebtPayment::where('income_id', $income->id)->value('payment_method'));

        // "Update both places" (credit is Cash now, linked still UPI → revert to UPI first, then change to Card)
        $this->updateCredit($user, $credit->fresh(), ['payment_method' => 'UPI'], []);
        $this->updateCredit($user, $credit->fresh(), ['payment_method' => 'Card'], ['payment_method']);
        $this->assertSame('Card', $income->fresh()->payment_method);
        $this->assertSame('Card', CreditDebtPayment::where('income_id', $income->id)->value('payment_method'));
    }

    public function test_changing_credit_total_never_overwrites_a_partial_repayment_amount(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'debt', 300);
        $this->actingAs($user)->post(route('credits.record-as-income', $credit), [
            'amount' => 100,
            'date' => '2026-10-03',
            'payment_method' => 'UPI',
        ]);

        $this->updateCredit($user, $credit->fresh(), ['amount' => 350, 'description' => 'Edited'], ['amount']);

        $this->assertEquals(100.0, (float) Income::where('credit_debt_id', $credit->id)->value('amount'));
        $this->assertEquals(350.0, (float) $credit->fresh()->amount);
    }

    public function test_changing_credit_total_moves_a_whole_amount_linked_expense_when_confirmed(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'credit', 200, linkAsExpense: true);

        $this->updateCredit($user, $credit->fresh(), ['amount' => 250], ['amount']);

        $this->assertEquals(250.0, (float) Expense::where('credit_debt_id', $credit->id)->value('amount'));
    }

    public function test_editing_a_linked_expense_can_update_the_credit_too(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'credit', 200);
        $this->actingAs($user)->post(route('credits.record-as-expense', $credit), [
            'amount' => 80,
            'date' => '2026-10-02',
            'payment_method' => 'UPI',
        ]);
        $expense = Expense::where('credit_debt_id', $credit->id)->firstOrFail();

        $this->actingAs($user)->put(route('expenses.update', $expense), [
            'amount' => 90,
            'gst_amount' => 0,
            'category_id' => $expense->category_id,
            'date' => '2026-10-02',
            'description' => $expense->description,
            'payment_method' => 'Cash',
            'sync_linked' => ['payment_method', 'amount'],
        ])->assertSessionHasNoErrors();

        $payment = CreditDebtPayment::where('expense_id', $expense->id)->firstOrFail();
        $this->assertSame('Cash', $payment->payment_method);
        $this->assertEquals(90.0, (float) $payment->amount);
        $this->assertEquals(90.0, (float) $credit->fresh()->amount_paid);
    }

    public function test_edit_pages_show_the_linked_records_prompt(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'credit', 200, linkAsExpense: true);
        $expense = Expense::where('credit_debt_id', $credit->id)->firstOrFail();
        $debt = $this->createCredit($user, 'debt', 300);
        $this->actingAs($user)->post(route('credits.record-as-income', $debt), [
            'amount' => 100,
            'date' => '2026-10-03',
            'payment_method' => 'UPI',
        ]);
        $income = Income::where('credit_debt_id', $debt->id)->firstOrFail();

        $this->actingAs($user)->get(route('credits.edit', $credit))->assertOk()->assertSee('Also update linked expenses');
        $this->actingAs($user)->get(route('expenses.edit', $expense))->assertOk()->assertSee('Also update the linked credit');
        $this->actingAs($user)->get(route('income.edit', $income))->assertOk()->assertSee('Also update the linked debt');
        $this->actingAs($user)->get(route('credits.index', ['type' => 'credit']))->assertOk();
        $this->actingAs($user)->get(route('personal-expenses.index'))->assertOk();
        $this->actingAs($user)->get(route('food.index'))->assertOk();
        $this->actingAs($user)->get(route('settings.index'))->assertOk();
    }

    // ── M18: deleting a credit with linked records ────────────────────

    public function test_deleting_a_debt_with_linked_records_deletes_its_income_too(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'debt', 300);
        $this->actingAs($user)->post(route('credits.record-as-income', $credit), [
            'amount' => 100,
            'date' => '2026-10-03',
            'payment_method' => 'UPI',
        ]);

        $this->actingAs($user)->delete(route('credits.destroy', $credit), ['delete_linked_expense' => 1]);

        $this->assertSame(0, Income::count());
        $this->assertSame(1, Income::withTrashed()->count());
    }

    // ── M17: food entries and their auto-created expense ─────────────

    public function test_food_auto_expense_follows_its_entries(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user)->post(route('food.store'), [
            'items' => [['item_name' => 'Tea', 'amount' => 20], ['item_name' => 'Bun', 'amount' => 30]],
            'date' => '2026-10-05',
            'expense_mode' => 'separate',
            'payment_method' => 'Cash',
        ])->assertSessionHasNoErrors();

        $expense = Expense::firstOrFail();
        $this->assertEquals(50.0, $expense->totalAmount());

        $tea = FoodEntry::where('item_name', 'Tea')->firstOrFail();
        $this->actingAs($user)->put(route('food.update', $tea), [
            'item_name' => 'Tea',
            'amount' => 25,
            'date' => '2026-10-05',
            'parent_expense_id' => $expense->id,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(55.0, $expense->fresh()->totalAmount());

        FoodEntry::all()->each(fn (FoodEntry $entry) => $this->actingAs($user)->delete(route('food.destroy', $entry)));
        $this->assertSoftDeleted($expense);
    }

    // ── M24: GET requests must not write ──────────────────────────────

    public function test_viewing_pages_does_not_create_records(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get(route('dashboard', ['date' => '2026-01-15']))->assertOk();
        $this->actingAs($user)->get(route('petrol.index'))->assertOk();

        $this->assertSame(0, DailyRecord::count());
        $this->assertSame(0, Vehicle::count());
    }

    public function test_dismissing_a_prompt_requires_post(): void
    {
        $user = $this->createAdminUser();

        $this->actingAs($user)->get('/daily/dismiss/wakeup')->assertMethodNotAllowed();
        $this->actingAs($user)->post(route('daily.dismiss', ['type' => 'wakeup']))->assertRedirect();
        $this->assertTrue((bool) DailyRecord::firstOrFail()->wake_up_prompt_dismissed);
    }

    // ── Migration backfill of links for data saved before the fix ─────

    public function test_migration_backfills_links_from_old_link_columns_and_notes(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'credit', 200);
        $this->actingAs($user)->post(route('credits.record-as-expense', $credit), [
            'amount' => 50,
            'date' => '2026-10-02',
            'payment_method' => 'UPI',
        ]);
        $this->actingAs($user)->post(route('credits.close', $credit), ['payment_method' => 'Cash']);

        // Simulate rows written before the link columns existed
        Expense::query()->update(['credit_debt_id' => null]);
        CreditDebtPayment::query()->update(['expense_id' => null]);

        $migration = require database_path('migrations/2026_10_07_131936_add_credit_debt_links_to_finance_records.php');
        $backfill = fn (string $method) => (new \ReflectionMethod($migration, $method))->invoke($migration);
        $backfill('backfillRecordLinks');
        $backfill('backfillPaymentLinks');

        $this->assertSame(2, Expense::where('credit_debt_id', $credit->id)->count());
        $this->assertSame(2, CreditDebtPayment::whereNotNull('expense_id')->count());
    }

    // ── Repair command for data saved before the fix ─────────────────

    public function test_repair_command_removes_the_duplicate_closing_expense(): void
    {
        $user = $this->createAdminUser();
        $credit = $this->createCredit($user, 'credit', 200, linkAsExpense: true);
        $category = ExpenseCategory::create(['name' => 'Debt Repayment', 'user_id' => null]);
        // What the old "Close" did: a second full expense for the same credit
        Expense::create([
            'user_id' => $user->id,
            'credit_debt_id' => $credit->id,
            'category_id' => $category->id,
            'amount' => 200,
            'gst_amount' => 0,
            'date' => '2026-10-05',
            'description' => 'Credit Repaid to Sandeep (Credit #'.$credit->id.' closed)',
            'payment_method' => 'Cash',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);

        $this->artisan('finance:repair-credit-links')->assertSuccessful();
        $this->assertEquals(400.0, $credit->fresh()->expensedAmount());

        $this->artisan('finance:repair-credit-links', ['--fix' => true])->assertSuccessful();
        $this->assertEquals(200.0, $credit->fresh()->expensedAmount());
    }
}
