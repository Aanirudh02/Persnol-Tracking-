<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Friend;
use App\Models\FriendTransaction;
use App\Models\Role;
use App\Models\ScooterTrip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Maps\MapProviderInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartDrilldownAndSecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'Admin'], ['display_name' => 'Admin']);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    private function createExpense(User $user, ExpenseCategory $category, float $amount, string $date): Expense
    {
        return Expense::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'amount' => $amount,
            'gst_amount' => 0,
            'date' => $date,
            'description' => $category->name.' spend',
            'payment_method' => 'UPI',
            'paid_by' => 'Me',
            'paid_by_type' => 'me',
        ]);
    }

    public function test_chart_data_returns_bucket_ranges_and_totals(): void
    {
        $user = $this->createAdminUser();
        $food = ExpenseCategory::create(['name' => 'Food', 'user_id' => $user->id]);
        $this->createExpense($user, $food, 120, Carbon::today()->toDateString());

        $this->actingAs($user)->getJson(route('dashboard.chart-data', ['range' => '7d']))
            ->assertOk()
            ->assertJsonCount(7, 'ranges')
            ->assertJsonPath('ranges.6.start', Carbon::today()->toDateString())
            ->assertJsonPath('data.6', 120);
    }

    public function test_clicking_a_chart_bar_returns_category_breakdown(): void
    {
        $user = $this->createAdminUser();
        $food = ExpenseCategory::create(['name' => 'Food', 'user_id' => $user->id, 'color' => '#ff0000']);
        $travel = ExpenseCategory::create(['name' => 'Travel', 'user_id' => $user->id]);
        $this->createExpense($user, $food, 100, '2026-10-05');
        $this->createExpense($user, $food, 50, '2026-10-05');
        $this->createExpense($user, $travel, 150, '2026-10-05');
        $this->createExpense($user, $travel, 999, '2026-10-06');

        $this->actingAs($user)->getJson(route('dashboard.chart-breakdown', ['start' => '2026-10-05', 'end' => '2026-10-05']))
            ->assertOk()
            ->assertJsonPath('total', 300)
            ->assertJsonCount(2, 'categories')
            ->assertJsonFragment(['name' => 'Food', 'count' => 2, 'total' => 150, 'percentage' => 50]);
    }

    public function test_analytics_day_drilldown_includes_categories(): void
    {
        $user = $this->createAdminUser();
        $food = ExpenseCategory::create(['name' => 'Food', 'user_id' => $user->id]);
        $travel = ExpenseCategory::create(['name' => 'Travel', 'user_id' => $user->id]);
        $this->createExpense($user, $food, 40, '2026-10-05');
        $this->createExpense($user, $travel, 60, '2026-10-05');

        $this->actingAs($user)->getJson(route('analytics.drilldown', ['date' => '2026-10-05']))
            ->assertOk()
            ->assertJsonCount(2, 'categories')
            ->assertJsonPath('categories.0.name', 'Travel');
    }

    public function test_search_does_not_return_other_users_friend_transactions(): void
    {
        $user = $this->createAdminUser();
        $otherUser = User::factory()->create();
        $otherFriend = Friend::create(['user_id' => $otherUser->id, 'name' => 'Stranger']);
        FriendTransaction::create([
            'user_id' => $otherUser->id,
            'friend_id' => $otherFriend->id,
            'type' => 'paid_for_friend',
            'total_amount' => 500,
            'my_share' => 250,
            'friend_share' => 250,
            'description' => 'Secret dinner',
            'date' => '2026-10-05',
        ]);

        $response = $this->actingAs($user)->get(route('search', ['q' => 'Secret']))->assertOk();

        $this->assertCount(0, $response->viewData('results')['friend_transactions']);
    }

    public function test_user_cannot_archive_another_users_or_shared_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $othersCategory = ExpenseCategory::create(['name' => 'Theirs', 'user_id' => $otherUser->id]);
        $sharedCategory = ExpenseCategory::create(['name' => 'Shared', 'user_id' => null]);
        $ownCategory = ExpenseCategory::create(['name' => 'Mine', 'user_id' => $user->id]);

        $this->actingAs($user)->post(route('categories.archive', ['type' => 'expense', 'id' => $othersCategory->id]))->assertForbidden();
        $this->actingAs($user)->post(route('categories.archive', ['type' => 'expense', 'id' => $sharedCategory->id]))->assertForbidden();
        $this->actingAs($user)->post(route('categories.archive', ['type' => 'expense', 'id' => $ownCategory->id]))->assertRedirect();

        $this->assertFalse((bool) $othersCategory->fresh()->is_archived);
        $this->assertFalse((bool) $sharedCategory->fresh()->is_archived);
        $this->assertTrue((bool) $ownCategory->fresh()->is_archived);
    }

    public function test_storage_route_blocks_path_traversal(): void
    {
        $this->get('/storage/..%2F..%2F.env')->assertNotFound();
        $this->get('/storage/../../.env')->assertNotFound();
    }

    public function test_storage_fallback_serves_real_public_files(): void
    {
        $directory = storage_path('app/public');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $fileName = 'fallback-test-'.uniqid().'.txt';
        file_put_contents($directory.DIRECTORY_SEPARATOR.$fileName, 'hello');

        try {
            $this->get('/storage/'.$fileName)->assertOk();
        } finally {
            unlink($directory.DIRECTORY_SEPARATOR.$fileName);
        }
    }

    public function test_editing_a_trip_keeps_its_stops_in_the_distance(): void
    {
        $user = $this->createAdminUser();
        $vehicle = Vehicle::create(['user_id' => $user->id, 'name' => 'Scooter', 'is_default' => true, 'default_mileage_kmpl' => 40]);

        $maps = $this->mock(MapProviderInterface::class);
        $maps->shouldReceive('routeDistanceKm')
            ->once()
            ->withArgs(fn (array $waypoints) => count($waypoints) === 3)
            ->andReturn(12.0);

        $trip = ScooterTrip::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'title' => 'Ride',
            'date' => '2026-10-05',
            'from_label' => 'Home',
            'to_label' => 'Office',
            'status' => 'completed',
            'stops' => [['label' => 'Bakery', 'lat' => 11.02, 'lng' => 77.02]],
        ]);

        $this->actingAs($user)->put(route('scooter.update', $trip), [
            'from_label' => 'Home',
            'to_label' => 'Office',
            'start_latitude' => 11.01,
            'start_longitude' => 77.01,
            'end_latitude' => 11.03,
            'end_longitude' => 77.03,
            'vehicle_id' => $vehicle->id,
            'date' => '2026-10-05',
            'stops' => json_encode([['label' => 'Bakery', 'lat' => 11.02, 'lng' => 77.02]]),
        ])->assertRedirect(route('scooter.index'));

        $trip->refresh();
        $this->assertEquals(12.0, $trip->distance_km);
        $this->assertCount(1, $trip->stops);
    }
}
