<?php

namespace App\Support\Billing;

use App\Enums\JobStatus;
use App\Enums\SubscriptionPlan;
use App\Models\Company;
use App\Models\Subscription;

class PlanGate
{
    public function planFor(Company $company): SubscriptionPlan
    {
        $subscription = Subscription::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        return $subscription?->plan ?? SubscriptionPlan::Free;
    }

    public function activeJobCount(Company $company): int
    {
        return $company->jobs()
            ->where('status', JobStatus::Published)
            ->count();
    }

    public function canPublishJob(Company $company): bool
    {
        $limit = $this->planFor($company)->activeJobLimit();

        if ($limit === null) {
            return true;
        }

        return $this->activeJobCount($company) < $limit;
    }
}
