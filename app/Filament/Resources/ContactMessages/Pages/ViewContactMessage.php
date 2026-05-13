<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markRead')
                ->label('Позначити прочитаним')
                ->icon('heroicon-o-check')
                ->visible(fn () => ! $this->record->is_read)
                ->action(function (): void {
                    $this->record->update(['is_read' => true]);
                    $this->refreshFormData(['is_read']);
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Відправник')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label('Ім\'я'),

                    TextEntry::make('contact')
                        ->label('Email або Telegram')
                        ->copyable(),

                    TextEntry::make('role')
                        ->label('Роль')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state->label()),

                    TextEntry::make('topic')
                        ->label('Тема')
                        ->placeholder('—'),
                ]),

            Section::make('Повідомлення')
                ->schema([
                    TextEntry::make('message')
                        ->label('')
                        ->prose(),
                ]),

            Section::make('Статус')
                ->columns(2)
                ->schema([
                    TextEntry::make('is_read')
                        ->label('Прочитано')
                        ->formatStateUsing(fn (bool $state) => $state ? 'Так' : 'Ні'),

                    TextEntry::make('created_at')
                        ->label('Отримано')
                        ->dateTime('d.m.Y H:i'),
                ]),
        ]);
    }
}
