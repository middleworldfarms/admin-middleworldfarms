<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\Invoice;
use Stripe\Subscription;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Get recent payments from Stripe
     */
    public function getRecentPayments($limit = 25, $days = 30)
    {
        try {
            $charges = Charge::all([
                'limit' => $limit,
                'created' => [
                    'gte' => Carbon::now()->subDays($days)->timestamp,
                ],
            ]);

            return collect($charges->data)->map(function ($charge) {
                return [
                    'id' => $charge->id,
                    'amount' => $charge->amount / 100, // Convert from cents
                    'currency' => strtoupper($charge->currency),
                    'status' => $charge->status,
                    'customer_email' => $charge->billing_details->email ?? $charge->receipt_email,
                    'customer_name' => $charge->billing_details->name ?? 'Unknown',
                    'description' => $charge->description,
                    'created' => Carbon::createFromTimestamp($charge->created),
                    'payment_method' => $this->getPaymentMethodInfo($charge),
                    'refunded' => $charge->refunded,
                    'amount_refunded' => $charge->amount_refunded / 100,
                    'receipt_url' => $charge->receipt_url,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get payment statistics for dashboard
     */
    public function getPaymentStatistics($days = 30)
    {
        try {
            $charges = Charge::all([
                'limit' => 100,
                'created' => [
                    'gte' => Carbon::now()->subDays($days)->timestamp,
                ],
            ]);

            $payments = collect($charges->data);

            return [
                'total_revenue' => $payments->where('status', 'succeeded')->sum(function ($charge) {
                    return $charge->amount / 100;
                }),
                'total_transactions' => $payments->where('status', 'succeeded')->count(),
                'failed_transactions' => $payments->where('status', 'failed')->count(),
                'refunded_amount' => $payments->sum(function ($charge) {
                    return $charge->amount_refunded / 100;
                }),
                'average_transaction' => $payments->where('status', 'succeeded')->avg(function ($charge) {
                    return $charge->amount / 100;
                }),
                'top_customers' => $this->getTopCustomers($payments),
                'daily_revenue' => $this->getDailyRevenue($payments, $days),
            ];
        } catch (\Exception $e) {
            Log::error('Stripe Statistics Error: ' . $e->getMessage());
            return [
                'total_revenue' => 0,
                'total_transactions' => 0,
                'failed_transactions' => 0,
                'refunded_amount' => 0,
                'average_transaction' => 0,
                'top_customers' => [],
                'daily_revenue' => [],
            ];
        }
    }

    /**
     * Get subscription information
     */
    public function getSubscriptions($limit = 25)
    {
        try {
            $subscriptions = Subscription::all(['limit' => $limit]);

            return collect($subscriptions->data)->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'customer_id' => $subscription->customer,
                    'status' => $subscription->status,
                    'current_period_start' => Carbon::createFromTimestamp($subscription->current_period_start),
                    'current_period_end' => Carbon::createFromTimestamp($subscription->current_period_end),
                    'amount' => $subscription->items->data[0]->price->unit_amount / 100 ?? 0,
                    'currency' => strtoupper($subscription->items->data[0]->price->currency ?? 'USD'),
                    'interval' => $subscription->items->data[0]->price->recurring->interval ?? 'month',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Stripe Subscriptions Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Search for customer by email or name
     */
    public function searchCustomer($query)
    {
        try {
            $customers = Customer::all([
                'email' => $query,
                'limit' => 10
            ]);

            return collect($customers->data)->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'email' => $customer->email,
                    'name' => $customer->name ?? $customer->description,
                    'created' => Carbon::createFromTimestamp($customer->created),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Stripe Customer Search Error: ' . $e->getMessage());
            return collect([]);
        }
    }

    private function getPaymentMethodInfo($charge)
    {
        if ($charge->payment_method_details) {
            $type = $charge->payment_method_details->type;
            switch ($type) {
                case 'card':
                    return [
                        'type' => 'Card',
                        'brand' => ucfirst($charge->payment_method_details->card->brand),
                        'last4' => $charge->payment_method_details->card->last4,
                    ];
                case 'bank_transfer':
                    return ['type' => 'Bank Transfer'];
                default:
                    return ['type' => ucfirst($type)];
            }
        }
        return ['type' => 'Unknown'];
    }

    private function getTopCustomers($payments)
    {
        return $payments->where('status', 'succeeded')
            ->groupBy('customer')
            ->map(function ($customerPayments) {
                return [
                    'total' => $customerPayments->sum(function ($charge) {
                        return $charge->amount / 100;
                    }),
                    'count' => $customerPayments->count(),
                    'email' => $customerPayments->first()->receipt_email ?? 'Unknown'
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values();
    }

    private function getDailyRevenue($payments, $days)
    {
        $dailyRevenue = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayStart = Carbon::now()->subDays($i)->startOfDay()->timestamp;
            $dayEnd = Carbon::now()->subDays($i)->endOfDay()->timestamp;
            
            $dayRevenue = $payments->where('status', 'succeeded')
                ->filter(function ($charge) use ($dayStart, $dayEnd) {
                    return $charge->created >= $dayStart && $charge->created <= $dayEnd;
                })
                ->sum(function ($charge) {
                    return $charge->amount / 100;
                });
            
            $dailyRevenue[] = [
                'date' => $date,
                'revenue' => $dayRevenue
            ];
        }
        
        return $dailyRevenue;
    }
}
