<?php

namespace Tests\Feature;

use App\Filament\Resources\FormControlResource\Pages\CreateFormControl;
use App\Models\FormControl;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\ProveedorAccessService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormControlTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_generates_a_quick_access_code_and_casts_multi_value_fields(): void
    {
        $formControl = $this->createFormControl();

        $this->assertMatchesRegularExpression('/^F-[A-Z0-9]{8}$/', $formControl->quick_access_code);
        $this->assertSame(['lote'], $formControl->access_type);
        $this->assertSame(['A12', 'B04'], $formControl->lote_ids);
        $this->assertSame(['Visita'], $formControl->income_type);
    }

    public function test_create_page_sends_the_database_notification_after_saving(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $formControl = $this->createFormControl();
        $notificationsBefore = DB::table('notifications')->where('notifiable_id', $admin->id)->count();

        $page = new CreateFormControl();
        $page->record = $formControl;
        $afterCreate = new ReflectionMethod(CreateFormControl::class, 'afterCreate');
        $afterCreate->invoke($page);

        $this->assertSame(
            $notificationsBefore + 1,
            DB::table('notifications')->where('notifiable_id', $admin->id)->count(),
        );
    }

    public function test_it_is_active_only_when_authorized_and_inside_a_date_range(): void
    {
        Carbon::setTestNow('2026-09-10 12:00:00');

        $formControl = $this->createFormControl(['status' => 'Authorized']);
        $formControl->dateRanges()->create([
            'start_date_range' => '2026-09-10',
            'start_time_range' => '07:00',
            'end_date_range' => '2026-09-10',
            'end_time_range' => '18:00',
            'date_unilimited' => false,
        ]);

        $this->assertTrue($formControl->fresh()->isDayRange());
        $this->assertTrue($formControl->fresh()->isActive());

        Carbon::setTestNow('2026-09-10 19:00:00');

        $this->assertFalse($formControl->fresh()->isDayRange());
    }

    public function test_approving_and_rejecting_record_the_responsible_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $approved = $this->createFormControl(['status' => 'Pending']);
        $approved->aprobar();

        $this->assertSame('Authorized', $approved->fresh()->status);
        $this->assertSame($user->id, $approved->fresh()->authorized_user_id);

        $denied = $this->createFormControl(['status' => 'Pending']);
        $denied->rechazar();

        $this->assertSame('Denied', $denied->fresh()->status);
        $this->assertSame($user->id, $denied->fresh()->denied_user_id);
    }

    public function test_provider_and_form_qr_resolve_the_same_active_authorizations(): void
    {
        $proveedor = Proveedor::create([
            'nombre_empresa' => 'Servicios del Sur',
            'telefono_empresa' => '1122334455',
        ]);

        $first = $this->createFormControl([
            'proveedor_id' => $proveedor->id,
            'income_type' => ['Proveedor'],
            'status' => 'Authorized',
            'lote_ids' => ['A12'],
        ]);
        $second = $this->createFormControl([
            'proveedor_id' => $proveedor->id,
            'income_type' => ['Proveedor'],
            'status' => 'Authorized',
            'lote_ids' => ['B04', 'A12'],
        ]);

        foreach ([$first, $second] as $formControl) {
            $formControl->dateRanges()->create([
                'start_date_range' => '2026-09-10',
                'start_time_range' => '07:00',
                'end_date_range' => '2026-09-10',
                'end_time_range' => '18:00',
            ]);
        }

        $service = app(ProveedorAccessService::class);
        $resolvedByProvider = $service->resolveProviderByCode($proveedor->quick_access_code);
        $resolvedByForm = $service->resolveProviderByCode($first->quick_access_code);
        $forms = $service->activeForms($resolvedByForm, Carbon::parse('2026-09-10 12:00'));

        $this->assertTrue($resolvedByProvider->is($proveedor));
        $this->assertTrue($resolvedByForm->is($proveedor));
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $forms->pluck('id')->all());
        $this->assertSame(['A12', 'B04'], $service->authorizedLotes($forms));
    }

    public function test_provider_quick_access_page_recognizes_the_company(): void
    {
        $proveedor = Proveedor::create([
            'nombre_empresa' => 'Servicios del Sur',
            'telefono_empresa' => '1122334455',
        ]);

        $this->assertMatchesRegularExpression('/^P-[A-Z0-9]{8}$/', $proveedor->quick_access_code);

        $this->get('/quick-access/'.$proveedor->quick_access_code)
            ->assertOk()
            ->assertSee('Proveedor')
            ->assertSee('Servicios del Sur');
    }

    public function test_provider_resource_permissions_exist(): void
    {
        $this->assertSame(
            12,
            DB::table('permissions')->where('name', 'like', '%_proveedor')->count(),
        );
    }

    private function createFormControl(array $attributes = []): FormControl
    {
        $user = User::factory()->create();

        return FormControl::create(array_merge([
            'access_type' => ['lote'],
            'lote_ids' => ['A12', 'B04'],
            'income_type' => ['Visita'],
            'status' => 'Pending',
            'user_id' => $user->id,
        ], $attributes));
    }
}
