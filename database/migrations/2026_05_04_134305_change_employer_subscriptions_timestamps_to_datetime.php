<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employer_subscriptions', function (Blueprint $table) {
            $table->dateTime('starts_at')->change();
            $table->dateTime('ends_at')->change();
            $table->dateTime('cancelled_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employer_subscriptions', function (Blueprint $table) {
            $table->timestamp('starts_at')->change();
            $table->timestamp('ends_at')->change();
            $table->timestamp('cancelled_at')->nullable()->change();
        });
    }
};
