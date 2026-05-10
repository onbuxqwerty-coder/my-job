<?php

declare(strict_types=1);

namespace App\Filament\Resources\EmployerSubscriptions;

use App\Filament\Resources\EmployerSubscriptions\Pages\ListEmployerSubscriptions;
use App\Filament\Resources\EmployerSubscriptions\Pages\ViewEmployerSubscription;
use App\Filament\Resources\EmployerSubscriptions\Tables\EmployerSubscriptionsTable;
use App\Models\EmployerSubscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EmployerSubscriptionResource extends Resource
{
    protected static ?string $model = EmployerSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel  = 'Підписки';
    protected static ?string $modelLabel       = 'Підписка';
    protected static ?string $pluralModelLabel = 'Підписки';
    protected static string|UnitEnum|null $navigationGroup = 'Фінанси';
    protected static ?int    $navigationSort   = 30;

    public static function canCreate(): bool { return false; }

    /** @param EmployerSubscription $record */
    public static function canEdit($record): bool { return false; }

    /** @param EmployerSubscription $record */
    public static function canDelete($record): bool { return false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return EmployerSubscriptionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployerSubscriptions::route('/'),
            'view'  => ViewEmployerSubscription::route('/{record}'),
        ];
    }
}
