<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FoodEntry;
use App\Models\Friend;
use App\Models\Income;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedNotesVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_and_friend_notes_are_shown(): void
    {
        $user = $this->createMember();
        $income = Income::create([
            'user_id' => $user->id,
            'amount' => 500,
            'source' => 'Salary',
            'date' => now()->toDateString(),
            'payment_method' => 'UPI',
            'notes' => 'Advance for the trip',
        ]);
        Friend::create(['user_id' => $user->id, 'name' => 'Rajesh', 'notes' => 'Met at the office']);

        $this->actingAs($user)->get(route('income.index'))->assertSeeText('Advance for the trip');
        $this->actingAs($user)->get(route('income.show', $income))->assertSeeText('Advance for the trip');
        $this->actingAs($user)->get(route('friends.index'))->assertSeeText('Met at the office');
    }

    public function test_editing_food_keeps_and_shows_its_notes(): void
    {
        $user = $this->createMember();
        $food = FoodEntry::create([
            'user_id' => $user->id,
            'item_name' => 'Puff',
            'amount' => 20,
            'date' => now()->toDateString(),
            'notes' => 'From the corner bakery',
        ]);

        $this->actingAs($user)->get(route('food.index'))
            ->assertSee('name="notes" value="From the corner bakery"', false);

        $this->actingAs($user)->put(route('food.update', $food), [
            'item_name' => 'Egg puff',
            'amount' => 20,
            'date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertSame('From the corner bakery', $food->refresh()->notes);
    }

    public function test_settings_activity_shows_only_my_records_with_their_reason(): void
    {
        $user = $this->createMember();
        $otherUser = User::factory()->create();
        AuditLog::logChange('expense', 7, 'updated', reason: 'Forgot details', userId: $user->id);
        AuditLog::logChange('expense', 8, 'updated', reason: 'Someone else reason', userId: $otherUser->id);

        $this->actingAs($user)->get(route('settings.index'))
            ->assertOk()
            ->assertSeeText('Expense #7')
            ->assertSeeText('Forgot details')
            ->assertDontSeeText('Someone else reason');
    }

    /**
     * A non-admin with just the page permissions these tests visit.
     */
    private function createMember(): User
    {
        $role = Role::firstOrCreate(['name' => 'Member'], ['display_name' => 'Member']);

        foreach (['food.view', 'food.edit', 'settings.view'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], ['display_name' => $name, 'module' => 'test']);
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
