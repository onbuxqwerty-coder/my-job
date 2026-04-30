<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'type'          => 'free',
                'name'          => 'Безкоштовний',
                'price_monthly' => 0,
                'features'      => [
                    'active_jobs'            => 1,
                    'applications_per_month' => 10,
                    'analytics'              => false,
                    'message_templates'      => false,
                    'hot_per_month'          => 0,
                    'top_per_month'          => 0,
                    'api_access'             => false,
                    'team_members'           => 1,
                ],
            ],
            [
                'type'          => 'start',
                'name'          => 'Старт',
                'price_monthly' => 799,
                'features'      => [
                    'active_jobs'            => 3,
                    'applications_per_month' => 50,
                    'analytics'              => false,
                    'message_templates'      => false,
                    'hot_per_month'          => 0,
                    'top_per_month'          => 0,
                    'api_access'             => false,
                    'team_members'           => 1,
                ],
            ],
            [
                'type'          => 'business',
                'name'          => 'Бізнес',
                'price_monthly' => 1990,
                'features'      => [
                    'active_jobs'            => 10,
                    'applications_per_month' => 0,
                    'analytics'              => true,
                    'message_templates'      => true,
                    'hot_per_month'          => 1,
                    'top_per_month'          => 0,
                    'api_access'             => false,
                    'team_members'           => 3,
                ],
            ],
            [
                'type'          => 'pro',
                'name'          => 'Про',
                'price_monthly' => 4490,
                'features'      => [
                    'active_jobs'            => 0,
                    'applications_per_month' => 0,
                    'analytics'              => true,
                    'message_templates'      => true,
                    'hot_per_month'          => 3,
                    'top_per_month'          => 1,
                    'api_access'             => true,
                    'team_members'           => 0,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['type' => $plan['type']], $plan);
        }
    }
}
