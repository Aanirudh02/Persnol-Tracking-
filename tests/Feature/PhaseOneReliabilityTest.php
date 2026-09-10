<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Friend;
use App\Models\FuelEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseOneReliabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_sets_remember_cookie_when_requested(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login.submit'), [
            'login' => $user->email,
            'password' => 'secret-password',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_friends_page_renders_without_friends(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)
            ->get(route('friends.index'))
            ->assertOk()
            ->assertSee('No friends added yet.');
    }

    public function test_settings_can_export_sanitized_json_and_csv_data(): void
    {
        $user = User::factory()->create([
            'email' => 'export@example.com',
            'password' => Hash::make('secret-password'),
            'is_active' => true,
        ]);
        Friend::query()->create(['user_id' => $user->id, 'name' => 'Export Friend', 'role' => 'Friend']);

        $jsonResponse = $this->actingAs($user)->get(route('settings.export.json'));
        $jsonResponse->assertDownload();
        $json = json_decode($jsonResponse->streamedContent(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('lifetracker-json-backup', $json['format']);
        $this->assertSame('export@example.com', $json['user']['email']);
        $this->assertArrayNotHasKey('password', $json['user']);
        $this->assertArrayHasKey('friends', $json['tables']);

        $csvResponse = $this->actingAs($user)->get(route('settings.export.csv', ['module' => 'friends']));
        $csvResponse->assertDownload();
        $this->assertStringContainsString('Export Friend', $csvResponse->streamedContent());
    }

    public function test_petrol_can_create_and_update_a_linked_expense(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('petrol.store'), [
            'date' => '2026-09-10',
            'time' => '09:30',
            'amount' => '500.00',
            'litres' => '4.50',
            'petrol_station' => 'Indian Oil',
            'payment_method' => 'UPI',
            'notes' => 'Full tank',
            'add_as_expense' => '1',
        ])->assertRedirect(route('petrol.index'));

        $fuel = FuelEntry::query()->with('expense')->firstOrFail();
        $this->assertNotNull($fuel->expense_id);
        $this->assertSame('500.00', $fuel->expense->amount);
        $this->assertSame('Petrol - Indian Oil', $fuel->expense->description);
        $this->assertSame('Full tank', $fuel->expense->notes);

        $this->actingAs($user)->put(route('petrol.update', $fuel), [
            'date' => '2026-09-10',
            'time' => '10:00',
            'amount' => '550.00',
            'litres' => '5.00',
            'petrol_station' => 'Bharat Petroleum',
            'payment_method' => 'Cash',
            'notes' => 'Updated receipt note',
        ])->assertRedirect(route('petrol.index'));

        $fuel->refresh()->load('expense');
        $this->assertSame('550.00', $fuel->expense->amount);
        $this->assertSame('Petrol - Bharat Petroleum', $fuel->expense->description);
        $this->assertSame('Updated receipt note', $fuel->expense->notes);
    }

    public function test_petrol_deletion_can_preserve_or_delete_linked_expense(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('petrol.store'), [
            'date' => '2026-09-10',
            'amount' => '300.00',
            'litres' => '2.50',
            'payment_method' => 'Cash',
            'add_as_expense' => '1',
        ]);

        $fuel = FuelEntry::query()->firstOrFail();
        $expense = Expense::query()->firstOrFail();

        $this->actingAs($user)->delete(route('petrol.destroy', $fuel), [
            'delete_linked_expense' => '0',
        ])->assertRedirect(route('petrol.index'));

        $this->assertSoftDeleted('fuel_entries', ['id' => $fuel->id]);
        $this->assertNotNull($expense->fresh());
        $this->assertNull(FuelEntry::withTrashed()->findOrFail($fuel->id)->expense_id);
    }

    public function test_petrol_deletion_can_delete_linked_expense(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('petrol.store'), [
            'date' => '2026-09-10',
            'amount' => '350.00',
            'litres' => '3.00',
            'payment_method' => 'UPI',
            'add_as_expense' => '1',
        ])->assertRedirect(route('petrol.index'));

        $fuel = FuelEntry::query()->with('expense')->firstOrFail();
        $expense = $fuel->expense;
        $this->assertNotNull($expense);

        $this->actingAs($user)->delete(route('petrol.destroy', $fuel), [
            'delete_linked_expense' => '1',
        ])->assertRedirect(route('petrol.index'));

        $this->assertSoftDeleted('fuel_entries', ['id' => $fuel->id]);
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_friends_page_renders_friend_data(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Friend::query()->create([
            'user_id' => $user->id,
            'name' => 'Test Friend',
            'role' => 'Friend',
        ]);

        $this->actingAs($user)
            ->get(route('friends.index'))
            ->assertOk()
            ->assertSee('Test Friend');
    }
}
