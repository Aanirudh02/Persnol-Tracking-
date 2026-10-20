<?php

namespace Tests\Feature;

use App\Models\FuelEntry;
use App\Models\OdometerGroup;
use App\Models\OdometerReading;
use App\Models\Role;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OdometerAndPetrolLogTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $user = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'Admin'], ['display_name' => 'Admin']);
        $user->roles()->syncWithoutDetaching([$role->id]);

        return $user;
    }

    /**
     * @return array{0: Vehicle, 1: Vehicle}
     */
    private function createVehicles(User $user): array
    {
        return [
            Vehicle::create(['user_id' => $user->id, 'name' => 'Scooter', 'is_default' => true]),
            Vehicle::create(['user_id' => $user->id, 'name' => 'Bike', 'is_default' => false]),
        ];
    }

    private function recordReading(User $user, Vehicle $vehicle, string $type, float $km, string $date): void
    {
        $this->actingAs($user)->post(route('odometer.store'), [
            'reading_type' => $type,
            'odometer_km' => $km,
            'reading_date' => $date,
            'reading_time' => '09:00',
            'vehicle_id' => $vehicle->id,
        ])->assertSessionHasNoErrors();
    }

    public function test_each_vehicle_keeps_its_own_active_cycle(): void
    {
        $user = $this->createAdminUser();
        [$scooter, $bike] = $this->createVehicles($user);

        $this->recordReading($user, $scooter, 'source', 1000, '2026-10-01');
        $this->recordReading($user, $bike, 'source', 5000, '2026-10-01');

        $this->assertSame(2, OdometerGroup::where('user_id', $user->id)->where('status', 'active')->count());

        $this->recordReading($user, $scooter, 'intermediate', 1012.5, '2026-10-02');

        $scooterLeg = OdometerReading::where('vehicle_id', $scooter->id)->where('reading_type', 'intermediate')->firstOrFail();
        $this->assertEquals(12.5, (float) $scooterLeg->distance_km);
        $this->assertSame('active', OdometerGroup::activeFor($user->id, $bike->id)->status);
    }

    public function test_ending_leg_distance_is_measured_from_the_previous_leg_not_the_cycle_start(): void
    {
        $user = $this->createAdminUser();
        [$scooter] = $this->createVehicles($user);

        $this->recordReading($user, $scooter, 'source', 1000, '2026-10-01');
        $this->recordReading($user, $scooter, 'intermediate', 1020, '2026-10-02');
        $this->recordReading($user, $scooter, 'ending', 1050, '2026-10-03');

        $ending = OdometerReading::where('reading_type', 'ending')->firstOrFail();
        $this->assertEquals(30, (float) $ending->distance_km);
        $this->assertEquals(50, (float) $ending->group->total_km);
    }

    public function test_reading_lower_than_the_previous_one_is_rejected(): void
    {
        $user = $this->createAdminUser();
        [$scooter] = $this->createVehicles($user);

        $this->recordReading($user, $scooter, 'source', 1000, '2026-10-01');

        $this->actingAs($user)->post(route('odometer.store'), [
            'reading_type' => 'intermediate',
            'odometer_km' => 990,
            'reading_date' => '2026-10-02',
            'vehicle_id' => $scooter->id,
        ])->assertSessionHas('error');

        $this->assertSame(1, OdometerReading::count());
    }

    public function test_log_shows_previous_reading_of_the_same_vehicle(): void
    {
        $user = $this->createAdminUser();
        [$scooter, $bike] = $this->createVehicles($user);

        $this->recordReading($user, $scooter, 'source', 1000, '2026-10-01');
        $this->recordReading($user, $bike, 'source', 5000, '2026-10-02');
        $this->recordReading($user, $scooter, 'intermediate', 1015, '2026-10-03');

        $latestScooter = OdometerReading::withPreviousReading()
            ->where('vehicle_id', $scooter->id)
            ->where('reading_type', 'intermediate')
            ->firstOrFail();

        $this->assertEquals(1000, (float) $latestScooter->previous_odometer_km);
        $this->assertEquals(15, $latestScooter->distance_from_previous);

        $this->actingAs($user)->get(route('odometer.index', ['vehicle_id' => $scooter->id]))
            ->assertOk()
            ->assertSee('Odometer Log')
            ->assertSee('+15.0 km');
    }

    public function test_petrol_log_measures_distance_per_vehicle(): void
    {
        $user = $this->createAdminUser();
        [$scooter, $bike] = $this->createVehicles($user);

        $fill = fn (Vehicle $vehicle, string $date, int $odometer, float $litres) => FuelEntry::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'date' => $date,
            'amount' => 500,
            'litres' => $litres,
            'price_per_litre' => round(500 / $litres, 2),
            'odometer' => $odometer,
            'payment_method' => 'UPI',
        ]);

        $fill($scooter, '2026-10-01', 1000, 5);
        $fill($bike, '2026-10-02', 9000, 5);
        $scooterSecond = $fill($scooter, '2026-10-03', 1200, 4);

        $response = $this->actingAs($user)->get(route('petrol.index'))->assertOk();
        $fillLog = $response->viewData('fillLog');

        $this->assertSame(1000, $fillLog[$scooterSecond->id]['previous_odometer']);
        $this->assertSame(200, $fillLog[$scooterSecond->id]['distance']);
        $this->assertEquals(50.0, $fillLog[$scooterSecond->id]['mileage']);
    }

    public function test_petrol_fill_with_lower_odometer_than_previous_fill_is_rejected(): void
    {
        $user = $this->createAdminUser();
        [$scooter] = $this->createVehicles($user);

        FuelEntry::create([
            'user_id' => $user->id,
            'vehicle_id' => $scooter->id,
            'date' => '2026-10-01',
            'amount' => 500,
            'litres' => 5,
            'price_per_litre' => 100,
            'odometer' => 1000,
            'payment_method' => 'UPI',
        ]);

        $this->actingAs($user)->post(route('petrol.store'), [
            'vehicle_id' => $scooter->id,
            'date' => '2026-10-05',
            'amount' => 300,
            'litres' => 3,
            'odometer' => 900,
            'payment_method' => 'UPI',
        ])->assertSessionHas('error');

        $this->assertSame(1, FuelEntry::count());
    }

    public function test_user_can_edit_completed_cycle_and_add_petrol_entry_later(): void
    {
        $user = $this->createAdminUser();
        [$scooter] = $this->createVehicles($user);

        // 1. Start cycle and end cycle without petrol entry
        $this->recordReading($user, $scooter, 'source', 1000, '2026-10-01');
        $this->recordReading($user, $scooter, 'ending', 1200, '2026-10-05');

        $group = OdometerGroup::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('completed', $group->status);
        $this->assertEquals(200, (float) $group->total_km);
        $this->assertNull($group->calculated_mileage);

        // 2. Add petrol entry later via updateGroup (create_new mode)
        $response = $this->actingAs($user)->put(route('odometer.groups.update', $group), [
            'title' => $group->title,
            'fuel_action' => 'create_new',
            'new_fuel_litres' => 4.0,
            'new_fuel_amount' => 400.0,
            'new_fuel_date' => '2026-10-05',
            'new_fuel_payment_method' => 'UPI',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $group->refresh();
        $this->assertNotNull($group->end_fuel_entry_id);
        $this->assertEquals(4.0, (float) $group->total_litres);
        $this->assertEquals(400.0, (float) $group->total_fuel_cost);
        $this->assertEquals(50.0, (float) $group->calculated_mileage); // 200 km / 4 L = 50 km/L
        $this->assertEquals(2.0, (float) $group->cost_per_km); // 400 / 200 = 2 Rs/km
    }

    public function test_editing_petrol_entry_recalculates_linked_odometer_cycle(): void
    {
        $user = $this->createAdminUser();
        [$scooter] = $this->createVehicles($user);

        $this->recordReading($user, $scooter, 'source', 1000, '2026-10-01');
        $this->recordReading($user, $scooter, 'ending', 1200, '2026-10-05');

        $group = OdometerGroup::where('user_id', $user->id)->firstOrFail();

        $fuel = FuelEntry::create([
            'user_id' => $user->id,
            'vehicle_id' => $scooter->id,
            'date' => '2026-10-05',
            'amount' => 500,
            'litres' => 5,
            'price_per_litre' => 100,
            'payment_method' => 'UPI',
        ]);

        $group->update(['end_fuel_entry_id' => $fuel->id]);
        $group->recalculateSummary();
        $this->assertEquals(40.0, (float) $group->calculated_mileage); // 200 / 5 = 40 km/L

        // Now edit the fuel entry via PetrolController::update
        $this->actingAs($user)->put(route('petrol.update', $fuel), [
            'vehicle_id' => $scooter->id,
            'date' => '2026-10-05',
            'amount' => 400,
            'litres' => 4,
            'payment_method' => 'UPI',
        ])->assertRedirect();

        $group->refresh();
        $this->assertEquals(50.0, (float) $group->calculated_mileage); // 200 / 4 = 50 km/L
    }
}
