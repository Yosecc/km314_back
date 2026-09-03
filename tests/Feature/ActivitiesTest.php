<?php

namespace Tests\Feature;

use App\Models\Activities;
use App\Models\ActivitiesPeople;
use App\Models\FormControl;
use App\Models\User;
use App\Services\CurrentPeopleInsideQuery;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActivitiesTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_an_activity_can_reference_a_form_control_and_people(): void
    {
        $formControl = $this->createFormControl();
        $activity = Activities::create([
            'form_control_id' => $formControl->id,
            'lote_ids' => 'A12',
            'tipo_entrada' => 3,
            'type' => 'Entry',
        ]);

        $person = $activity->peoples()->create([
            'model' => 'FormControl',
            'model_id' => 987,
        ]);

        $this->assertTrue($activity->fresh()->formControl->is($formControl));
        $this->assertTrue($activity->fresh()->peoples->contains($person));
    }

    public function test_the_current_people_query_returns_only_people_whose_latest_movement_is_entry(): void
    {
        Carbon::setTestNow('2026-09-10 08:00:00');
        $this->recordMovement('Employee', 10, 'Entry');
        $this->recordMovement('Employee', 20, 'Entry');

        Carbon::setTestNow('2026-09-10 09:00:00');
        $this->recordMovement('Employee', 10, 'Exit');

        $inside = CurrentPeopleInsideQuery::make()->get();

        $this->assertCount(1, $inside);
        $this->assertSame('Employee', $inside->first()->model);
        $this->assertSame(20, (int) $inside->first()->model_id);
    }

    public function test_a_new_entry_after_an_exit_marks_the_person_as_inside_again(): void
    {
        Carbon::setTestNow('2026-09-10 08:00:00');
        $this->recordMovement('Employee', 10, 'Entry');

        Carbon::setTestNow('2026-09-10 09:00:00');
        $this->recordMovement('Employee', 10, 'Exit');

        Carbon::setTestNow('2026-09-10 10:00:00');
        $latest = $this->recordMovement('Employee', 10, 'Entry');

        $inside = CurrentPeopleInsideQuery::make()->get();

        $this->assertCount(1, $inside);
        $this->assertTrue($inside->first()->is($latest));
    }

    private function recordMovement(string $model, int $modelId, string $type): ActivitiesPeople
    {
        $activity = Activities::create([
            'tipo_entrada' => 2,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $activity->peoples()->create([
            'model' => $model,
            'model_id' => $modelId,
        ]);
    }

    private function createFormControl(): FormControl
    {
        return FormControl::create([
            'access_type' => ['lote'],
            'lote_ids' => ['A12'],
            'income_type' => ['Visita'],
            'status' => 'Authorized',
            'user_id' => User::factory()->create()->id,
        ]);
    }
}
