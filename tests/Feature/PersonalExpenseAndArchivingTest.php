<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\FamilyMember;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Models\PersonalExpenseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalExpenseAndArchivingTest extends TestCase
{
    use RefreshDatabase;

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
}
