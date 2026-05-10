<?php

declare(strict_types=1);

namespace App\Filament\Resources\SkillTags\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SkillTagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Назва')
                ->required()
                ->maxLength(100),

            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            Select::make('category')
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
                ])
                ->nullable()
                ->searchable(),
        ]);
    }
}
