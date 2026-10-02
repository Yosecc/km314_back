<?php

namespace Tests\Feature;

use App\Filament\Pages\FormControlMonitor;
use App\Filament\Resources\FormControlResource\Pages\ListFormControls;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FormControlTutorialTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tutorial_is_shared_by_the_form_list_and_monitor_and_uses_browser_storage(): void
    {
        $user = User::factory()->create();

        foreach (['view_any_form::control', 'create_form::control', 'page_FormControlMonitor'] as $permissionName) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        $this->actingAs($user);

        Livewire::test(ListFormControls::class)
            ->assertActionVisible('formControlTutorial')
            ->assertSee('Un formulario para cada ingreso al barrio')
            ->assertSee('Cargá los datos necesarios para validar el acceso')
            ->assertSee('Administración verifica el formulario')
            ->assertSee('Acceso ágil con QR o DNI')
            ->assertSee('km314.form-control-tutorial.seen.user-'.$user->id, escape: false)
            ->assertSee('images/form-control-tutorial/purpose-and-types.webp', escape: false)
            ->assertSee('images/form-control-tutorial/information-and-documents.webp', escape: false)
            ->assertSee('images/form-control-tutorial/administration-review.webp', escape: false)
            ->assertSee('images/form-control-tutorial/qr-dni-and-validity.webp', escape: false)
            ->callAction('formControlTutorial')
            ->assertDispatched('open-form-control-tutorial');

        Livewire::test(FormControlMonitor::class)
            ->assertSuccessful()
            ->assertSee('Cómo funciona')
            ->assertSee('El permiso funciona solamente dentro del rango seleccionado.')
            ->assertSee('km314.form-control-tutorial.seen.user-'.$user->id, escape: false);
    }
}
