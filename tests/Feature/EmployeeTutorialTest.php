<?php

namespace Tests\Feature;

use App\Filament\Resources\EmployeeResource\Pages\ManageEmployees;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeTutorialTest extends TestCase
{
    use DatabaseTransactions;

    public function test_employee_tutorial_is_shown_once_and_can_be_reopened(): void
    {
        $user = User::factory()->create();

        foreach (['view_any_employee', 'create_employee'] as $permissionName) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        $this->actingAs($user);

        Livewire::test(ManageEmployees::class)
            ->assertSet('showTutorial', true)
            ->assertActionVisible('employeeTutorial')
            ->assertSee('Tus trabajadores, listos para cada ingreso')
            ->assertSee('Información clara para un acceso seguro')
            ->assertSee('La aprobación mantiene el acceso protegido')
            ->assertSee('Actualización cada 6 meses.')
            ->assertSee('images/employee-tutorial/preload-worker.webp', escape: false)
            ->assertSee('images/employee-tutorial/personal-and-vehicle-documents.webp', escape: false)
            ->assertSee('images/employee-tutorial/review-and-renewal.webp', escape: false)
            ->call('markTutorialSeen')
            ->assertSet('showTutorial', false);

        $this->assertNotNull($user->fresh()->employee_tutorial_seen_at);

        Livewire::test(ManageEmployees::class)
            ->assertSet('showTutorial', false)
            ->callAction('employeeTutorial')
            ->assertSet('showTutorial', true);
    }
}
