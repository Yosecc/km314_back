<?php

namespace Tests\Unit;

use App\Models\ActivitiesPeople;
use App\Models\FormControl;
use App\Models\FormControlPeople;
use App\Services\AccessPeopleInsideService;
use PHPUnit\Framework\TestCase;

class AccessPeopleInsideServiceTest extends TestCase
{
    public function test_it_classifies_the_direct_access_models(): void
    {
        $this->assertSame(['owners'], AccessPeopleInsideService::categoryKeys($this->row('Owner')));
        $this->assertSame(['families'], AccessPeopleInsideService::categoryKeys($this->row('OwnerFamily')));
        $this->assertSame(['employees'], AccessPeopleInsideService::categoryKeys($this->row('Employee')));
        $this->assertSame(['spontaneous'], AccessPeopleInsideService::categoryKeys($this->row('OwnerSpontaneousVisit')));
    }

    public function test_it_uses_one_primary_category_for_forms_with_overlapping_values(): void
    {
        $form = new FormControl([
            'access_type' => ['hause', 'lote'],
            'income_type' => ['Inquilino', 'Visita Temporal (24hs)', 'Trabajador'],
        ]);
        $person = new FormControlPeople();
        $person->setRelation('formControl', $form);
        $row = $this->row('FormControl');
        $row->setRelation('formControlPeople', $person);

        $this->assertSame(
            ['workers'],
            AccessPeopleInsideService::categoryKeys($row),
        );
    }

    public function test_it_classifies_providers_and_incomplete_form_records(): void
    {
        $this->assertSame(['providers'], AccessPeopleInsideService::categoryKeys($this->row('ProveedorEmpleado')));
        $missingPerson = $this->row('FormControl');
        $missingPerson->setRelation('formControlPeople', null);

        $this->assertSame(['unclassified'], AccessPeopleInsideService::categoryKeys($missingPerson));
    }

    public function test_category_counts_always_add_up_to_the_total(): void
    {
        $rows = collect([
            $this->row('Owner'),
            $this->row('OwnerFamily'),
            $this->row('Employee'),
            $this->row('OwnerSpontaneousVisit'),
            $this->row('ProveedorEmpleado'),
            $this->row('UnknownAccessModel'),
        ]);

        $this->assertSame($rows->count(), array_sum(AccessPeopleInsideService::counts($rows)));
    }

    private function row(string $model): ActivitiesPeople
    {
        $row = new ActivitiesPeople();
        $row->setRawAttributes(['model' => $model, 'model_id' => 1], true);

        return $row;
    }
}
