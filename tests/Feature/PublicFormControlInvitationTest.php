<?php

namespace Tests\Feature;

use App\Models\FormControl;
use App\Models\FormControlPublicInvitation;
use App\Models\Lote;
use App\Models\Owner;
use App\Models\User;
use App\Models\FilesRequired;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use App\Filament\Resources\FormControlResource\Pages\ListFormControls;

class PublicFormControlInvitationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_link_has_fixed_context_creates_one_owner_pending_form_and_is_single_use(): void
    {
        Storage::fake('public');
        FilesRequired::updateOrCreate(['type'=>'Inquilino'],['name'=>'Documentos de inquilino','required'=>[['document'=>'DNI (Frente)','is_required'=>false,'date_is_required'=>false]],'no_required'=>[]]);
        [$owner,$lote,$ownerUser] = $this->context();
        [$invitation,$token] = FormControlPublicInvitation::issue($owner,$lote,$ownerUser);

        $this->get(route('form-control-public.show',$token))->assertOk()
            ->assertSee($owner->nombres())->assertSee($lote->getNombre())->assertSee('Inquilino');

        $payload = [
            'start_date'=>now()->addDay()->format('Y-m-d'),'start_time'=>'09:00',
            'end_date'=>now()->addDay()->format('Y-m-d'),'end_time'=>'18:00',
            'people'=>[['dni'=>'30111222','first_name'=>'Invitada','last_name'=>'Ejemplo','phone'=>'1','documents'=>[
                ['name'=>'DNI manipulado','file'=>UploadedFile::fake()->create('dni.pdf',10,'application/pdf')],
            ]]],
            'observations'=>'Creado desde enlace público','accept_terms'=>'1',
        ];
        $invalid=$payload; unset($invalid['end_time']);
        $this->from(route('form-control-public.show',$token))->post(route('form-control-public.store',$token),$invalid)
            ->assertRedirect(route('form-control-public.show',$token))->assertSessionHasErrors('end_time')
            ->assertSessionHasInput('people.0.first_name','Invitada');
        $this->post(route('form-control-public.store',$token),$payload)->assertRedirect();

        $form = FormControl::where('observations','Creado desde enlace público')->firstOrFail();
        $this->assertSame('OwnerPending',$form->status);
        $this->assertSame(['lote'],$form->access_type);
        $this->assertSame(['Inquilino'],$form->income_type);
        $this->assertSame([$lote->getNombre()],$form->lote_ids);
        $this->assertCount(1,$form->peoples);
        $this->assertSame('DNI (Frente)',$form->peoples->first()->files->first()->name);
        $this->assertNotNull($invitation->fresh()->used_at);

        $this->post(route('form-control-public.store',$token),$payload)->assertRedirect();
        $this->assertSame(1,FormControl::where('observations','Creado desde enlace público')->count());
    }

    public function test_owner_approval_moves_the_form_to_administration_pending(): void
    {
        [$owner,$lote,$ownerUser] = $this->context();
        $form = FormControl::create([
            'owner_id'=>$owner->id,'user_id'=>$ownerUser->id,'access_type'=>['lote'],
            'lote_ids'=>[$lote->getNombre()],'income_type'=>['Inquilino'],'status'=>'OwnerPending',
            'start_date_range'=>now()->subHour()->format('Y-m-d'),'start_time_range'=>now()->subHour()->format('H:i'),
            'end_date_range'=>now()->addDay()->format('Y-m-d'),'end_time_range'=>now()->addDay()->format('H:i'),
        ]);
        $form->approveByOwner($ownerUser);
        $this->assertSame('Pending',$form->fresh()->status);
        $this->assertSame($ownerUser->id,(int)$form->fresh()->owner_approved_by_user_id);
        $this->assertNotNull($form->fresh()->owner_approved_at);
        $this->assertSame('Pending',$form->fresh()->statusComputed());
    }

    public function test_owner_can_generate_the_single_use_link_from_the_form_list(): void
    {
        [$owner,$lote,$ownerUser] = $this->context();
        $ownerUser->forceFill(['is_terms_condition'=>true])->save();
        Permission::firstOrCreate(['name'=>'create_form::control','guard_name'=>'web']);
        $ownerUser->givePermissionTo('create_form::control');
        $this->actingAs($ownerUser);
        Livewire::test(ListFormControls::class)
            ->callAction('sharePublicForm',['lote_id'=>$lote->id])
            ->assertHasNoActionErrors()
            ->assertSet('mountedActions',['generatedPublicLink'])
            ->assertSee('Tu enlace está listo')
            ->assertSee('Compartir por WhatsApp')
            ->assertSee('Abrir enlace')
            ->assertSee('dura 7 días');
        $invitation = FormControlPublicInvitation::where('owner_id',$owner->id)->latest()->firstOrFail();
        $this->assertSame($lote->id,(int)$invitation->lote_id);
        $this->assertTrue($invitation->isAvailable());
    }

    private function context(): array
    {
        $suffix=uniqid();
        $owner=Owner::create(['first_name'=>'Laura','last_name'=>'Propietaria','email'=>"public{$suffix}@test.local",'user_id'=>'0']);
        $user=User::factory()->create(['owner_id'=>$owner->id]);
        $owner->update(['user_id'=>(string)$user->id]);
        DB::table('roles')->insertOrIgnore(['name'=>'owner','guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]);
        $user->assignRole('owner');
        $sector=DB::table('sectors')->insertGetId(['name'=>'Z','created_at'=>now(),'updated_at'=>now()]);
        $type=DB::table('lote_types')->insertGetId(['name'=>'Casa','created_at'=>now(),'updated_at'=>now()]);
        $status=DB::table('lote_statuses')->insertGetId(['name'=>'Activo','color'=>'green','created_at'=>now(),'updated_at'=>now()]);
        $lote=Lote::create(['width'=>'10','height'=>'20','m2'=>'200','sector_id'=>$sector,'lote_id'=>9876,'lote_type_id'=>$type,'lote_status_id'=>$status,'owner_id'=>$owner->id]);
        return [$owner->fresh(),$lote,$user];
    }
}
