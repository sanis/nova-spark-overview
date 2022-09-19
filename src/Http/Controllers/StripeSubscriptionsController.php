<?php

namespace RhysLees\NovaSparkOverview\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;
use ReflectionClass;
use Spark\Spark;
use Stripe\Plan;
use Stripe\Subscription as StripeSubscription;

class StripeSubscriptionsController extends Controller
{
    /**
     * @param $billableId
     * @return array
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function show($billableId)
    {
        // /** @var \Laravel\Cashier\Subscription $subscription */
        // $subscription = Subscription::find($subscriptionId);

        // if (! $subscription) {
        //     return [
        //         'subscription' => null,
        //     ];
        // }

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

        $plan = $billable->sparkPlan()->name;

        $plans = Spark::plans(strtolower(class_basename($billable)));

        $stripePlans = collect(Plan::all([
            'limit' => 100,
            'active' => true,
        ])->data);

        $plans = $plans->each(function ($plan) use ($stripePlans) {
            $stripePlan = $stripePlans->firstWhere('id', $plan->id);

            $plan->price = $stripePlan->amount;
            $plan->currency = $stripePlan->currency;
            $plan->interval = $stripePlan->interval;
            $plan->interval_count = $stripePlan->interval_count;
        });

        return [
            'subscription' => $this->formatSubscription($subscription, $plan),
            'plans' => $this->formatPlans($plans),
            'invoices' => $this->formatInvoices($subscription->owner->invoicesIncludingPending()),
        ];
    }

    /**
     * @param $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Laravel\Cashier\Exceptions\SubscriptionUpdateFailure
     */
    public function update($subscriptionId)
    {
        /** @var \Laravel\Cashier\Subscription $subscription */
        $subscription = Subscription::findOrFail($subscriptionId);

        $subscription->swap($this->request->input('plan'));

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @param $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel($subscriptionId)
    {
        /** @var \Laravel\Cashier\Subscription $subscription */
        $subscription = Subscription::findOrFail($subscriptionId);

        if ($this->request->input('now')) {
            $subscription->cancelNow();
        } else {
            $subscription->cancel();
        }

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @param $subscriptionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function resume($subscriptionId)
    {
        /** @var \Laravel\Cashier\Subscription $subscription */
        $subscription = Subscription::findOrFail($subscriptionId);

        $subscription->resume();

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  \Laravel\Cashier\Subscription  $subscription
     * @return array
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    protected function formatSubscription(Subscription $subscription, $plan)
    {
        $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_id);

        return array_merge($subscription->toArray(), [
            'plan_amount' => $stripeSubscription->plan->amount,
            'plan_amount_formatted' => Cashier::formatAmount($stripeSubscription->plan->amount, $stripeSubscription->plan->currency),
            'plan_interval' => $stripeSubscription->plan->interval,
            'plan_currency' => $stripeSubscription->plan->currency,
            'plan' => $subscription->stripe_plan,
            'stripe_plan' => $plan,
            'ended' => $subscription->ended(),
            'canceled' => $subscription->canceled(),
            'active' => $subscription->active(),
            'on_trial' => $subscription->onTrial(),
            'on_grace_period' => $subscription->onGracePeriod(),
            'charges_automatically' => $stripeSubscription->billing == 'charge_automatically',
            'created_at' => $this->formatDate($stripeSubscription->billing_cycle_anchor),
            'ended_at' => $this->formatDate($stripeSubscription->ended_at),
            'current_period_start' => $this->formatDate($stripeSubscription->current_period_start),
            'current_period_end' => $this->formatDate($stripeSubscription->current_period_end),
            'days_until_due' => $stripeSubscription->days_until_due,
            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
            'canceled_at' => $stripeSubscription->canceled_at,
        ]);
    }

    /**
     * Format the plans collection.
     *
     * @return array
     */
    protected function formatPlans($plans)
    {
        return $plans->map(function ($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
                'price_formatted' => Cashier::formatAmount($plan->price, $plan->currency),
                'interval' => $plan->interval,
                'currency' => $plan->currency,
                'interval_count' => $plan->interval_count,
            ];
        })->toArray();
    }

    /**
     * @param $invoices
     * @return array
     */
    protected function formatInvoices($invoices)
    {
        return collect($invoices)->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'total' => $invoice->total,
                'total_formatted' => Cashier::formatAmount($invoice->total, $invoice->currency),
                'attempted' => $invoice->attempted,
                'charge_id' => $invoice->charge,
                'currency' => $invoice->currency,
                'period_start' => $this->formatDate($invoice->period_start),
                'period_end' => $this->formatDate($invoice->period_end),
                'link' => $invoice->hosted_invoice_url,
                'subscription' => $invoice->subscription,
            ];
        })->toArray();
    }

    /**
     * @param  mixed  $value
     * @return string|null
     */
    protected function formatDate($value)
    {
        return $value ? Carbon::createFromTimestamp($value)->toDateTimeString() : null;
    }
}
