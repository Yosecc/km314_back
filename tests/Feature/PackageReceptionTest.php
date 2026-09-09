<?php

namespace Tests\Feature;

use App\Models\Lote;
use App\Models\Owner;
use App\Models\PackageReception;
use App\Models\User;
use App\Services\PackageReceptionService;
use App\Filament\Pages\PackageReceptionMonitor;
use App\Filament\Resources\PackageReceptionResource;
use App\Filament\Widgets\PackageReceptionStats;
use App\Filament\Resources\PackageReceptionResource\Pages\ListPackageReceptions;
use App\Filament\Resources\PackageReceptionResource\Pages\ViewPackageReception;
use App\Filament\Resources\PackageReceptionResource\Pages\CreatePackageReception;
use App\Filament\Resources\PackageReceptionResource\Pages\EditPackageReception;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PackageReceptionTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    public function test_it_creates_a_reference_and_encrypts_the_access_code(): void
    {
        [$owner, $lote, $user] = $this->context();
        $record = $this->reception($owner, $lote, $user, ['carrier_access_code'=>'CLAVE-123']);
        $this->assertStringStartsWith('PAQ-', $record->reference_code);
        $this->assertSame('CLAVE-123', $record->carrier_access_code);
        $this->assertNotSame('CLAVE-123', DB::table('package_receptions')->where('id',$record->id)->value('carrier_access_code'));
    }

    public function test_receive_records_actor_time_file_event_and_notification(): void
    {
        Carbon::setTestNow('2026-09-05 11:30:00');
        [$owner, $lote, $user] = $this->context();
        $operator = User::factory()->create();
        $record = $this->reception($owner, $lote, $user);
        $result = app(PackageReceptionService::class)->receive($record, $operator, [
            'received_packages_count'=>2, 'reception_notes'=>'Caja sin daños',
            'reception_files'=>['package-receptions/reception/foto.jpg'],
        ]);
        $this->assertSame(PackageReception::RECEIVED, $result->status);
        $this->assertSame($operator->id, (int) $result->received_by_user_id);
        $this->assertSame(2, $result->received_packages_count);
        $this->assertDatabaseHas('package_reception_events',['package_reception_id'=>$record->id,'event_type'=>'received']);
        $this->assertDatabaseHas('package_reception_files',['package_reception_id'=>$record->id,'category'=>'reception']);
        $this->assertDatabaseHas('notifications',['notifiable_id'=>$user->id]);
    }

    public function test_an_already_received_package_cannot_be_received_again(): void
    {
        [$owner, $lote, $user] = $this->context();
        $record = $this->reception($owner, $lote, $user, ['status'=>PackageReception::RECEIVED,'received_at'=>now()]);
        $this->expectException(ValidationException::class);
        app(PackageReceptionService::class)->receive($record, $user, []);
    }

    public function test_third_party_delivery_requires_identity_data_and_image(): void
    {
        [$owner, $lote, $user] = $this->context();
        $record = $this->reception($owner, $lote, $user, ['status'=>PackageReception::RECEIVED,'received_at'=>now()]);
        try {
            app(PackageReceptionService::class)->deliver($record, $user, ['delivered_to_type'=>'third_party']);
            $this->fail('La entrega sin identificación no fue rechazada.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('delivered_to_name', $e->errors());
        }
        $result = app(PackageReceptionService::class)->deliver($record, $user, [
            'delivered_to_type'=>'third_party','delivered_to_name'=>'María Pérez','delivered_to_dni'=>'30111222',
            'delivery_identity_files'=>['package-receptions/delivery-identity/dni.jpg'],
        ]);
        $this->assertSame(PackageReception::DELIVERED, $result->status);
        $this->assertDatabaseHas('package_reception_files',['package_reception_id'=>$record->id,'category'=>'delivery_identity']);
    }

    public function test_alert_command_marks_notifications_only_once(): void
    {
        Carbon::setTestNow('2026-09-05 12:00:00');
        [$owner, $lote, $user] = $this->context();
        $record = $this->reception($owner, $lote, $user, ['expected_from'=>now()->subHours(3),'expected_until'=>now()->subHour()]);
        Artisan::call('package-receptions:process-alerts');
        $firstCount = DB::table('notifications')->where('notifiable_id',$user->id)->count();
        Artisan::call('package-receptions:process-alerts');
        $this->assertNotNull($record->fresh()->overdue_notified_at);
        $this->assertSame($firstCount, DB::table('notifications')->where('notifiable_id',$user->id)->count());
        $this->assertSame(1, $firstCount);
    }

    public function test_owner_visibility_scope_never_returns_another_owners_package(): void
    {
        [$owner, $lote, $user] = $this->context();
        $mine = $this->reception($owner, $lote, $user);
        [$otherOwner, $otherLote, $otherUser] = $this->context();
        $this->reception($otherOwner, $otherLote, $otherUser);
        DB::table('roles')->insertOrIgnore(['name'=>'owner','guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]);
        $user->assignRole('owner');
        $this->assertSame([$mine->id], PackageReception::query()->visibleTo($user)->pluck('id')->all());
    }

    public function test_operational_list_and_monitor_render_successfully(): void
    {
        [$owner, $lote, $user] = $this->context();
        $record = $this->reception($owner, $lote, $user, ['carrier_access_code'=>'PUERTA-9']);
        $record->files()->create(['category'=>'registration','path'=>'package-receptions/registration/comprobante.jpg','original_name'=>'comprobante.jpg','uploaded_by_user_id'=>$user->id]);
        Permission::firstOrCreate(['name'=>'page_PackageReceptionMonitor','guard_name'=>'web']);
        $user->givePermissionTo(Permission::whereIn('name', [
            'view_any_package::reception', 'view_package::reception',
            'page_PackageReceptionMonitor', 'receive_package::reception', 'deliver_package::reception',
            'cancel_package::reception',
            'view_sensitive_package::reception', 'create_package::reception',
        ])->get());
        $this->actingAs($user);
        Livewire::test(ListPackageReceptions::class)->assertSuccessful()->assertSee('Mercado Libre');
        Livewire::test(PackageReceptionMonitor::class)->assertSuccessful()->assertSee('Seguimiento de paquetes')->assertSee('Mercado Libre')
            ->assertSee('Nueva recepción')->assertSee('Gestionar recepciones');
        Livewire::test(ViewPackageReception::class, ['record'=>$record->getRouteKey()])->assertSuccessful()->assertSee('PUERTA-9')->assertSee('comprobante.jpg');
        Livewire::test(PackageReceptionMonitor::class)
            ->mountAction('receive', ['reception'=>$record->id])
            ->setActionData(['received_packages_count'=>2, 'reception_notes'=>'Recibido desde el monitor'])
            ->callMountedAction()
            ->assertHasNoActionErrors();
        $this->assertSame(PackageReception::RECEIVED, $record->fresh()->status);
        $this->assertSame('Recibido desde el monitor', $record->fresh()->reception_notes);

        Livewire::test(PackageReceptionMonitor::class)
            ->mountAction('deliver', ['reception'=>$record->id])
            ->setActionData(['delivered_to_type'=>'owner', 'delivery_notes'=>'Entregado desde el monitor'])
            ->callMountedAction()
            ->assertHasNoActionErrors();
        $this->assertSame(PackageReception::DELIVERED, $record->fresh()->status);

        $cancelledRecord = $this->reception($owner, $lote, $user, ['courier_name'=>'OCA']);
        Livewire::test(PackageReceptionMonitor::class)
            ->mountAction('cancel', ['reception'=>$cancelledRecord->id])
            ->setActionData(['cancellation_reason'=>'El propietario anuló la compra'])
            ->callMountedAction()
            ->assertHasNoActionErrors();
        $this->assertSame(PackageReception::CANCELLED, $cancelledRecord->fresh()->status);
        $this->assertSame('El propietario anuló la compra', $cancelledRecord->fresh()->cancellation_reason);
    }

    public function test_package_resource_exposes_transition_permissions_to_shield(): void
    {
        $prefixes = PackageReceptionResource::getPermissionPrefixes();

        $this->assertContains('receive', $prefixes);
        $this->assertContains('deliver', $prefixes);
        $this->assertContains('cancel', $prefixes);
        $this->assertContains('view_sensitive', $prefixes);
    }

    public function test_transition_permissions_respect_both_permission_and_current_status(): void
    {
        [$owner, $lote, $user] = $this->context();
        $record = $this->reception($owner, $lote, $user);
        $user->givePermissionTo(Permission::whereIn('name', [
            'receive_package::reception',
            'deliver_package::reception',
            'cancel_package::reception',
        ])->get());

        $this->assertTrue($user->can('receive', $record));
        $this->assertTrue($user->can('cancel', $record));
        $this->assertFalse($user->can('deliver', $record));

        $record->update(['status'=>PackageReception::RECEIVED, 'received_at'=>now()]);
        $record->refresh();

        $this->assertFalse($user->can('receive', $record));
        $this->assertFalse($user->can('cancel', $record));
        $this->assertTrue($user->can('deliver', $record));
    }

    public function test_a_package_reception_can_only_be_edited_while_it_is_expected(): void
    {
        [$owner, $lote, $user] = $this->context();
        $record = $this->reception($owner, $lote, $user);
        $user->givePermissionTo(Permission::whereIn('name', [
            'view_any_package::reception',
            'view_package::reception',
            'update_package::reception',
        ])->get());
        $this->actingAs($user);

        $this->assertTrue($user->can('update', $record));
        $this->assertTrue(PackageReceptionResource::canEdit($record));
        Livewire::test(EditPackageReception::class, ['record'=>$record->getRouteKey()])
            ->assertSuccessful();

        $record->update(['status'=>PackageReception::RECEIVED, 'received_at'=>now()]);
        $record->refresh();

        $this->assertFalse($user->can('update', $record));
        $this->assertFalse(PackageReceptionResource::canEdit($record));
        Livewire::test(EditPackageReception::class, ['record'=>$record->getRouteKey()])
            ->assertForbidden();
    }

    public function test_owner_cannot_access_package_monitor_without_its_shield_page_permission(): void
    {
        [, , $user] = $this->context();
        DB::table('roles')->insertOrIgnore(['name'=>'owner','guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]);
        $user->assignRole('owner');
        $user->givePermissionTo(Permission::whereIn('name', ['view_any_package::reception','view_package::reception'])->get());
        $this->actingAs($user);

        $this->assertFalse(PackageReceptionMonitor::canAccess());
        Livewire::test(ListPackageReceptions::class)->assertActionHidden('monitor');

        $user->givePermissionTo(Permission::firstOrCreate(['name'=>'page_PackageReceptionMonitor','guard_name'=>'web']));
        $this->assertTrue(PackageReceptionMonitor::canAccess());
        Livewire::test(ListPackageReceptions::class)->assertActionVisible('monitor');
    }

    public function test_dashboard_widget_shows_the_four_package_summaries_for_owner(): void
    {
        Carbon::setTestNow('2026-09-05 12:00:00');
        [$owner,$lote,$user]=$this->context();
        DB::table('roles')->insertOrIgnore(['name'=>'owner','guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]);
        $user->assignRole('owner');
        $this->reception($owner,$lote,$user,['expected_from'=>now()->addHour(),'expected_until'=>now()->addHours(2)]);
        $this->reception($owner,$lote,$user,['expected_from'=>now()->subHours(2),'expected_until'=>now()->subHour()]);
        $this->reception($owner,$lote,$user,['status'=>PackageReception::RECEIVED,'received_at'=>now()]);
        $this->reception($owner,$lote,$user,['status'=>PackageReception::RECEIVED,'received_at'=>now()->subHours(config('package-receptions.pickup_warning_hours')+1)]);
        foreach (['widget_PackageReceptionStats','page_PackageReceptionMonitor'] as $permission) {
            Permission::firstOrCreate(['name'=>$permission,'guard_name'=>'web']);
            $user->givePermissionTo($permission);
        }
        $this->actingAs($user);

        Livewire::test(PackageReceptionStats::class)->assertSuccessful()
            ->assertSee('Recepción de Paquetes')->assertSee('Esperados')->assertSee('En recepción')
            ->assertSee('No llegaron')->assertSee('Retiro demorado')->assertSee('Abrir monitor');
    }

    public function test_creation_form_combines_date_and_separate_times(): void
    {
        [$owner, $lote, $user] = $this->context();
        $user->givePermissionTo(Permission::whereIn('name', ['view_any_package::reception','create_package::reception'])->get());
        $this->actingAs($user);
        Livewire::test(CreatePackageReception::class)->fillForm([
            'owner_id'=>$owner->id, 'lote_id'=>$lote->id, 'courier_name'=>'OCA',
            'expected_date'=>'2026-09-08', 'expected_from_time'=>'09:15', 'expected_until_time'=>'13:45',
            'carrier_access_code'=>'CLAVE VISIBLE',
        ])->call('create')->assertHasNoFormErrors();
        $record = PackageReception::where('courier_name','OCA')->firstOrFail();
        $this->assertSame('2026-09-08 09:15', $record->expected_from->format('Y-m-d H:i'));
        $this->assertSame('2026-09-08 13:45', $record->expected_until->format('Y-m-d H:i'));
        $this->assertSame('CLAVE VISIBLE', $record->carrier_access_code);
    }

    private function context(): array
    {
        $suffix = uniqid();
        $owner = Owner::create(['first_name'=>'Ana','last_name'=>'Propietaria','email'=>"owner{$suffix}@test.local",'user_id'=>'0']);
        $user = User::factory()->create(['owner_id'=>$owner->id]);
        $owner->update(['user_id'=>(string) $user->id]);
        $sector = DB::table('sectors')->insertGetId(['name'=>'A','created_at'=>now(),'updated_at'=>now()]);
        $type = DB::table('lote_types')->insertGetId(['name'=>'Casa','created_at'=>now(),'updated_at'=>now()]);
        $status = DB::table('lote_statuses')->insertGetId(['name'=>'Activo','color'=>'green','created_at'=>now(),'updated_at'=>now()]);
        $lote = Lote::create(['width'=>'10','height'=>'20','m2'=>'200','sector_id'=>$sector,'lote_id'=>random_int(1,9999),'lote_type_id'=>$type,'lote_status_id'=>$status,'owner_id'=>$owner->id]);
        return [$owner->fresh(), $lote, $user];
    }

    private function reception(Owner $owner, Lote $lote, User $user, array $attributes=[]): PackageReception
    {
        return PackageReception::create(array_merge([
            'owner_id'=>$owner->id,'lote_id'=>$lote->id,'created_by_user_id'=>$user->id,
            'courier_name'=>'Mercado Libre','expected_from'=>now(),'expected_until'=>now()->addHours(2),
        ], $attributes));
    }
}
