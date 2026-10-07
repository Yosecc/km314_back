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

    public function test_employee_tutorial_uses_browser_storage_and_can_always_be_reopened(): void
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
            ->assertActionVisible('employeeTutorial')
            ->assertSee('Tus trabajadores, listos para cada ingreso')
            ->assertSee('Información clara para un acceso seguro')
            ->assertSee('La aprobación mantiene el acceso protegido')
            ->assertSee('Actualización cada '.config('employees.documentation_renewal_months').' meses.')
            ->assertSee('km314.employee-tutorial.seen.user-'.$user->id, escape: false)
            ->assertSee('images/employee-tutorial/preload-worker.webp', escape: false)
            ->assertSee('images/employee-tutorial/personal-and-vehicle-documents.webp', escape: false)
            ->assertSee('images/employee-tutorial/review-and-renewal.webp', escape: false)
            ->callAction('employeeTutorial')
            ->assertDispatched('open-employee-tutorial');
    }
}
