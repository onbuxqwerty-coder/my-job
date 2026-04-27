<?php

declare(strict_types=1);

namespace App\Filament\Resources\PaymentTransactions\Pages;

use App\Filament\Resources\PaymentTransactions\PaymentTransactionResource;
use App\Filament\Widgets\PaymentStatsWidget;
use App\Models\PaymentTransaction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentTransactions extends ListRecords
{
    protected static string $resource = PaymentTransactionResource::class;

    public function getSubheading(): ?string
    {
        $count = PaymentTransaction::whereMonth('processed_at', now()->month)
            ->whereYear('processed_at', now()->year)
            ->count();

        return "Цього місяця: {$count} транзакцій";
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PaymentStatsWidget::class,
        ];
    }
}
