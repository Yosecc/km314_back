<?php

namespace Tests\Feature;

use App\Filament\Resources\InterestedLevelResource;
use App\Filament\Resources\InterestedLevelResource\Pages\ManageInterestedLevels;
use App\Models\InterestedLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InterestedLevelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_default_interest_levels_exist(): void
    {
        $this->assertSame(
            ['Bajo', 'Medio', 'Alto'],
            InterestedLevel::query()->orderBy('id')->pluck('name')->all(),
        );
    }

    public function test_interest_level_resource_respects_shield_and_lists_levels(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->assertFalse(InterestedLevelResource::canViewAny());

        $permission = Permission::firstOrCreate([
            'name' => 'view_any_interested::level',
            'guard_name' => 'web',
        ]);
        $user->givePermissionTo($permission);

        $this->assertTrue(InterestedLevelResource::canViewAny());
        $this->assertContains(
            InterestedLevelResource::class,
            Filament::getPanel('admin')->getResources(),
        );

        Livewire::test(ManageInterestedLevels::class)
            ->assertSuccessful()
            ->assertSee('Bajo')
            ->assertSee('Medio')
            ->assertSee('Alto');
    }
}
