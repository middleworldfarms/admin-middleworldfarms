<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StripeController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Display Stripe payments dashboard
     */
    public function index()
    {
        $statistics = $this->stripeService->getPaymentStatistics(30);
        $recentPayments = $this->stripeService->getRecentPayments(10);
        $subscriptions = $this->stripeService->getSubscriptions(5);

        return view('admin.stripe.dashboard', compact('statistics', 'recentPayments', 'subscriptions'));
    }

    /**
     * Get recent payments (AJAX)
     */
    public function getPayments(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 25);
        $days = $request->get('days', 30);
        
        $payments = $this->stripeService->getRecentPayments($limit, $days);
        
        return response()->json([
            'success' => true,
            'payments' => $payments,
            'total' => $payments->count()
        ]);
    }

    /**
     * Get payment statistics (AJAX)
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $statistics = $this->stripeService->getPaymentStatistics($days);
        
        return response()->json([
            'success' => true,
            'statistics' => $statistics
        ]);
    }

    /**
     * Search customers
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $query = $request->get('query');
        
        if (!$query) {
            return response()->json(['success' => false, 'message' => 'Query required']);
        }
        
        $customers = $this->stripeService->searchCustomer($query);
        
        return response()->json([
            'success' => true,
            'customers' => $customers
        ]);
    }

    /**
     * Get subscriptions
     */
    public function getSubscriptions(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 25);
        $subscriptions = $this->stripeService->getSubscriptions($limit);
        
        return response()->json([
            'success' => true,
            'subscriptions' => $subscriptions
        ]);
    }
}
