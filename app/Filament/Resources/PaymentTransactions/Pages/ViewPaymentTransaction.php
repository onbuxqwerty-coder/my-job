<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentTransactions\Pages;

use App\Filament\Resources\PaymentTransactions\PaymentTransactionResource;
use App\Models\Vacancy;
use App\Payments\CheckoutService;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPaymentTransaction extends ViewRecord
{
    protected static string $resource = PaymentTransactionResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Транзакція')
                ->columns(2)
                ->schema([
                    TextEntry::make('event_id')
                        ->label('ID події')
                        ->copyable(),

                    TextEntry::make('gateway')
                        ->label('Провайдер')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'mono'      => 'success',
                            'wayforpay' => 'info',
                            'liqpay'    => 'warning',
                            'stripe'    => 'primary',
                            default     => 'gray',
                        })
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'mono'      => 'MonoPay',
                            'wayforpay' => 'WayForPay',
                            'liqpay'    => 'LiqPay',
                            'stripe'    => 'Stripe',
                            default     => ucfirst($state),
                        }),

                    TextEntry::make('order_id')
                        ->label('Order ID')
                        ->copyable(),

                    TextEntry::make('processed_at')
                        ->label('Оброблено')
                        ->dateTime('d.m.Y H:i:s'),
                ]),

            Section::make('Деталі оплати')
                ->columns(2)
                ->schema([
                    TextEntry::make('days_label')
                        ->label('Тариф')
                        ->state(function ($record): string {
                            [, $days] = CheckoutService::parseOrderId($record->order_id);
                            return $days ? "{$days} днів" : '—';
                        }),

                    TextEntry::make('amount_label')
                        ->label('Сума')
                        ->state(function ($record): string {
                            [, $days] = CheckoutService::parseOrderId($record->order_id);
                            if (! $days) {
                                return '—';
                            }
                            $kopecks = config("payments.prices.{$days}");
                            return $kopecks
                                ? number_format($kopecks / 100, 0, '.', ' ') . ' ₴'
                                : '—';
                        }),
                ]),

            Section::make('Вакансія')
                ->schema([
                    TextEntry::make('vacancy_info')
                        ->label('Вакансія')
                        ->state(function ($record): string {
                            [$vacancyId] = CheckoutService::parseOrderId($record->order_id);
                            if (! $vacancyId) {
                                return 'Невідомо';
                            }
                            $vacancy = Vacancy::find($vacancyId);
                            return $vacancy
                                ? "#{$vacancy->id} — {$vacancy->title} ({$vacancy->status->label()})"
                                : "#{$vacancyId} (вакансію видалено)";
                        }),
                ]),
        ]);
    }
}
