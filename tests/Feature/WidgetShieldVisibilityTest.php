<?php

namespace Tests\Feature;

use App\Filament\Widgets\FormIncidentComplianceWidget;
use App\Filament\Widgets\FormIncidentStatsWidget;
use App\Filament\Widgets\AccessControlStats;
use App\Filament\Widgets\RecurrentVisitorApprovalStats;
use App\Models\Owner;
use App\Models\RecurrentVisitor;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class WidgetShieldVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_every_registered_widget_is_hidden_without_its_shield_permission(): void
    {
        $role = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $widgetPermissionIds = Permission::where('name', 'like', 'widget\_%')->pluck('id');
        DB::table('role_has_permissions')->where('role_id', $role->id)->whereIn('permission_id', $widgetPermissionIds)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create(['is_terms_condition' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        foreach (FilamentShield::getWidgets() as $widget) {
            $class = $widget['class'];
            $this->assertFalse($class::canView(), "El widget {$class} ignoró su permiso Shield.");
        }

        $permission = Permission::firstOrCreate(['name' => 'widget_AccessControlStats', 'guard_name' => 'web']);
        $user->givePermissionTo($permission);

        $this->assertTrue(AccessControlStats::canView());
    }

    public function test_super_admin_only_sees_widgets_explicitly_selected_in_shield(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate([
            'name' => 'widget_AccessControlStats',
            'guard_name' => 'web',
        ]);

        $role->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $this->assertFalse(AccessControlStats::canView());
        $this->assertFalse(FormIncidentStatsWidget::canView());

        $role->givePermissionTo($permission);

        $this->assertTrue(AccessControlStats::canView());

        $incidentPermission = Permission::firstOrCreate([
            'name' => 'widget_FormIncidentStatsWidget',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($incidentPermission);

        $this->assertTrue(FormIncidentStatsWidget::canView());
    }

    public function test_incident_stats_widget_uses_the_monitor_visual_summary(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate([
            'name' => 'widget_FormIncidentStatsWidget',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        Livewire::test(FormIncidentStatsWidget::class)
            ->assertSuccessful()
            ->assertSee('Formularios de incidentes')
            ->assertSee('Sin leer hoy')
            ->assertSee('Sin leer esta semana')
            ->assertSee('Total sin leer');
    }

    public function test_access_control_widget_uses_the_monitor_summary_design(): void
    {
        $this->assertSame('Control de Acceso', FilamentShield::getLocalizedWidgetLabel(AccessControlStats::class));

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate([
            'name' => 'widget_AccessControlStats',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        Livewire::test(AccessControlStats::class)
            ->assertSuccessful()
            ->assertSee('Control de Acceso')
            ->assertSee('Personas adentro')
            ->assertSee('Propietarios')
            ->assertSee('Trabajadores');
    }

    public function test_incident_compliance_widget_has_a_clear_shield_label(): void
    {
        $this->assertSame(
            'Widget de Incidentes',
            FilamentShield::getLocalizedWidgetLabel(FormIncidentComplianceWidget::class),
        );
    }

    public function test_recurrent_visitors_widget_alerts_admin_about_pending_approvals(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate([
            'name' => 'widget_RecurrentVisitorApprovalStats',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);
        $owner = Owner::create([
            'first_name' => 'Ana',
            'last_name' => 'Propietaria',
            'email' => uniqid('owner').'@test.local',
            'user_id' => '0',
        ]);
        RecurrentVisitor::create([
            'owner_id' => $owner->id,
            'dni' => '30111222',
            'first_name' => 'Visitante',
            'last_name' => 'Pendiente',
            'status' => 'pendiente',
            'user_id' => $user->id,
        ]);
        RecurrentVisitor::create([
            'owner_id' => $owner->id,
            'dni' => '30222333',
            'first_name' => 'Visitante',
            'last_name' => 'Aprobado',
            'status' => 'aprobado',
            'user_id' => $user->id,
        ]);
        $this->actingAs($user);

        $this->assertTrue(RecurrentVisitorApprovalStats::canView());
        Livewire::test(RecurrentVisitorApprovalStats::class)
            ->assertSuccessful()
            ->assertSee('Visitantes Recurrentes')
            ->assertSee('Hay visitantes pendientes por aprobar')
            ->assertSee('Pendientes de aprobación')
            ->assertSee('Aprobados')
            ->assertSee('Rechazados')
            ->assertSee('Total registrados');
    }
}
