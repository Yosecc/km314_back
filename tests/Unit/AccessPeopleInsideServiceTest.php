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
        $this->assertSame(['employees'], AccessPeopleInsideService::categoryKeys($this->row('Employee')));
        $this->assertSame(['spontaneous'], AccessPeopleInsideService::categoryKeys($this->row('OwnerSpontaneousVisit')));
    }

    public function test_it_supports_historical_house_value_and_overlapping_form_categories(): void
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
            ['common_visitors', 'tenants', 'workers', 'visits'],
            AccessPeopleInsideService::categoryKeys($row),
        );
    }

    public function test_a_provider_or_a_missing_form_person_is_only_included_in_the_total(): void
    {
        $this->assertSame([], AccessPeopleInsideService::categoryKeys($this->row('ProveedorEmpleado')));
        $missingPerson = $this->row('FormControl');
        $missingPerson->setRelation('formControlPeople', null);

        $this->assertSame([], AccessPeopleInsideService::categoryKeys($missingPerson));
    }

    private function row(string $model): ActivitiesPeople
    {
        $row = new ActivitiesPeople();
        $row->setRawAttributes(['model' => $model, 'model_id' => 1], true);

        return $row;
    }
}
