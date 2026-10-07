<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushDeviceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_register_and_remove_a_push_device(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $token = str_repeat('a', 120);

        $this->postJson('/api/push/devices', [
            'token' => $token,
            'platform' => 'android',
            'device_name' => 'Pixel de prueba',
        ])->assertOk();

        $this->assertDatabaseHas('fcm_devices', [
            'user_id' => $user->id,
            'token' => $token,
            'platform' => 'android',
        ]);

        $this->deleteJson('/api/push/devices', ['token' => $token])
            ->assertOk();

        $this->assertDatabaseMissing('fcm_devices', ['token' => $token]);
    }
}
