<?php

declare(strict_types=1);

namespace App\Filament\Resources\InterviewRequests\Pages;

use App\Filament\Resources\InterviewRequests\InterviewRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListInterviewRequests extends ListRecords
{
    protected static string $resource = InterviewRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
