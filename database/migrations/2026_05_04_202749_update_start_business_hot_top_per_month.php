<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE subscription_plans SET features = JSON_SET(features, '$.hot_per_month', 1, '$.top_per_month', 1) WHERE type='start'");
        DB::statement("UPDATE subscription_plans SET features = JSON_SET(features, '$.top_per_month', 1) WHERE type='business'");
    }

    public function down(): void
    {
        DB::statement("UPDATE subscription_plans SET features = JSON_SET(features, '$.hot_per_month', 0, '$.top_per_month', 0) WHERE type='start'");
        DB::statement("UPDATE subscription_plans SET features = JSON_SET(features, '$.top_per_month', 0) WHERE type='business'");
    }
};
