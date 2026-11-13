<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PersonalExpense;
use App\Models\PersonalExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_category_with_expenses_cannot_be_deleted_but_unused_one_can(): void
    {
        $user = User::factory()->create();
        $usedCategory = ExpenseCategory::create(['user_id' => $user->id, 'name' => 'Rent']);
        $unusedCategory = ExpenseCategory::create(['user_id' => $user->id, 'name' => 'Spare']);
        $expense = Expense::query()->create([
            'user_id' => $user->id,
            'category_id' => $usedCategory->id,
            'amount' => 500,
            'date' => '2026-11-01',
            'description' => 'November rent',
            'payment_method' => 'Cash',
        ]);
        $expense->delete();

        $this->actingAs($user)
            ->delete(route('categories.expense.destroy', $usedCategory))
            ->assertSessionHas('error');
        $this->assertModelExists($usedCategory);

        $this->actingAs($user)
            ->delete(route('categories.expense.destroy', $unusedCategory))
            ->assertSessionHas('success');
        $this->assertModelMissing($unusedCategory);
    }

    public function test_expense_category_can_be_renamed_by_owner_only_with_unique_name(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = ExpenseCategory::create(['user_id' => $user->id, 'name' => 'Rent']);
        ExpenseCategory::create(['user_id' => $user->id, 'name' => 'Bills']);

        $this->actingAs($user)
            ->put(route('categories.expense.update', $category), ['name' => 'Bills'])
            ->assertSessionHasErrors('name');

        $this->actingAs($user)
            ->put(route('categories.expense.update', $category), ['name' => 'House Rent', 'color' => '#111111'])
            ->assertSessionHas('success');
        $this->assertSame('House Rent', $category->fresh()->name);

        $this->actingAs($otherUser)
            ->put(route('categories.expense.update', $category), ['name' => 'Hijack'])
            ->assertForbidden();
    }

    public function test_personal_category_with_expenses_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $category = PersonalExpenseCategory::create(['user_id' => $user->id, 'name' => 'Gifts']);
        PersonalExpense::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => 200,
            'date' => '2026-11-01',
        ]);

        $this->actingAs($user)
            ->delete(route('categories.personal.destroy', $category))
            ->assertSessionHas('error');
        $this->assertModelExists($category);
    }
}
