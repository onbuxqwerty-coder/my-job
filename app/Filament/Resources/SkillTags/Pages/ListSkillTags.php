<?php

declare(strict_types=1);

namespace App\Filament\Resources\SkillTags\Pages;

use App\Filament\Resources\SkillTags\SkillTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSkillTags extends ListRecords
{
    protected static string $resource = SkillTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Додати навичку'),
        ];
    }
}
