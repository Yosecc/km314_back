<?php

namespace Tests\Feature;

use App\Filament\Widgets\VisitaEspontaneaEnElBarrio;
use App\Models\User;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
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

        $permission = Permission::firstOrCreate(['name' => 'widget_VisitaEspontaneaEnElBarrio', 'guard_name' => 'web']);
        $user->givePermissionTo($permission);

        $this->assertTrue(VisitaEspontaneaEnElBarrio::canView());
    }

    public function test_super_admin_only_sees_widgets_explicitly_selected_in_shield(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate([
            'name' => 'widget_VisitaEspontaneaEnElBarrio',
            'guard_name' => 'web',
        ]);

        $role->revokePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $this->assertFalse(VisitaEspontaneaEnElBarrio::canView());

        $role->givePermissionTo($permission);

        $this->assertTrue(VisitaEspontaneaEnElBarrio::canView());
    }
}
