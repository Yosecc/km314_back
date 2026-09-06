<?php
namespace App\Filament\Resources\PackageReceptionResource\Pages;

use App\Filament\Resources\PackageReceptionResource;
use App\Models\PackageReceptionFile;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Carbon\Carbon;

class EditPackageReception extends EditRecord
{
    protected static string $resource = PackageReceptionResource::class;
    protected array $uploads = [];
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $from = Carbon::parse($data['expected_from']);
        $until = Carbon::parse($data['expected_until']);
        $data['expected_date'] = $from->format('Y-m-d');
        $data['expected_from_time'] = $from->format('H:i');
        $data['expected_until_time'] = $until->format('H:i');
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->uploads = $data['registration_uploads'] ?? [];
        unset($data['registration_uploads']);
        $data['expected_from'] = Carbon::parse($data['expected_date'].' '.$data['expected_from_time']);
        $data['expected_until'] = Carbon::parse($data['expected_date'].' '.$data['expected_until_time']);
        unset($data['expected_date'], $data['expected_from_time'], $data['expected_until_time']);
        return $data;
    }
    protected function afterSave(): void { foreach ($this->uploads as $path) $this->record->files()->firstOrCreate(['path'=>$path],['category'=>PackageReceptionFile::REGISTRATION,'original_name'=>basename($path),'uploaded_by_user_id'=>auth()->id()]); }
    protected function getHeaderActions(): array { return [Actions\ViewAction::make()]; }
}
