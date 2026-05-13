<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportThreads\Pages;

use App\Filament\Resources\SupportThreads\SupportThreadResource;
use App\Models\SupportThread;
use Filament\Resources\Pages\ListRecords;

class ListSupportThreads extends ListRecords
{
    protected static string $resource = SupportThreadResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getSubheading(): ?string
    {
        $unread = SupportThread::whereHas(
            'messages',
            fn ($q) => $q->where('is_read', false)
        )->count();

        return $unread > 0 ? "Непрочитаних звернень: {$unread}" : null;
    }
}
