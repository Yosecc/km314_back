<?php

namespace Tests\Feature;

use App\Models\FormControl;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FormControlTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_generates_a_quick_access_code_and_casts_multi_value_fields(): void
    {
        $formControl = $this->createFormControl();

        $this->assertMatchesRegularExpression('/^F-[A-Z0-9]{8}$/', $formControl->quick_access_code);
        $this->assertSame(['lote'], $formControl->access_type);
        $this->assertSame(['A12', 'B04'], $formControl->lote_ids);
        $this->assertSame(['Visita'], $formControl->income_type);
    }

    public function test_it_is_active_only_when_authorized_and_inside_a_date_range(): void
    {
        Carbon::setTestNow('2026-09-10 12:00:00');

        $formControl = $this->createFormControl(['status' => 'Authorized']);
        $formControl->dateRanges()->create([
            'start_date_range' => '2026-09-10',
            'start_time_range' => '07:00',
            'end_date_range' => '2026-09-10',
            'end_time_range' => '18:00',
            'date_unilimited' => false,
        ]);

        $this->assertTrue($formControl->fresh()->isDayRange());
        $this->assertTrue($formControl->fresh()->isActive());

        Carbon::setTestNow('2026-09-10 19:00:00');

        $this->assertFalse($formControl->fresh()->isDayRange());
    }

    public function test_approving_and_rejecting_record_the_responsible_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $approved = $this->createFormControl(['status' => 'Pending']);
        $approved->aprobar();

        $this->assertSame('Authorized', $approved->fresh()->status);
        $this->assertSame($user->id, $approved->fresh()->authorized_user_id);

        $denied = $this->createFormControl(['status' => 'Pending']);
        $denied->rechazar();

        $this->assertSame('Denied', $denied->fresh()->status);
        $this->assertSame($user->id, $denied->fresh()->denied_user_id);
    }

    private function createFormControl(array $attributes = []): FormControl
    {
        $user = User::factory()->create();

        return FormControl::create(array_merge([
            'access_type' => ['lote'],
            'lote_ids' => ['A12', 'B04'],
            'income_type' => ['Visita'],
            'status' => 'Pending',
            'user_id' => $user->id,
        ], $attributes));
    }
}
