<?php

namespace RhysLees\NovaSparkOverview\Http\Controllers;

use Carbon\Carbon;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;
use Spark\Plan;
use Spark\Spark;

class DatabaseSubscriptionsController extends Controller
{
    /**
     * @param $billableId
     * @return array
     */
    public function show($billableId)
    {
        $customerModel = Cashier::$customerModel;

        /** @var \Illuminate\Database\Eloquent\Model $billableModel */
        $billableModel = (new $customerModel());

        /** @var \Laravel\Cashier\Billable|\Illuminate\Database\Eloquent\Model $billable */
        $billable = $billableModel->find($billableId);

        /** @var \Laravel\Cashier\Subscription $subscription */
        $subscription = $billable->subscription(
            $this->request->query('subscription', 'default')
        );

        if (! $subscription) {
            return [
                'subscription' => null,
            ];
        }

        $plan = $billable->sparkPlan();

        return [
            'subscription' => $this->formatSubscription($subscription, $plan),
        ];
    }

    protected function formatSubscription(Subscription $subscription, Plan $plan)
    {
        $planName = $subscription->name;

        if ($plan){
            $planName = $plan->name . ' - ' . $plan->interval;
        }

        return array_merge($subscription->toArray(), [
            'plan' => $planName,
            'ended' => $subscription->ended(),
            'canceled' => $subscription->canceled(),
            'active' => $subscription->active(),
            'on_trial' => $subscription->onTrial(),
            'on_grace_period' => $subscription->onGracePeriod(),
            'created_at' => $this->formatDate($subscription->created_at),
        ]);
    }

    /**
     * @param  mixed  $value
     * @return string|null
     */
    protected function formatDate($value)
    {
        return $value ? Carbon::parse($value)->toDateTimeString() : null;
    }
}
