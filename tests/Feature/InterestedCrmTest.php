<?php

namespace Tests\Feature;

use App\Filament\Pages\InterestedCalendar;
use App\Filament\Pages\InterestedMonitor;
use App\Filament\Widgets\InterestedCalendarWidget;
use App\Models\Interested;
use App\Models\InterestedFollowUp;
use App\Models\InterestedLevel;
use App\Models\InterestedOrigin;
use App\Models\InterestedStatus;
use App\Models\InterestedType;
use App\Models\User;
use App\Services\InterestedCrmService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InterestedCrmTest extends TestCase
{
    use DatabaseTransactions;

    public function test_contact_history_and_follow_up_keep_summary_dates_in_sync(): void
    {
        $user = User::factory()->create();
        $interested = $this->makeInterested($user);
        $nextContact = now()->addDay()->startOfMinute();

        $contact = app(InterestedCrmService::class)->registerContact($interested, $user, [
            'channel' => 'whatsapp',
            'direction' => 'outgoing',
            'result' => 'Solicitó información',
            'notes' => 'Se enviaron detalles del lote.',
            'contacted_at' => now()->startOfMinute(),
            'next_contact_at' => $nextContact,
            'follow_up_title' => 'Confirmar visita',
            'assigned_user_id' => $user->id,
        ]);

        $interested->refresh();
        $followUp = $interested->pendingFollowUps()->firstOrFail();

        $this->assertSame($contact->contacted_at->toDateTimeString(), $interested->last_contacted_at->toDateTimeString());
        $this->assertSame($nextContact->toDateTimeString(), $interested->next_contact_at->toDateTimeString());
        $this->assertSame('Confirmar visita', $followUp->title);

        app(InterestedCrmService::class)->completeFollowUp($followUp, $user, [
            'channel' => 'phone',
            'direction' => 'outgoing',
            'result' => 'Visita confirmada',
            'notes' => 'Confirmó asistencia.',
        ]);

        $this->assertNull($interested->fresh()->next_contact_at);
        $this->assertSame(2, $interested->contacts()->count());
        $this->assertSame(InterestedFollowUp::COMPLETED, $followUp->fresh()->status);
    }

    public function test_due_follow_up_sends_one_database_reminder(): void
    {
        $user = User::factory()->create();
        $interested = $this->makeInterested($user);
        $followUp = app(InterestedCrmService::class)->scheduleFollowUp($interested, $user, [
            'title' => 'Llamar al interesado',
            'channel' => 'phone',
            'due_at' => now()->subMinute(),
            'reminder_at' => now()->subMinutes(5),
            'assigned_user_id' => $user->id,
        ]);

        $this->artisan('interesteds:process-reminders')->assertSuccessful();
        $this->artisan('interesteds:process-reminders')->assertSuccessful();

        $this->assertNotNull($followUp->fresh()->notified_at);
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $user->id)->count());
    }

    public function test_monitor_respects_shield_and_renders_operational_cards(): void
    {
        $user = User::factory()->create();
        $interested = $this->makeInterested($user);
        $this->actingAs($user);

        $this->assertFalse(InterestedMonitor::canAccess());

        foreach (['page_InterestedMonitor', 'view_any_interested'] as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        $this->assertTrue(InterestedMonitor::canAccess());
        Livewire::test(InterestedMonitor::class)
            ->assertSuccessful()
            ->assertSee('Monitor de Interesados')
            ->assertSee($interested->full_name)
            ->assertSee('Sin seguimiento');
    }

    public function test_crm_permissions_and_default_statuses_exist(): void
    {
        $this->assertSame(9, InterestedStatus::query()->count());
        $this->assertDatabaseHas('permissions', ['name' => 'page_InterestedMonitor', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'complete_interested::follow::up', 'guard_name' => 'web']);
        $this->assertDatabaseHas('permissions', ['name' => 'widget_InterestedFollowUpStats', 'guard_name' => 'web']);
    }

    public function test_commercial_calendar_respects_shield_and_renders(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertFalse(InterestedCalendar::canAccess());

        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'page_InterestedCalendar', 'guard_name' => 'web']));

        $this->assertFalse(InterestedCalendar::canAccess());
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'view_any_interested::follow::up', 'guard_name' => 'web']));

        $this->assertTrue(InterestedCalendar::canAccess());
        Livewire::test(InterestedCalendar::class)->assertSuccessful()->assertSee('Agenda comercial');
        Livewire::test(InterestedCalendarWidget::class)->assertSuccessful();
    }

    private function makeInterested(User $user): Interested
    {
        $suffix = uniqid();
        $origin = InterestedOrigin::create(['name' => 'Instagram '.$suffix]);
        $type = InterestedType::create(['name' => 'Compra '.$suffix]);
        $level = InterestedLevel::query()->where('name', 'Alto')->firstOrFail();
        $status = InterestedStatus::query()->where('name', 'Nuevo')->firstOrFail();

        return Interested::create([
            'first_name' => 'Julia',
            'last_name' => 'Seguimiento '.$suffix,
            'email' => 'julia-'.$suffix.'@example.test',
            'phone' => '+54 9 11 5555-1234',
            'interested_origins_id' => $origin->id,
            'interested_type_id' => $type->id,
            'interested_level_id' => $level->id,
            'interested_status_id' => $status->id,
            'assigned_user_id' => $user->id,
        ]);
    }
}
