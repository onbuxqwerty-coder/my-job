<?php

declare(strict_types=1);

namespace App\Filament\Resources\SkillTags\Pages;

use App\Filament\Resources\SkillTags\SkillTagResource;
use Filament\Resources\Pages\EditRecord;

class EditSkillTag extends EditRecord
{
    protected static string $resource = SkillTagResource::class;
}
