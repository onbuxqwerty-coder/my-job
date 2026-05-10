<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployerSubscriptions\Pages;

use App\Filament\Resources\EmployerSubscriptions\EmployerSubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployerSubscriptions extends ListRecords
{
    protected static string $resource = EmployerSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
