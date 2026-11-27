<?php

namespace Tests\Feature;

use App\Filament\Resources\EmployeeResource\Pages\ManageEmployees;
use App\Models\User;
use App\Models\FilesRequired;
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

    public function test_employee_creation_shows_friendly_validation_errors(): void
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
            ->callAction('create', [
                'dni' => 'DNI inválido',
                'first_name' => '',
                'last_name' => '',
                'phone' => 'teléfono inválido',
            ])
            ->assertHasActionErrors([
                'dni' => 'numeric',
                'first_name' => 'required',
                'last_name' => 'required',
                'phone' => 'numeric',
            ])
            ->assertNotified('Revise los datos del trabajador');

        $this->assertDatabaseMissing('employees', [
            'dni' => 'DNI inválido',
        ]);
    }

    public function test_employee_creation_identifies_the_required_document_in_the_error(): void
    {
        FilesRequired::updateOrCreate(
            ['type' => 'employee'],
            [
                'name' => 'Documentos del trabajador',
                'required' => [[
                    'document' => 'Seguro personal',
                    'is_required' => true,
                    'date_is_required' => true,
                ]],
                'no_required' => [],
            ],
        );

        $user = User::factory()->create();

        foreach (['view_any_employee', 'create_employee'] as $permissionName) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        $this->actingAs($user);

        Livewire::test(ManageEmployees::class)
            ->callAction('create', [
                'dni' => '30111222',
                'first_name' => 'Carla',
                'last_name' => 'Ejemplo',
                'phone' => '2215708176',
            ])
            ->assertHasActionErrors([
                'files.*.file' => fn (array $rules, array $messages): bool => collect($messages)
                    ->flatten()
                    ->contains('Debe cargar el archivo de Seguro personal.'),
                'files.*.fecha_vencimiento' => fn (array $rules, array $messages): bool => collect($messages)
                    ->flatten()
                    ->contains('Ingrese la fecha de vencimiento de Seguro personal.'),
            ])
            ->assertNotified('Revise los datos del trabajador');

        $this->assertDatabaseMissing('employees', [
            'dni' => '30111222',
        ]);
    }
}
