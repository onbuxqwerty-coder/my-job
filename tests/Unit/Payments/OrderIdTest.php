<?php

declare(strict_types=1);

namespace Tests\Unit\Payments;

use App\Payments\CheckoutService;
use Tests\TestCase;

class OrderIdTest extends TestCase
{
    public function test_build_order_id_generates_valid_format(): void
    {
        $orderId = CheckoutService::buildOrderId(42, 30);
        $this->assertMatchesRegularExpression('/^vac_42_30_[a-f0-9]+$/', $orderId);
    }

    public function test_parse_order_id_extracts_vacancy_id_and_days(): void
    {
        $orderId = CheckoutService::buildOrderId(42, 30);
        [$vacancyId, $days] = CheckoutService::parseOrderId($orderId);

        $this->assertSame(42, $vacancyId);
        $this->assertSame(30, $days);
    }

    public function test_parse_order_id_returns_nulls_for_invalid_format(): void
    {
        [$vacancyId, $days] = CheckoutService::parseOrderId('unknown_format');

        $this->assertNull($vacancyId);
        $this->assertNull($days);
    }

    public function test_each_build_order_id_is_unique(): void
    {
        $ids = array_map(fn () => CheckoutService::buildOrderId(1, 30), range(1, 100));
        $this->assertSame(100, count(array_unique($ids)));
    }

    public function test_parse_order_id_handles_large_vacancy_id(): void
    {
        $orderId = CheckoutService::buildOrderId(999999, 90);
        [$vacancyId, $days] = CheckoutService::parseOrderId($orderId);

        $this->assertSame(999999, $vacancyId);
        $this->assertSame(90, $days);
    }
}
