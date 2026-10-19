<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileOwnerApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_profile_remains_compatible_when_owner_has_no_status(): void
    {
        $user = User::factory()->create(['owner_id' => null]);
        $owner = Owner::create([
            'first_name' => 'Perfil',
            'last_name' => 'Sin Estado',
            'email' => 'profile-'.uniqid().'@test.local',
            'user_id' => $user->id,
            'owner_status_id' => null,
        ]);

        $user->update(['owner_id' => $owner->id]);
        Sanctum::actingAs($user->fresh());

        $this->getJson('/api/profile/owner')
            ->assertOk()
            ->assertJsonPath('id', $owner->id)
            ->assertJsonPath('owner_status_id', 0)
            ->assertJsonPath('status.id', 0)
            ->assertJsonPath('status.name', 'Sin estado')
            ->assertJsonStructure([
                'status' => ['id', 'name', 'color', 'created_at', 'updated_at'],
            ]);
    }
}
