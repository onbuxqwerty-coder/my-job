<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->index();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['telegram_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_subscriptions');
    }
};
