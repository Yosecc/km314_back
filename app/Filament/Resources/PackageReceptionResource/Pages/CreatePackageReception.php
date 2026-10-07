<?php
namespace App\Filament\Resources\PackageReceptionResource\Pages;

use App\Filament\Resources\PackageReceptionResource;
use App\Models\PackageReceptionFile;
use App\Services\PackageReceptionService;
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;

class CreatePackageReception extends CreateRecord
{
    protected static string $resource = PackageReceptionResource::class;
    protected array $uploads = [];
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->uploads = $data['registration_uploads'] ?? [];
        unset($data['registration_uploads']);
        $data['expected_from'] = Carbon::parse($data['expected_date'].' '.$data['expected_from_time']);
        $data['expected_until'] = Carbon::parse($data['expected_date'].' '.$data['expected_until_time']);
        unset($data['expected_date'], $data['expected_from_time'], $data['expected_until_time']);
        $data['created_by_user_id'] = auth()->id();
        $data['status'] = 'expected';
        if (auth()->user()->hasRole('owner')) $data['owner_id'] = auth()->user()->owner_id;
        return $data;
    }
    protected function afterCreate(): void
    {
        foreach ($this->uploads as $path) $this->record->files()->create(['category'=>PackageReceptionFile::REGISTRATION,'path'=>$path,'original_name'=>basename($path),'uploaded_by_user_id'=>auth()->id()]);
        app(PackageReceptionService::class)->recordCreation($this->record, auth()->user());
    }
}
