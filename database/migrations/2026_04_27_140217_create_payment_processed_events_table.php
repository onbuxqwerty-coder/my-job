<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_processed_events', function (Blueprint $table) {
            $table->string('event_id', 255);
            $table->string('gateway', 32);
            $table->string('order_id', 255);
            $table->timestamp('processed_at')->useCurrent();

            $table->primary(['event_id', 'gateway']);
            $table->index('processed_at');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_processed_events');
    }
};
