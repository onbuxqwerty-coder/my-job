<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubscriptionPlans\Schemas;

use App\Enums\PlanType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Назва тарифу')
                            ->required()
                            ->maxLength(100),
                        Select::make('type')
                            ->label('Тип')
                            ->options(collect(PlanType::cases())->mapWithKeys(
                                fn (PlanType $t) => [$t->value => $t->value]
                            ))
                            ->required(),
                        TextInput::make('price_monthly')
                            ->label('Ціна (грн / місяць)')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->suffix('₴'),
                        Toggle::make('is_active')
                            ->label('Активний')
                            ->default(true)
                            ->inline(false),
                    ]),

                Textarea::make('description')
                    ->label('Опис')
                    ->columnSpanFull()
                    ->rows(2),

                Section::make('Функції тарифу')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('features.active_jobs')
                                    ->label('Активних вакансій (0 = ∞)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('features.applications_per_month')
                                    ->label('Відгуків / місяць (0 = ∞)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('features.team_members')
                                    ->label('Членів команди (0 = ∞)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('features.hot_per_month')
                                    ->label('HOT вакансій / місяць')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('features.hot_days')
                                    ->label('HOT днів (0 = ∞)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('features.top_per_month')
                                    ->label('TOP вакансій / місяць')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('features.top_days')
                                    ->label('TOP днів (0 = ∞)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),
                                Toggle::make('features.analytics')
                                    ->label('Аналітика')
                                    ->inline(false),
                                Toggle::make('features.message_templates')
                                    ->label('Шаблони повідомлень')
                                    ->inline(false),
                                Toggle::make('features.api_access')
                                    ->label('API доступ')
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }
}
