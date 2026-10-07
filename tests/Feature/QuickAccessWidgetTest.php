<?php

namespace Tests\Feature;

use App\Filament\Widgets\QuickAccessWidget;
use App\Models\QuickAccessLink;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QuickAccessWidgetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_widget_only_displays_the_authenticated_users_links(): void
    {
        $user = $this->authorizedUser();
        $other = User::factory()->create();
        QuickAccessLink::create(['user_id'=>$user->id,'name'=>'Mis formularios','url'=>'/form-controls','icon'=>'heroicon-o-document-text','sort_order'=>0]);
        QuickAccessLink::create(['user_id'=>$other->id,'name'=>'Enlace ajeno','url'=>'/private','icon'=>'heroicon-o-link','sort_order'=>0]);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->assertSuccessful()
            ->assertSee('Accesos Rápidos')
            ->assertSee('Mis formularios')
            ->assertDontSee('Enlace ajeno');
    }

    public function test_user_can_create_edit_reorder_and_remove_links_from_configuration_modal(): void
    {
        $user = $this->authorizedUser();
        $first = QuickAccessLink::create(['user_id'=>$user->id,'name'=>'Anterior','url'=>'/anterior','icon'=>'heroicon-o-link','sort_order'=>0]);
        $removed = QuickAccessLink::create(['user_id'=>$user->id,'name'=>'Quitar','url'=>'/quitar','icon'=>'heroicon-o-link','sort_order'=>1]);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->mountAction('settings')
            ->set('mountedActionsData.0.links', [
                ['id'=>$first->id,'name'=>'Inicio actualizado','url'=>'/','icon'=>'heroicon-o-home'],
                ['name'=>'Sitio KM314','url'=>'https://kilometro314.com','icon'=>'heroicon-o-globe-alt'],
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('quick_access_links', ['id'=>$first->id,'user_id'=>$user->id,'name'=>'Inicio actualizado','url'=>'/','sort_order'=>0]);
        $this->assertDatabaseMissing('quick_access_links', ['id'=>$removed->id]);
        $this->assertDatabaseHas('quick_access_links', ['user_id'=>$user->id,'name'=>'Sitio KM314','sort_order'=>1]);
    }

    private function authorizedUser(): User
    {
        $permission = Permission::firstOrCreate(['name'=>'widget_QuickAccessWidget','guard_name'=>'web']);
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }
}
