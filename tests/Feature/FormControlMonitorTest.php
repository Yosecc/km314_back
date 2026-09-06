<?php

namespace Tests\Feature;

use App\Filament\Pages\FormControlMonitor;
use App\Filament\Pages\MonitorAccesos;
use App\Filament\Pages\ProfileOwner;
use App\Filament\Resources\FormControlResource\Widgets\FormControlStats;
use App\Models\FormControl;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FormControlMonitorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_owner_monitor_only_displays_its_own_forms(): void
    {
        [$owner,$lote,$user]=$this->context('owner');
        $user->forceFill(['is_terms_condition'=>true])->save();
        $mine=$this->form($owner,$lote,$user,'OwnerPending');
        [$otherOwner,$otherLote,$otherUser]=$this->context('owner');
        $other=$this->form($otherOwner,$otherLote,$otherUser,'OwnerPending');
        $this->actingAs($user);
        foreach (['page_FormControlMonitor','create_form::control'] as $permission) {
            Permission::firstOrCreate(['name'=>$permission,'guard_name'=>'web']);
            $user->givePermissionTo($permission);
        }
        Livewire::test(FormControlMonitor::class)->assertSuccessful()
            ->assertSee('Monitor de Formularios')->assertSee('FORMULARIO #'.$mine->id)
            ->assertDontSee('FORMULARIO #'.$other->id)->assertSee('Aprobar solicitud')->assertSee('Ver QR')
            ->assertSee('Form. Públicos')->assertSee('Nuevo formulario')->assertActionVisible('sharePublicForm');
    }

    public function test_custom_pages_respect_their_individual_shield_permissions(): void
    {
        [, , $user]=$this->context('owner');
        $this->actingAs($user);

        $this->assertFalse(FormControlMonitor::canAccess());
        $this->assertFalse(MonitorAccesos::canAccess());
        $this->assertFalse(ProfileOwner::canAccess());

        foreach (['page_FormControlMonitor','page_MonitorAccesos','page_ProfileOwner'] as $permission) {
            Permission::firstOrCreate(['name'=>$permission,'guard_name'=>'web']);
            $user->givePermissionTo($permission);
        }

        $this->assertTrue(FormControlMonitor::canAccess());
        $this->assertTrue(MonitorAccesos::canAccess());
        $this->assertTrue(ProfileOwner::canAccess());
    }

    public function test_dashboard_widget_shows_the_four_form_control_summaries_for_owner(): void
    {
        [$owner,$lote,$user]=$this->context('owner');
        $this->form($owner,$lote,$user,'OwnerPending');
        $this->form($owner,$lote,$user,'Pending');
        $this->form($owner,$lote,$user,'Authorized');
        $this->form($owner,$lote,$user,'Denied');
        Permission::firstOrCreate(['name'=>'widget_FormControlStats','guard_name'=>'web']);
        $user->givePermissionTo('widget_FormControlStats');
        $this->actingAs($user);

        Livewire::test(FormControlStats::class)->assertSuccessful()
            ->assertSee('Esperan tu aprobación')->assertSee('Pendientes')
            ->assertSee('Autorizados')->assertSee('Requieren atención')
            ->assertSee('1 rechazados · 0 vencidos · 0 expirados');
    }

    public function test_admin_does_not_see_owner_pending_and_can_reject_pending_form(): void
    {
        [$owner,$lote,$ownerUser]=$this->context('owner');
        $hidden=$this->form($owner,$lote,$ownerUser,'OwnerPending');
        $pending=$this->form($owner,$lote,$ownerUser,'Pending');
        $admin=User::factory()->create();
        DB::table('roles')->insertOrIgnore(['name'=>'super_admin','guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]);
        $admin->assignRole('super_admin');
        $permissions=collect(['aprobar_form::control','rechazar_form::control','update_form::control','delete_form::control'])->map(fn ($name) => Permission::firstOrCreate(['name'=>$name,'guard_name'=>'web']));
        $admin->givePermissionTo($permissions);
        $this->actingAs($admin);
        $component=Livewire::test(FormControlMonitor::class)->assertSuccessful()
            ->assertSee('FORMULARIO #'.$pending->id)->assertDontSee('FORMULARIO #'.$hidden->id)
            ->mountAction('reject',['form'=>$pending->id])->callMountedAction()->assertHasNoActionErrors();
        $this->assertSame('Denied',$pending->fresh()->status);
        $component->call('setStatus','attention')->assertSet('status','attention')
            ->assertSee('FORMULARIO #'.$pending->id)->assertSee('rechazados')->assertSee('Pendientes')
            ->mountAction('deleteForm',['form'=>$pending->id])->callMountedAction()->assertHasNoActionErrors();
        $this->assertSoftDeleted('form_controls',['id'=>$pending->id]);
    }

    private function form(Owner $owner,Lote $lote,User $user,string $status): FormControl
    {
        return FormControl::create(['owner_id'=>$owner->id,'user_id'=>$user->id,'access_type'=>['lote'],'lote_ids'=>[$lote->getNombre()],'income_type'=>['Inquilino'],'status'=>$status,'start_date_range'=>now()->addDay()->format('Y-m-d'),'start_time_range'=>'09:00','end_date_range'=>now()->addDays(2)->format('Y-m-d'),'end_time_range'=>'18:00']);
    }

    private function context(string $role): array
    {
        $suffix=uniqid();$owner=Owner::create(['first_name'=>'Monitor','last_name'=>$suffix,'email'=>"monitor{$suffix}@test.local",'user_id'=>'0']);
        $user=User::factory()->create(['owner_id'=>$owner->id]);$owner->update(['user_id'=>(string)$user->id]);
        DB::table('roles')->insertOrIgnore(['name'=>$role,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]);$user->assignRole($role);
        $sector=DB::table('sectors')->insertGetId(['name'=>'M','created_at'=>now(),'updated_at'=>now()]);$type=DB::table('lote_types')->insertGetId(['name'=>'Casa','created_at'=>now(),'updated_at'=>now()]);$status=DB::table('lote_statuses')->insertGetId(['name'=>'Activo','color'=>'violet','created_at'=>now(),'updated_at'=>now()]);
        $lote=Lote::create(['width'=>'10','height'=>'20','m2'=>'200','sector_id'=>$sector,'lote_id'=>random_int(1000,9999),'lote_type_id'=>$type,'lote_status_id'=>$status,'owner_id'=>$owner->id]);return [$owner->fresh(),$lote,$user];
    }
}
