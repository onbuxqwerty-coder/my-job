<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $promoDays = [
        'free'     => ['hot_days' => 1,  'top_days' => 1],
        'start'    => ['hot_days' => 7,  'top_days' => 7],
        'business' => ['hot_days' => 30, 'top_days' => 30],
        'pro'      => ['hot_days' => 0,  'top_days' => 0],
    ];

    public function up(): void
    {
        foreach ($this->promoDays as $type => $days) {
            $plan = DB::table('subscription_plans')->where('type', $type)->first();

            if (! $plan) {
                continue;
            }

            $features = json_decode($plan->features, true);
            $features['hot_days'] = $days['hot_days'];
            $features['top_days'] = $days['top_days'];

            DB::table('subscription_plans')
                ->where('type', $type)
                ->update(['features' => json_encode($features)]);
        }
    }

    public function down(): void
    {
        DB::table('subscription_plans')->each(function ($plan): void {
            $features = json_decode($plan->features, true);
            unset($features['hot_days'], $features['top_days']);
            DB::table('subscription_plans')
                ->where('id', $plan->id)
                ->update(['features' => json_encode($features)]);
        });
    }
};
