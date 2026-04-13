<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'Редагувати Користувача';
    }

    public function getBreadcrumb(): string
    {
        return 'Редагувати';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Збережено';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Видалити'),
        ];
    }
}
