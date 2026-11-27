<?php

namespace Tests\Feature;

use App\Models\FormControl;
use App\Models\FormControlPeople;
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
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use App\Filament\Resources\FormControlResource\Pages\ListFormControls;
use RuntimeException;

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
            'people'=>[['dni'=>'30111222','first_name'=>'Invitada','last_name'=>'Ejemplo','phone'=>'2215 70-8176','documents'=>[
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
        $this->assertSame('2215 70-8176',$form->peoples->first()->phone);
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

    public function test_public_link_can_create_a_tenant_visitor_form(): void
    {
        FilesRequired::updateOrCreate(
            ['type'=>FormControlPublicInvitation::TENANT_VISITOR],
            ['name'=>'Documentos para visitante de inquilino','required'=>[],'no_required'=>[]],
        );
        [$owner,$lote,$ownerUser] = $this->context();
        [, $token] = FormControlPublicInvitation::issue(
            $owner,
            $lote,
            $ownerUser,
            FormControlPublicInvitation::TENANT_VISITOR,
        );

        $this->get(route('form-control-public.show',$token))
            ->assertOk()
            ->assertSee(FormControlPublicInvitation::TENANT_VISITOR);

        $this->post(route('form-control-public.store',$token),[
            'start_date'=>now()->addDay()->format('Y-m-d'),'start_time'=>'09:00',
            'end_date'=>now()->addDay()->format('Y-m-d'),'end_time'=>'18:00',
            'people'=>[['dni'=>'30111222','first_name'=>'Visitante','last_name'=>'Ejemplo']],
            'observations'=>'Visita de inquilino','accept_terms'=>'1',
        ])->assertRedirect();

        $form = FormControl::where('observations','Visita de inquilino')->firstOrFail();
        $this->assertSame([FormControlPublicInvitation::TENANT_VISITOR],$form->income_type);
        $this->assertSame('OwnerPending',$form->status);
    }

    public function test_owner_can_generate_the_single_use_link_from_the_form_list(): void
    {
        [$owner,$lote,$ownerUser] = $this->context();
        $ownerUser->forceFill(['is_terms_condition'=>true])->save();
        Permission::firstOrCreate(['name'=>'create_form::control','guard_name'=>'web']);
        $ownerUser->givePermissionTo('create_form::control');
        $this->actingAs($ownerUser);
        Livewire::test(ListFormControls::class)
            ->callAction('sharePublicForm',[
                'lote_id'=>$lote->id,
                'income_type'=>FormControlPublicInvitation::TENANT_VISITOR,
            ])
            ->assertHasNoActionErrors()
            ->assertSet('mountedActions',['generatedPublicLink'])
            ->assertSee('Tu enlace está listo')
            ->assertSee('Compartir por WhatsApp')
            ->assertSee('Abrir enlace')
            ->assertSee('Dura 7 días');
        $invitation = FormControlPublicInvitation::where('owner_id',$owner->id)->latest()->firstOrFail();
        $this->assertSame($lote->id,(int)$invitation->lote_id);
        $this->assertSame(FormControlPublicInvitation::TENANT_VISITOR,$invitation->income_type);
        $this->assertTrue($invitation->isAvailable());
    }

    public function test_owner_can_generate_and_receive_an_extended_visit_form_from_its_own_link(): void
    {
        FilesRequired::updateOrCreate(
            ['type' => FormControlPublicInvitation::EXTENDED_VISIT],
            ['name' => 'Documentos de visita', 'required' => [
                ['document' => 'Documento de visita', 'is_required' => false, 'date_is_required' => false],
            ], 'no_required' => []],
        );
        [$owner, $lote, $ownerUser] = $this->context();
        $ownerUser->forceFill(['is_terms_condition' => true])->save();
        Permission::firstOrCreate(['name' => 'create_form::control', 'guard_name' => 'web']);
        $ownerUser->givePermissionTo('create_form::control');
        $this->actingAs($ownerUser);

        Livewire::test(ListFormControls::class)
            ->callAction('sharePublicForm', [
                'lote_id' => $lote->id,
                'income_type' => FormControlPublicInvitation::EXTENDED_VISIT,
            ])
            ->assertHasActionErrors(['income_type' => 'in']);
        $this->assertDatabaseMissing('form_control_public_invitations', ['owner_id' => $owner->id]);

        $action = Livewire::test(ListFormControls::class)
            ->callAction('shareExtendedVisitForm', ['lote_id' => $lote->id])
            ->assertHasNoActionErrors()
            ->assertSet('mountedActions', ['generatedPublicLink'])
            ->assertSee('Compartir por WhatsApp');
        $url = $action->get('mountedActionsArguments')[0]['url'];
        $invitation = FormControlPublicInvitation::resolveToken(basename($url));
        $this->assertSame(FormControlPublicInvitation::EXTENDED_VISIT, $invitation->income_type);

        $this->app['auth']->forgetGuards();
        $this->get($url)->assertOk()
            ->assertSee($lote->getNombre())
            ->assertSee(FormControlPublicInvitation::EXTENDED_VISIT)
            ->assertSee('Documento de visita');

        $this->post($url, [
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_date' => now()->addDays(2)->format('Y-m-d'),
            'end_time' => '18:00',
            'people' => [['dni' => '30111222', 'first_name' => 'Visitante', 'last_name' => 'Ejemplo']],
            'accept_terms' => '1',
        ])->assertRedirect($url)->assertSessionHas('submitted', true);

        $form = $invitation->fresh()->usedFormControl;
        $this->assertSame([FormControlPublicInvitation::EXTENDED_VISIT], $form->income_type);
        $this->assertSame([$lote->getNombre()], $form->lote_ids);
        $this->assertSame('OwnerPending', $form->status);
    }

    public function test_mobile_owner_can_issue_links_and_send_a_public_request_to_administration(): void
    {
        [$owner, $lote, $ownerUser] = $this->context();
        [, $otherLote, $otherUser] = $this->context();
        foreach ([$ownerUser, $otherUser] as $user) {
            foreach (['create_form::control', 'view_form::control', 'update_form::control'] as $name) {
                $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
            }
        }
        Sanctum::actingAs($ownerUser);

        $endpoint = '/api/form_control/mobile/public-invitations';
        $this->postJson($endpoint, ['lote_id' => $otherLote->id, 'income_type' => 'Inquilino'])->assertNotFound();
        $this->postJson($endpoint, ['lote_id' => $lote->id, 'income_type' => 'Trabajador'])->assertUnprocessable();

        foreach (['Inquilino', 'Visitante de Inquilino', 'Visita (+24hs)'] as $type) {
            $url = $this->postJson($endpoint, ['lote_id' => $lote->id, 'income_type' => $type])
                ->assertOk()->json('url');
            $this->assertSame($type, FormControlPublicInvitation::resolveToken(basename($url))->income_type);
        }

        $form = FormControl::create([
            'owner_id' => $owner->id, 'user_id' => $ownerUser->id, 'status' => 'OwnerPending',
            'income_type' => ['Visita (+24hs)'], 'lote_ids' => [$lote->getNombre()],
        ]);
        $this->postJson('/api/form_control/index')->assertOk()
            ->assertJsonPath('publicInvitationLots.0.id', $lote->id)
            ->assertJsonPath('misForms.0.id', $form->id)
            ->assertJsonPath('misForms.0.can_approve_by_owner', true)
            ->assertJsonCount(0, 'historicoForms');

        $approve = "/api/form_control/mobile/{$form->id}/approve-by-owner";
        Sanctum::actingAs($otherUser);
        $this->postJson($approve)->assertForbidden();
        Sanctum::actingAs($ownerUser);
        $this->postJson($approve)->assertOk()->assertJsonPath('status', 'Pending');
        $this->postJson($approve)->assertOk()->assertJsonPath('status', 'Pending');
        $this->assertSame($ownerUser->id, (int) $form->fresh()->owner_approved_by_user_id);
    }

    public function test_public_link_rejects_an_invalid_phone_with_a_friendly_message(): void
    {
        FilesRequired::updateOrCreate(
            ['type' => 'Inquilino'],
            ['name' => 'Documentos de inquilino', 'required' => [], 'no_required' => []],
        );
        [$owner, $lote, $ownerUser] = $this->context();
        [, $token] = FormControlPublicInvitation::issue($owner, $lote, $ownerUser);

        $this->from(route('form-control-public.show', $token))
            ->post(route('form-control-public.store', $token), [
                'start_date' => now()->addDay()->format('Y-m-d'),
                'start_time' => '09:00',
                'end_date' => now()->addDay()->format('Y-m-d'),
                'end_time' => '18:00',
                'people' => [[
                    'dni' => '30111222',
                    'first_name' => 'Invitada',
                    'last_name' => 'Ejemplo',
                    'phone' => '2215 / dato inválido',
                ]],
                'accept_terms' => '1',
            ])
            ->assertRedirect(route('form-control-public.show', $token))
            ->assertSessionHasErrors([
                'people.0.phone' => 'Ingrese un teléfono válido. Puede usar números, espacios, +, -, puntos y paréntesis.',
            ])
            ->assertSessionHasInput('people.0.phone', '2215 / dato inválido');

        $this->assertDatabaseMissing('form_controls', [
            'owner_id' => $owner->id,
            'status' => 'OwnerPending',
        ]);

        $this->get(route('form-control-public.show', $token))
            ->assertOk()
            ->assertSee('No pudimos enviar el formulario.')
            ->assertSee('Ingrese un teléfono válido.')
            ->assertDontSee('Stack trace');
    }

    public function test_public_link_hides_unexpected_errors_and_preserves_the_form_data(): void
    {
        FilesRequired::updateOrCreate(
            ['type' => 'Inquilino'],
            ['name' => 'Documentos de inquilino', 'required' => [], 'no_required' => []],
        );
        [$owner, $lote, $ownerUser] = $this->context();
        [, $token] = FormControlPublicInvitation::issue($owner, $lote, $ownerUser);

        $originalDispatcher = clone FormControlPeople::getEventDispatcher();
        FormControlPeople::creating(function (): void {
            throw new RuntimeException('Detalle interno que nunca debe mostrarse al usuario.');
        });

        try {
            $response = $this->from(route('form-control-public.show', $token))
                ->post(route('form-control-public.store', $token), [
                    'start_date' => now()->addDay()->format('Y-m-d'),
                    'start_time' => '09:00',
                    'end_date' => now()->addDay()->format('Y-m-d'),
                    'end_time' => '18:00',
                    'people' => [[
                        'dni' => '30111222',
                        'first_name' => 'Invitada',
                        'last_name' => 'Ejemplo',
                        'phone' => '+54 (221) 570-8176',
                    ]],
                    'observations' => 'Debe conservarse al volver',
                    'accept_terms' => '1',
                ]);
        } finally {
            FormControlPeople::setEventDispatcher($originalDispatcher);
        }

        $response
            ->assertRedirect(route('form-control-public.show', $token))
            ->assertSessionHasErrors([
                'submission' => 'No pudimos guardar el formulario. Revise los datos e intente nuevamente en unos minutos.',
            ])
            ->assertSessionHasInput('observations', 'Debe conservarse al volver');

        $this->assertDatabaseMissing('form_controls', [
            'owner_id' => $owner->id,
            'status' => 'OwnerPending',
            'observations' => 'Debe conservarse al volver',
        ]);

        $this->get(route('form-control-public.show', $token))
            ->assertOk()
            ->assertSee('No pudimos enviar el formulario.')
            ->assertSee('No pudimos guardar el formulario.')
            ->assertDontSee('Detalle interno que nunca debe mostrarse al usuario.')
            ->assertDontSee('Stack trace');
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
