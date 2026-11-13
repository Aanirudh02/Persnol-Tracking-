<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\FamilyMember;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Models\PersonalExpenseGroup;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PersonalExpenseAndArchivingTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_expense_saves_optional_gst_receipt_and_time(): void
    {
        $user = User::factory()->create();
        $category = PersonalExpenseCategory::create([
            'user_id' => $user->id,
            'name' => 'Shopping',
            'color' => '#ec4899',
        ]);
        $this->mock(CloudinaryService::class)
            ->shouldReceive('upload')
            ->once()
            ->andReturn('receipts/personal-test.jpg');

        $response = $this->actingAs($user)->post(route('personal-expenses.store'), [
            'category_id' => $category->id,
            'amount' => 100,
            'gst_amount' => 18,
            'date' => '2026-09-10',
            'time' => '10:15',
            'description' => 'Shopping with GST',
            'payment_method' => 'UPI',
            'receipt_image' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $response->assertRedirect(route('personal-expenses.index'));
        $expense = PersonalExpense::query()->where('description', 'Shopping with GST')->firstOrFail();
        $this->assertSame(18.0, (float) $expense->gst_amount);
        $this->assertSame(118.0, $expense->totalAmount());
        $this->assertSame('10:15', (string) $expense->time);
        $this->assertSame('receipts/personal-test.jpg', $expense->receipt_image);
    }

    public function test_personal_expense_totals_exclude_voluntary_spending_and_include_gst(): void
    {
        $user = User::factory()->create();
        $category = PersonalExpenseCategory::create([
            'user_id' => $user->id,
            'name' => 'Shopping',
            'color' => '#ec4899',
        ]);
        PersonalExpense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 100,
            'gst_amount' => 18,
            'date' => now()->toDateString(),
            'description' => 'Regular personal expense',
            'is_voluntary' => false,
        ]);
        PersonalExpense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 25,
            'gst_amount' => 5,
            'date' => now()->toDateString(),
            'description' => 'Voluntary personal expense',
            'is_voluntary' => true,
        ]);

        $response = $this->actingAs($user)->get(route('personal-expenses.index'));

        $response->assertOk();
        $this->assertSame(118.0, (float) $response->viewData('totalAmount'));
        $this->assertSame(118.0, (float) $response->viewData('monthAmount'));
        $this->assertSame(30.0, (float) $response->viewData('voluntaryAmount'));
    }

    public function test_personal_expenses_can_be_grouped_renamed_detached_and_ungrouped(): void
    {
        $user = User::factory()->create();
        $cat = PersonalExpenseCategory::create([
            'user_id' => $user->id,
            'name' => 'Family',
            'color' => '#8b5cf6',
        ]);

        $exp1 = PersonalExpense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 500.00,
            'date' => '2026-09-20',
            'description' => 'Dinner',
            'payment_method' => 'UPI',
            'done_by' => 'Me',
            'done_to' => 'Family',
        ]);

        $exp2 = PersonalExpense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 200.00,
            'date' => '2026-09-20',
            'description' => 'Dessert',
            'payment_method' => 'Cash',
            'done_by' => 'Me',
            'done_to' => 'Sister',
        ]);

        // 1. Group
        $response = $this->actingAs($user)->post(route('personal-expenses.group'), [
            'expense_ids' => [$exp1->id, $exp2->id],
            'name' => 'Dinner & Dessert Group',
        ]);
        $response->assertRedirect(route('personal-expenses.index'));

        $group = PersonalExpenseGroup::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Dinner & Dessert Group', $group->name);
        $this->assertSame(2, $group->personalExpenses()->count());

        // 2. Rename Group
        $response = $this->actingAs($user)->put(route('personal-expense-groups.update', $group), [
            'name' => 'Family Night Out',
        ]);
        $response->assertRedirect();
        $this->assertSame('Family Night Out', $group->fresh()->name);

        // 3. Detach one item (should dissolve group if remaining < 2)
        $response = $this->actingAs($user)->delete(route('personal-expense-groups.expenses.detach', [$group, $exp1]));
        $response->assertRedirect();
        $this->assertDatabaseMissing('personal_expense_groups', ['id' => $group->id]);
        $this->assertNull($exp1->fresh()->personal_expense_group_id);
        $this->assertNull($exp2->fresh()->personal_expense_group_id);
    }

    public function test_normal_and_personal_expenses_can_be_archived_and_restored(): void
    {
        $user = User::factory()->create();

        // Normal expense
        $normalExpense = Expense::create([
            'user_id' => $user->id,
            'amount' => 150.00,
            'date' => '2026-09-18',
            'description' => 'Test Normal Expense',
            'payment_method' => 'UPI',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);

        // Archive normal expense
        $response = $this->actingAs($user)->post(route('expenses.archive', $normalExpense));
        $response->assertRedirect();
        $this->assertTrue((bool) $normalExpense->fresh()->is_archived);

        // Restore normal expense
        $response = $this->actingAs($user)->post(route('expenses.restore', $normalExpense));
        $response->assertRedirect();
        $this->assertFalse((bool) $normalExpense->fresh()->is_archived);

        // Personal expense
        $cat = PersonalExpenseCategory::create([
            'user_id' => $user->id,
            'name' => 'Personal',
            'color' => '#3b82f6',
        ]);
        $personalExpense = PersonalExpense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 300.00,
            'date' => '2026-09-18',
            'description' => 'Test Personal Expense',
            'payment_method' => 'Cash',
            'done_by' => 'Me',
        ]);

        // Archive personal expense
        $response = $this->actingAs($user)->post(route('personal-expenses.archive', $personalExpense));
        $response->assertRedirect();
        $this->assertTrue((bool) $personalExpense->fresh()->is_archived);

        // Restore personal expense
        $response = $this->actingAs($user)->post(route('personal-expenses.restore', $personalExpense));
        $response->assertRedirect();
        $this->assertFalse((bool) $personalExpense->fresh()->is_archived);
    }

    public function test_family_member_crud(): void
    {
        $user = User::factory()->create();

        // Create
        $response = $this->actingAs($user)->post(route('family-members.store'), [
            'name' => 'Grandmother',
            'relationship' => 'Grandmother',
            'phone' => '9876543210',
            'details' => 'Family elder',
        ]);
        $response->assertRedirect(route('family-members.index'));
        $this->assertDatabaseHas('family_members', [
            'user_id' => $user->id,
            'name' => 'Grandmother',
        ]);

        $member = FamilyMember::where('name', 'Grandmother')->firstOrFail();

        // Update
        $response = $this->actingAs($user)->put(route('family-members.update', $member), [
            'name' => 'Grandmother Senior',
            'relationship' => 'Grandmother',
            'phone' => '9876543210',
            'details' => 'Family elder updated',
        ]);
        $response->assertRedirect(route('family-members.index'));
        $this->assertSame('Grandmother Senior', $member->fresh()->name);

        // Delete
        $response = $this->actingAs($user)->delete(route('family-members.destroy', $member));
        $response->assertRedirect(route('family-members.index'));
        $this->assertSoftDeleted($member);
    }

    public function test_personal_expense_filtering_by_date_range_and_done_to(): void
    {
        $user = User::factory()->create();
        $cat = PersonalExpenseCategory::create([
            'user_id' => $user->id,
            'name' => 'Shopping',
            'color' => '#ec4899',
        ]);

        // 1. Within range (01/10/2026 - 19/10/2026), done_to = Me (or null/self)
        $exp1 = PersonalExpense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 350.00,
            'date' => '2026-10-10',
            'description' => 'Shirt for Me',
            'payment_method' => 'UPI',
            'done_by' => 'Me',
            'done_to' => 'Me',
        ]);

        // 2. Within range, done_to is null (Self/None)
        $exp2 = PersonalExpense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 150.00,
            'date' => '2026-10-15',
            'description' => 'Coffee for Self',
            'payment_method' => 'Cash',
            'done_by' => 'Me',
            'done_to' => null,
        ]);

        // 3. Within range, done_to is Sister
        $exp3 = PersonalExpense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 500.00,
            'date' => '2026-10-12',
            'description' => 'Gift for Sister',
            'payment_method' => 'UPI',
            'done_by' => 'Me',
            'done_to' => 'Sister',
        ]);

        // 4. Outside range (25/10/2026), done_to = Me
        $exp4 = PersonalExpense::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 800.00,
            'date' => '2026-10-25',
            'description' => 'Future Shoes',
            'payment_method' => 'Card',
            'done_by' => 'Me',
            'done_to' => 'Me',
        ]);

        // Filter: from_date 2026-10-01 to 2026-10-19, done_to = Me
        $response = $this->actingAs($user)->get(route('personal-expenses.index', [
            'from_date' => '2026-10-01',
            'to_date' => '2026-10-19',
            'done_to' => 'Me',
        ]));
        $response->assertOk();

        $filteredExpenses = $response->viewData('expenses');
        $ids = $filteredExpenses->pluck('id')->all();

        $this->assertContains($exp1->id, $ids);
        $this->assertContains($exp2->id, $ids); // done_to null is considered Me / Self
        $this->assertNotContains($exp3->id, $ids); // Sister excluded
        $this->assertNotContains($exp4->id, $ids); // Outside date range excluded
    }
}
