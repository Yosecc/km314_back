<?php

namespace Tests\Feature;

use App\Filament\Resources\InterestedResource\Pages\ManageInteresteds;
use App\Models\Interested;
use App\Models\InterestedOrigin;
use App\Models\User;
use App\Services\InterestedCsv;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InterestedCsvTest extends TestCase
{
    use DatabaseTransactions;

    public function test_csv_round_trip_preserves_values_and_skips_registered_emails(): void
    {
        $origin = InterestedOrigin::create(['name' => 'Prueba CSV']);
        $source = Interested::create([
            'first_name' => 'María',
            'last_name' => 'Pérez',
            'email' => 'maria-csv@example.test',
            'phone' => '+54 9 11 5555-1234',
            'interested_origins_id' => $origin->id,
            'observations' => "=1+1; nota\nSegunda línea",
        ]);
        $csv = app(InterestedCsv::class);
        $stream = fopen('php://temp', 'w+b');

        try {
            $csv->export($stream);
            rewind($stream);
            $this->assertStringStartsWith("\xEF\xBB\xBFfirst_name;", stream_get_contents($stream));

            rewind($stream);
            $result = $csv->import($stream, true);
            $this->assertGreaterThanOrEqual(1, $result['skipped']);

            $source->update(['email' => 'anterior-csv@example.test']);
            rewind($stream);
            $csv->import($stream, true);

            $copy = Interested::where('email', 'maria-csv@example.test')->firstOrFail();
            $this->assertSame($source->first_name, $copy->first_name);
            $this->assertSame($source->phone, $copy->phone);
            $this->assertSame($source->observations, $copy->observations);
        } finally {
            fclose($stream);
        }
    }

    public function test_invalid_row_rolls_back_earlier_rows(): void
    {
        $origin = InterestedOrigin::create(['name' => 'Origen CSV']);
        $source = Interested::create([
            'first_name' => 'Persona',
            'last_name' => 'CSV',
            'interested_origins_id' => $origin->id,
        ]);
        $csv = app(InterestedCsv::class);
        $stream = fopen('php://temp', 'w+b');

        try {
            $csv->export($stream);
            fputcsv($stream, ['incompleto'], ';', '"', '');
            rewind($stream);

            try {
                $csv->import($stream, true);
                $this->fail('Se esperaba un error de validación.');
            } catch (ValidationException $exception) {
                $this->assertStringContainsString('Fila ', $exception->getMessage());
            }

            $this->assertSame(1, Interested::where('interested_origins_id', $source->interested_origins_id)->count());
        } finally {
            fclose($stream);
        }
    }

    public function test_list_shows_csv_actions_only_with_their_shield_permissions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach (['view_any_interested', 'create_interested'] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        Livewire::test(ManageInteresteds::class)
            ->assertActionHidden('exportCsv')
            ->assertActionHidden('importCsv');

        foreach (['export_interested', 'import_interested'] as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        Livewire::test(ManageInteresteds::class)
            ->assertActionVisible('exportCsv')
            ->assertActionVisible('importCsv')
            ->callAction('exportCsv')
            ->assertFileDownloaded('interesados-'.now()->format('Y-m-d').'.csv');
    }
}
