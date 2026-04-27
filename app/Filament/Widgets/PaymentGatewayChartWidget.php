<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\PaymentTransaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentGatewayChartWidget extends ChartWidget
{
    protected static ?int    $sort            = 4;
    protected ?string        $heading         = 'Платежі по днях';
    protected ?string        $maxHeight       = '200px';
    protected ?string        $pollingInterval = '600s';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7'  => 'Останні 7 днів',
            '30' => 'Останні 30 днів',
            '90' => 'Останні 90 днів',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;

        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->format('d.m');
        }

        $gateways = [
            'mono'      => 'MonoPay',
            'wayforpay' => 'WayForPay',
            'liqpay'    => 'LiqPay',
            'stripe'    => 'Stripe',
        ];
        $colors = [
            'mono'      => 'rgb(34, 197, 94)',
            'wayforpay' => 'rgb(59, 130, 246)',
            'liqpay'    => 'rgb(234, 179, 8)',
            'stripe'    => 'rgb(139, 92, 246)',
        ];

        $data = PaymentTransaction::query()
            ->where('processed_at', '>=', now()->subDays($days))
            ->select('gateway', DB::raw('DATE(processed_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date', 'gateway')
            ->orderBy('date')
            ->get()
            ->groupBy('gateway');

        $datasets = [];
        foreach ($gateways as $gwKey => $gwLabel) {
            $gwData  = $data->get($gwKey, collect());
            $indexed = $gwData->pluck('count', 'date')->toArray();

            $values = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date     = now()->subDays($i)->format('Y-m-d');
                $values[] = $indexed[$date] ?? 0;
            }

            if (array_sum($values) > 0) {
                $rgb        = $colors[$gwKey];
                $rgba       = str_replace('rgb(', 'rgba(', $rgb);
                $rgba       = str_replace(')', ', 0.1)', $rgba);

                $datasets[] = [
                    'label'           => $gwLabel,
                    'data'            => $values,
                    'borderColor'     => $rgb,
                    'backgroundColor' => $rgba,
                    'fill'            => true,
                    'tension'         => 0.3,
                ];
            }
        }

        return [
            'labels'   => $labels,
            'datasets' => $datasets,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
