<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InterestedResourceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_booking_actions_are_protected_by_shield_permissions(): void
    {
        $user = User::factory()->create();
        $booking = new Booking();

        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Booking::class));
        $this->assertFalse(Gate::forUser($user)->allows('create', Booking::class));
        $this->assertFalse(Gate::forUser($user)->allows('update', $booking));

        foreach (['view_any_booking', 'create_booking', 'update_booking'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));
        }

        $this->assertTrue(Gate::forUser($user)->allows('viewAny', Booking::class));
        $this->assertTrue(Gate::forUser($user)->allows('create', Booking::class));
        $this->assertTrue(Gate::forUser($user)->allows('update', $booking));
    }

    public function test_contact_and_amount_columns_use_safe_types(): void
    {
        $this->assertTrue(config('filament-shield.entities.custom_permissions'));
        $this->assertSame('string', Schema::getColumnType('interesteds', 'email'));
        $this->assertSame('string', Schema::getColumnType('interesteds', 'phone'));
        $this->assertSame('decimal', Schema::getColumnType('bookings', 'amount'));
    }
}
