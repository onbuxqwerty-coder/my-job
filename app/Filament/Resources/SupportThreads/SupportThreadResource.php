<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupportThreads;

use App\Filament\Resources\SupportThreads\Pages\ListSupportThreads;
use App\Filament\Resources\SupportThreads\Pages\ViewSupportThread;
use App\Filament\Resources\SupportThreads\Tables\SupportThreadsTable;
use App\Models\SupportThread;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SupportThreadResource extends Resource
{
    protected static ?string $model = SupportThread::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel  = 'Звернення підтримки';
    protected static ?string $modelLabel       = 'Звернення';
    protected static ?string $pluralModelLabel = 'Звернення підтримки';
    protected static string|UnitEnum|null $navigationGroup = 'Контент';
    protected static ?int    $navigationSort   = 20;

    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table
    {
        return SupportThreadsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportThreads::route('/'),
            'view'  => ViewSupportThread::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = SupportThread::whereHas(
            'messages',
            fn ($q) => $q->where('is_read', false)
        )->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }
}
