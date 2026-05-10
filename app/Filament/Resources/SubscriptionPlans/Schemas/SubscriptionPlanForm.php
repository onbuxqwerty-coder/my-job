<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubscriptionPlans\Schemas;

use App\Enums\PlanType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Назва')
                ->required()
                ->maxLength(100),

            Select::make('type')
                ->label('Тип')
                ->options(
                    collect(PlanType::cases())
                        ->mapWithKeys(fn ($case) => [$case->value => ucfirst($case->value)])
                        ->toArray()
                )
                ->required(),

            TextInput::make('price_monthly')
                ->label('Ціна (грн/міс)')
                ->numeric()
                ->required()
                ->minValue(0),

            Toggle::make('is_active')
                ->label('Активний')
                ->default(true),

            Section::make('Функції плану')
                ->columns(2)
                ->schema([
                    TextInput::make('features.active_jobs')
                        ->label('Активних вакансій')
                        ->helperText('0 = необмежено')
                        ->numeric()
                        ->default(0),

                    TextInput::make('features.applications_per_month')
                        ->label('Заявок на місяць')
                        ->helperText('0 = необмежено')
                        ->numeric()
                        ->default(0),

                    TextInput::make('features.hot_per_month')
                        ->label('HOT на місяць')
                        ->numeric()
                        ->default(0),

                    TextInput::make('features.top_per_month')
                        ->label('TOP на місяць')
                        ->numeric()
                        ->default(0),

                    TextInput::make('features.hot_days')
                        ->label('Днів HOT')
                        ->helperText('0 = необмежено')
                        ->numeric()
                        ->default(0),

                    TextInput::make('features.top_days')
                        ->label('Днів TOP')
                        ->helperText('0 = необмежено')
                        ->numeric()
                        ->default(0),

                    TextInput::make('features.team_members')
                        ->label('Членів команди')
                        ->helperText('0 = необмежено')
                        ->numeric()
                        ->default(0),

                    Toggle::make('features.analytics')
                        ->label('Аналітика')
                        ->default(false),

                    Toggle::make('features.message_templates')
                        ->label('Шаблони повідомлень')
                        ->default(false),

                    Toggle::make('features.api_access')
                        ->label('API доступ')
                        ->default(false),
                ]),
        ]);
    }
}
