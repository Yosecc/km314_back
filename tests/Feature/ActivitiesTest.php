<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivitiesResource;
use App\Filament\Pages\MonitorAccesos;
use App\Models\Activities;
use App\Models\ActivitiesPeople;
use App\Models\Auto;
use App\Models\FormControl;
use App\Models\Proveedor;
use App\Models\ProveedorEmpleado;
use App\Models\User;
use App\Services\CurrentPeopleInsideQuery;
use App\Services\ProveedorAccessService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use ReflectionMethod;

class ActivitiesTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_an_activity_can_reference_a_form_control_and_people(): void
    {
        $formControl = $this->createFormControl();
        $activity = Activities::create([
            'form_control_id' => $formControl->id,
            'lote_ids' => 'A12',
            'tipo_entrada' => 3,
            'type' => 'Entry',
        ]);

        $person = $activity->peoples()->create([
            'model' => 'FormControl',
            'model_id' => 987,
        ]);

        $this->assertTrue($activity->fresh()->formControl->is($formControl));
        $this->assertTrue($activity->fresh()->peoples->contains($person));
    }

    public function test_the_current_people_query_returns_only_people_whose_latest_movement_is_entry(): void
    {
        Carbon::setTestNow('2026-09-10 08:00:00');
        $this->recordMovement('Employee', 10, 'Entry');
        $this->recordMovement('Employee', 20, 'Entry');

        Carbon::setTestNow('2026-09-10 09:00:00');
        $this->recordMovement('Employee', 10, 'Exit');

        $inside = CurrentPeopleInsideQuery::make()->get();

        $this->assertCount(1, $inside);
        $this->assertSame('Employee', $inside->first()->model);
        $this->assertSame(20, (int) $inside->first()->model_id);
    }

    public function test_a_new_entry_after_an_exit_marks_the_person_as_inside_again(): void
    {
        Carbon::setTestNow('2026-09-10 08:00:00');
        $this->recordMovement('Employee', 10, 'Entry');

        Carbon::setTestNow('2026-09-10 09:00:00');
        $this->recordMovement('Employee', 10, 'Exit');

        Carbon::setTestNow('2026-09-10 10:00:00');
        $latest = $this->recordMovement('Employee', 10, 'Entry');

        $inside = CurrentPeopleInsideQuery::make()->get();

        $this->assertCount(1, $inside);
        $this->assertTrue($inside->first()->is($latest));
    }

    public function test_lote_visibility_tolerates_a_missing_form_control(): void
    {
        $this->assertFalse(ActivitiesResource::canShowLoteSelection(PHP_INT_MAX));

        $formControl = $this->createFormControl();

        $this->assertTrue(ActivitiesResource::canShowLoteSelection($formControl->id));
    }

    public function test_a_provider_activity_can_reference_one_person_and_multiple_forms(): void
    {
        $proveedor = Proveedor::create([
            'nombre_empresa' => 'Servicios del Sur',
            'telefono_empresa' => '1122334455',
        ]);
        $persona = ProveedorEmpleado::create([
            'proveedor_id' => $proveedor->id,
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'dni' => '30111222',
        ]);
        $first = $this->createFormControl(['proveedor_id' => $proveedor->id]);
        $second = $this->createFormControl(['proveedor_id' => $proveedor->id]);

        $activity = Activities::create([
            'proveedor_id' => $proveedor->id,
            'lote_ids' => 'A12, B04',
            'tipo_entrada' => 3,
            'type' => 'Entry',
        ]);
        $activity->formControls()->attach([$first->id, $second->id]);
        $movement = $activity->peoples()->create([
            'model' => 'ProveedorEmpleado',
            'model_id' => $persona->id,
        ]);

        $this->assertCount(2, $activity->fresh()->formControls);
        $this->assertTrue($activity->fresh()->proveedor->is($proveedor));
        $this->assertTrue($movement->getPeople()->is($persona));
    }

    public function test_provider_exit_recovers_the_forms_from_the_open_entry(): void
    {
        Carbon::setTestNow('2026-09-10 12:00:00');
        $proveedor = Proveedor::create([
            'nombre_empresa' => 'Servicios del Sur',
            'telefono_empresa' => '1122334455',
        ]);
        $persona = ProveedorEmpleado::create([
            'proveedor_id' => $proveedor->id,
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'dni' => '30111222',
        ]);
        $forms = collect([
            $this->createFormControl(['proveedor_id' => $proveedor->id]),
            $this->createFormControl(['proveedor_id' => $proveedor->id]),
        ]);
        $entry = Activities::create([
            'proveedor_id' => $proveedor->id,
            'lote_ids' => 'A12, B04',
            'tipo_entrada' => 3,
            'type' => 'Entry',
        ]);
        $entry->formControls()->attach($forms->pluck('id'));
        $entry->peoples()->create([
            'model' => 'ProveedorEmpleado',
            'model_id' => $persona->id,
        ]);

        $service = app(ProveedorAccessService::class);
        $openEntry = $service->openEntry($proveedor, $persona->id);

        $this->assertTrue($openEntry->is($entry));
        $this->assertEqualsCanonicalizing($forms->pluck('id')->all(), $openEntry->formControls->pluck('id')->all());

        $exit = Activities::create([
            'proveedor_id' => $proveedor->id,
            'lote_ids' => 'A12, B04',
            'tipo_entrada' => 3,
            'type' => 'Exit',
        ]);
        $exit->peoples()->create([
            'model' => 'ProveedorEmpleado',
            'model_id' => $persona->id,
        ]);

        $this->assertNull($service->openEntry($proveedor, $persona->id));
    }

    public function test_an_auto_added_from_provider_activity_belongs_to_the_provider(): void
    {
        $this->actingAs(User::factory()->create());
        $proveedor = Proveedor::create([
            'nombre_empresa' => 'Servicios del Sur',
            'telefono_empresa' => '1122334455',
        ]);

        ActivitiesResource::createAuto([[
            'marca' => 'Ford',
            'modelo' => 'Transit',
            'patente' => 'AA123BB',
            'color' => 'Blanco',
            'model' => 'Proveedor',
            'model_id' => $proveedor->id,
        ]], ['type' => 3]);

        $auto = Auto::where('patente', 'AA123BB')->firstOrFail();

        $this->assertSame('Proveedor', $auto->model);
        $this->assertSame($proveedor->id, (int) $auto->model_id);
        $this->assertTrue($proveedor->fresh()->autos->contains($auto));
    }

    public function test_provider_employee_stores_a_single_dni_file_from_repeater_state(): void
    {
        $proveedor = Proveedor::create([
            'nombre_empresa' => 'Servicios del Sur',
            'telefono_empresa' => '1122334455',
        ]);

        $persona = ProveedorEmpleado::create([
            'proveedor_id' => $proveedor->id,
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'dni' => '30111222',
            'archivo_dni' => ['proveedores/dni/30111222.pdf'],
        ]);

        $this->assertSame('proveedores/dni/30111222.pdf', $persona->fresh()->archivo_dni);
    }

    public function test_access_monitor_displays_provider_employee_data(): void
    {
        $proveedor = Proveedor::create([
            'nombre_empresa' => 'Servicios del Sur',
            'telefono_empresa' => '1122334455',
        ]);
        $persona = ProveedorEmpleado::create([
            'proveedor_id' => $proveedor->id,
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'dni' => '30111222',
        ]);
        $activity = Activities::create([
            'proveedor_id' => $proveedor->id,
            'lote_ids' => 'A12',
            'tipo_entrada' => 3,
            'type' => 'Entry',
        ]);
        $movement = $activity->peoples()->create([
            'model' => 'ProveedorEmpleado',
            'model_id' => $persona->id,
        ]);

        $method = new ReflectionMethod(MonitorAccesos::class, 'mapPersonEvent');
        $method->setAccessible(true);
        $event = $method->invoke(new MonitorAccesos(), $movement);

        $this->assertSame('Ana Pérez', $event['name']);
        $this->assertSame('30111222', $event['dni']);
        $this->assertStringContainsString('Servicios del Sur', $event['category']);
    }

    private function recordMovement(string $model, int $modelId, string $type): ActivitiesPeople
    {
        $activity = Activities::create([
            'tipo_entrada' => 2,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $activity->peoples()->create([
            'model' => $model,
            'model_id' => $modelId,
        ]);
    }

    private function createFormControl(array $attributes = []): FormControl
    {
        return FormControl::create(array_merge([
            'access_type' => ['lote'],
            'lote_ids' => ['A12'],
            'income_type' => ['Visita'],
            'status' => 'Authorized',
            'user_id' => User::factory()->create()->id,
        ], $attributes));
    }
}
