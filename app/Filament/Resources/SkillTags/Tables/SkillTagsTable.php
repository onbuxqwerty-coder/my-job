<?php

declare(strict_types=1);

namespace App\Filament\Resources\SkillTags\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SkillTagsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                TextColumn::make('category')
                    ->label('Категорія')
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('candidate_users_count')
                    ->label('Кандидатів')
                    ->counts('candidateUsers')
                    ->sortable(),

                TextColumn::make('vacancies_count')
                    ->label('Вакансій')
                    ->counts('vacancies')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Категорія')
                    ->options([
                        'backend'    => 'Backend',
                        'frontend'   => 'Frontend',
                        'design'     => 'Design',
                        'management' => 'Management',
                        'devops'     => 'DevOps',
                        'qa'         => 'QA',
                        'data'       => 'Data',
                        'other'      => 'Інше',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label('Редагувати'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Видалити'),
                ]),
            ])
            ->defaultSort('name');
    }
}
