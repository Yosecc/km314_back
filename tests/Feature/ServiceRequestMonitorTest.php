<?php

namespace Tests\Feature;

use App\Filament\Pages\ServiceRequestMonitor;
use App\Filament\Resources\ServiceRequestResource;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceRequestMonitorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_owner_card_uses_view_page_and_hides_edit_link_when_request_is_not_editable(): void
    {
        [$owner, $lote, $user] = $this->ownerContext();
        $status = ServiceRequestStatus::query()->updateOrCreate(
            ['code' => ServiceRequestStatus::IN_PROGRESS],
            ['name' => 'En proceso', 'color' => '#2563eb'],
        );
        $requestType = ServiceRequestType::create(['name' => 'Prueba monitor', 'isCalendar' => false]);
        $serviceTypeId = DB::table('service_types')->insertGetId([
            'name' => 'Prueba monitor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $service = Service::create([
            'service_type_id' => $serviceTypeId,
            'service_request_type_id' => $requestType->id,
            'name' => 'Servicio de prueba',
        ]);
        $request = ServiceRequest::withoutEvents(fn () => ServiceRequest::create([
            'name' => 'Solicitud visible pero no editable',
            'user_id' => $user->id,
            'owner_id' => $owner->id,
            'lote_id' => $lote->id,
            'service_id' => $service->id,
            'service_request_type_id' => $requestType->id,
            'service_request_status_id' => $status->id,
            'starts_at' => now()->addDay(),
        ]));

        foreach (['page_ServiceRequestMonitor', 'view_service::request', 'update_service::request'] as $permissionName) {
            $user->givePermissionTo(Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]));
        }

        $this->actingAs($user);

        $viewUrl = ServiceRequestResource::getUrl('view', ['record' => $request]);
        $editUrl = ServiceRequestResource::getUrl('edit', ['record' => $request]);

        $this->assertTrue(ServiceRequestResource::canView($request));
        $this->assertFalse(ServiceRequestResource::canEdit($request));

        Livewire::test(ServiceRequestMonitor::class)
            ->set('status', 'all')
            ->assertSee('SOLICITUD #'.$request->id)
            ->assertSeeHtml('href="'.$viewUrl.'"')
            ->assertDontSeeHtml('href="'.$editUrl.'"');
    }

    private function ownerContext(): array
    {
        $suffix = uniqid();
        $owner = Owner::create([
            'first_name' => 'Monitor',
            'last_name' => $suffix,
            'email' => "service{$suffix}@test.local",
            'user_id' => '0',
        ]);
        $user = User::factory()->create(['owner_id' => $owner->id]);
        $user->forceFill(['service_request_tutorial_seen_at' => now()])->save();
        $owner->update(['user_id' => (string) $user->id]);
        $user->assignRole(Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']));

        $sectorId = DB::table('sectors')->insertGetId(['name' => 'S', 'created_at' => now(), 'updated_at' => now()]);
        $typeId = DB::table('lote_types')->insertGetId(['name' => 'Casa', 'created_at' => now(), 'updated_at' => now()]);
        $statusId = DB::table('lote_statuses')->insertGetId(['name' => 'Activo', 'color' => 'green', 'created_at' => now(), 'updated_at' => now()]);
        $lote = Lote::create([
            'width' => '10',
            'height' => '20',
            'm2' => '200',
            'sector_id' => $sectorId,
            'lote_id' => random_int(1000, 9999),
            'lote_type_id' => $typeId,
            'lote_status_id' => $statusId,
            'owner_id' => $owner->id,
        ]);

        return [$owner->fresh(), $lote, $user];
    }
}
