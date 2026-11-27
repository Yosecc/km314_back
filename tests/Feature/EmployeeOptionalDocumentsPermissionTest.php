<?php

namespace Tests\Feature;

use App\Filament\Resources\EmployeeResource;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeOptionalDocumentsPermissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_shield_exposes_the_optional_documents_permission(): void
    {
        $this->assertContains('make_documents_optional', EmployeeResource::getPermissionPrefixes());
    }

    public function test_super_admin_can_choose_whether_documents_are_optional(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'make_documents_optional_employee',
            'guard_name' => 'web',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $role->revokePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $method = new ReflectionMethod(EmployeeResource::class, 'documentsAreOptionalForCurrentUser');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(null));

        $role->givePermissionTo($permission);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $this->assertTrue($method->invoke(null));
    }
}
