<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanFeature: string
{
    case ActiveJobs            = 'active_jobs';             // int: 0 = необмежено
    case ApplicationsPerMonth  = 'applications_per_month';  // int: 0 = необмежено
    case Analytics             = 'analytics';               // bool
    case MessageTemplates      = 'message_templates';       // bool
    case HotPerMonth           = 'hot_per_month';           // int
    case TopPerMonth           = 'top_per_month';           // int
    case ApiAccess             = 'api_access';              // bool
    case TeamMembers           = 'team_members';            // int: 0 = необмежено
}
