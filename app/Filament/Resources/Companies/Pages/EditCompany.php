<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\City;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['city_id'])) {
            $data['location'] = City::find($data['city_id'])?->name ?? $data['location'] ?? '';
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
