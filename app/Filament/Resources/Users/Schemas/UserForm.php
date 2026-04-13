<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label("Ім'я")
                    ->required(),
                TextInput::make('email')
                    ->label('Електронна пошта')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('Пошта підтверджена'),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->required()
                    ->suffixActions([
                        Action::make('generate_password')
                            ->label('Згенерувати')
                            ->icon('heroicon-m-arrow-path')
                            ->action(function (TextInput $component) {
                                $password = Str::password(12);
                                $component->state($password);
                            }),
                    ]),
                Select::make('role')
                    ->label('Роль')
                    ->options(collect(UserRole::cases())->mapWithKeys(
                        fn (UserRole $role) => [$role->value => $role->label()]
                    ))
                    ->placeholder('Оберіть роль')
                    ->default(UserRole::Candidate->value)
                    ->required(),
                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->placeholder('+380XXXXXXXXX')
                    ->dehydrateStateUsing(function (?string $state): ?string {
                        if (!$state) return null;
                        $digits = preg_replace('/\D/', '', $state);
                        if (str_starts_with($digits, '380')) return '+' . $digits;
                        if (str_starts_with($digits, '0'))   return '+38' . $digits;
                        return '+38' . $digits;
                    }),
                TextInput::make('telegram_id')
                    ->label('Telegram ID')
                    ->numeric()
                    ->helperText(new HtmlString(
                        'Як дізнатися свій Telegram ID:<br>' .
                        'Найпростіший спосіб — написати боту <strong><a href="https://t.me/userinfobot" target="_blank">@userinfobot</a></strong> ' .
                        'у Telegram, який миттєво надішле ваш числовий ID.'
                    )),
            ]);
    }
}
