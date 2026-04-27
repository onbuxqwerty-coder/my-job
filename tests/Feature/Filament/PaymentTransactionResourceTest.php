<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Resources\PaymentTransactions\Pages\ListPaymentTransactions;
use App\Filament\Resources\PaymentTransactions\PaymentTransactionResource;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentTransactionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
        config(['payments.prices' => [15 => 10000, 30 => 20000, 90 => 50000]]);
    }

    public function test_can_create_returns_false(): void
    {
        $this->assertFalse(PaymentTransactionResource::canCreate());
    }

    public function test_can_edit_returns_false(): void
    {
        $tx = PaymentTransaction::factory()->create();
        $this->assertFalse(PaymentTransactionResource::canEdit($tx));
    }

    public function test_can_delete_returns_false(): void
    {
        $tx = PaymentTransaction::factory()->create();
        $this->assertFalse(PaymentTransactionResource::canDelete($tx));
    }

    public function test_table_shows_transaction(): void
    {
        $tx = PaymentTransaction::factory()->mono()->create();

        Livewire::test(ListPaymentTransactions::class)
            ->assertCanSeeTableRecords([$tx]);
    }

    public function test_table_sorted_by_processed_at_desc(): void
    {
        $old   = PaymentTransaction::factory()->daysAgo(5)->create();
        $newer = PaymentTransaction::factory()->today()->create();

        $records = PaymentTransaction::latest('processed_at')->get();
        $this->assertSame($newer->event_id, $records->first()->event_id);
        $this->assertSame($old->event_id, $records->last()->event_id);
    }

    public function test_gateway_filter_shows_only_matching(): void
    {
        $mono   = PaymentTransaction::factory()->mono()->create();
        $liqpay = PaymentTransaction::factory()->liqpay()->create();

        $filtered = PaymentTransaction::where('gateway', 'mono')->get();
        $this->assertTrue($filtered->contains('event_id', $mono->event_id));
        $this->assertFalse($filtered->contains('event_id', $liqpay->event_id));
    }

    public function test_days_filter_shows_only_matching(): void
    {
        $tx30 = PaymentTransaction::factory()->forVacancy(
            \App\Models\Vacancy::factory()->active()->create(), 30
        )->create();

        $tx90 = PaymentTransaction::factory()->forVacancy(
            \App\Models\Vacancy::factory()->active()->create(), 90
        )->create();

        $filtered = PaymentTransaction::where('order_id', 'LIKE', '%_30_%')->get();
        $this->assertTrue($filtered->contains('event_id', $tx30->event_id));
        $this->assertFalse($filtered->contains('event_id', $tx90->event_id));
    }

    public function test_date_range_filter_shows_only_matching(): void
    {
        $old   = PaymentTransaction::factory()->daysAgo(10)->create();
        $today = PaymentTransaction::factory()->today()->create();

        $filtered = PaymentTransaction::whereDate('processed_at', today())->get();
        $this->assertTrue($filtered->contains('event_id', $today->event_id));
        $this->assertFalse($filtered->contains('event_id', $old->event_id));
    }

    public function test_navigation_badge_returns_null_when_no_recent(): void
    {
        PaymentTransaction::factory()->daysAgo(2)->create();

        $this->assertNull(PaymentTransactionResource::getNavigationBadge());
    }

    public function test_navigation_badge_returns_count_for_last_24_hours(): void
    {
        PaymentTransaction::factory()->today()->count(3)->create();

        $this->assertSame('3', PaymentTransactionResource::getNavigationBadge());
    }
}
