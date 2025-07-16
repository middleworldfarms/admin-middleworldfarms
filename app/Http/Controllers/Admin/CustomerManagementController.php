<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CustomerManagementController extends Controller
{
    public function index(Request $request)
    {
        // Fetch users from new MWF endpoint
        $recentCustomers = [];
        $debug = [];
        try {
            $baseUrl = config('services.wordpress.api_base');
            $apiKey = config('services.wordpress.api_key');
            $page = max(1, intval($request->input('page', 1)));
            $perPage = max(10, min(100, intval($request->input('per_page', 25)))); // Between 10-100
            $search = trim($request->input('q', ''));
            
            // Filter parameters
            $filter = $request->input('filter', 'all'); // Quick filter tabs
            $orderFilter = $request->input('order_filter', 'any'); // Order count filter
            $dateFilter = $request->input('date_filter', 'any'); // Registration date filter
            
            if (!$baseUrl || !$apiKey) {
                $debug['error'] = 'Missing MWF API configuration';
                return view('admin.customers.index', [
                    'recentCustomers' => $recentCustomers,
                    'debug' => $debug
                ]);
            }
            
            $endpoint = $baseUrl . '/users/list';
            $debug['endpoint'] = $endpoint;
            $debug['code_version'] = 'get_all_customers_v1'; // Marker to confirm new code is running
            
            // Get customers with proper pagination
            // If filtering for specific types, get more data to find matches
            $apiPerPage = $perPage;
            if ($filter === 'subscribers' || $filter === 'has_orders') {
                $apiPerPage = min(100, $perPage * 4); // Get 4x more data to find matches
            }
            
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])->get($endpoint, [
                'q' => $search,
                'page' => $page,
                'per_page' => $apiPerPage,
                'order' => 'DESC',
                'orderby' => 'registered',
            ]);
            
            // If we're looking for subscribers/orders and get no results, try older customers
            $fallbackAttempted = false;
            if (($filter === 'subscribers' || $filter === 'has_orders') && $page === 1) {
                // We'll check after processing if we need a fallback
            }
            
            $debug['query_params'] = [
                'q' => $search,
                'page' => $page,
                'per_page' => $perPage,
                'order' => 'DESC',
                'orderby' => 'registered',
                'filter' => $filter,
                'order_filter' => $orderFilter,
                'date_filter' => $dateFilter,
            ];
            
            $data = $response->json();
            $debug['api_response'] = [
                'status' => $response->status(),
                'success' => $data['success'] ?? false,
                'total_found' => $data['total_found'] ?? 0,
                'total_users' => $data['total_users'] ?? 0,
                'users_returned' => count($data['users'] ?? [])
            ];
            
            if ($data && isset($data['success']) && $data['success']) {
                $allCustomers = $data['users'] ?? [];
                $debug['total_available'] = $data['total_users'] ?? 0;
            } else {
                $debug['error'] = 'API call failed: ' . json_encode($data);
                $allCustomers = [];
            }
            
            // Now process the combined results
            $filteredOut = [];
            $includedCount = 0;
            if (!empty($allCustomers)) {
                foreach ($allCustomers as $user) {
                    // Filter out spam/test accounts
                    $email = $user['email'] ?? '';
                    $name = $user['display_name'] ?? '';
                    $isSpam = false;
                    if (
                        empty($email) ||
                        preg_match('/@(qq|emaily\.pro|vtext\.com|txt\.att\.net|vzwpix\.com|att\.net|hotmail\.com)$/i', $email) ||
                        preg_match('/^\d+@/', $email) ||
                        preg_match('/^\./', $email) ||
                        stripos($email, 'test') !== false ||
                        stripos($name, 'test') !== false
                    ) {
                        $isSpam = true;
                    }
                    $orderCount = $user['wc_data']['order_count'] ?? 0;
                    $subscribed = false;
                    if (isset($user['subscriptions']) && is_array($user['subscriptions'])) {
                        foreach ($user['subscriptions'] as $sub) {
                            if (isset($sub['status']) && $sub['status'] === 'active') {
                                $subscribed = true;
                                break;
                            }
                        }
                    }
                    // Relax filtering - show customers with orders, subscriptions, OR billing info
                    // This helps capture established customers even if they haven't ordered recently
                    if (!$isSpam && (
                        $orderCount > 0 || 
                        $subscribed || 
                        !empty($user['wc_data']['billing_first_name']) ||
                        !empty($user['wc_data']['billing_last_name']) ||
                        !empty($user['wc_data']['billing_phone']) ||
                        isset($user['roles']) && in_array('customer', $user['roles'])
                    )) {
                        // Apply quick filters
                        $includeUser = true;
                        switch ($filter) {
                            case 'subscribers':
                                $includeUser = $subscribed;
                                break;
                            case 'has_orders':
                                $includeUser = $orderCount > 0;
                                break;
                            case 'recent':
                                $joinedDate = $user['registration_date'] ?? '';
                                $includeUser = $joinedDate && strtotime($joinedDate) > strtotime('-30 days');
                                break;
                            case 'all':
                            default:
                                $includeUser = true;
                                break;
                        }
                        
                        // Apply advanced filters
                        if ($includeUser && $orderFilter !== 'any') {
                            switch ($orderFilter) {
                                case 'none':
                                    $includeUser = $orderCount == 0;
                                    break;
                                case 'some':
                                    $includeUser = $orderCount > 0 && $orderCount < 5;
                                    break;
                                case 'many':
                                    $includeUser = $orderCount >= 5;
                                    break;
                            }
                        }
                        
                        if ($includeUser && $dateFilter !== 'any') {
                            $joinedDate = $user['registration_date'] ?? '';
                            if ($joinedDate) {
                                $joinedTime = strtotime($joinedDate);
                                switch ($dateFilter) {
                                    case 'today':
                                        $includeUser = $joinedTime > strtotime('today');
                                        break;
                                    case 'week':
                                        $includeUser = $joinedTime > strtotime('-7 days');
                                        break;
                                    case 'month':
                                        $includeUser = $joinedTime > strtotime('-30 days');
                                        break;
                                    case 'older':
                                        $includeUser = $joinedTime < strtotime('-30 days');
                                        break;
                                }
                            } else {
                                $includeUser = false;
                            }
                        }
                        
                        if ($includeUser) {
                            $recentCustomers[] = [
                                'id' => $user['id'],
                                'name' => $name,
                                'email' => $email,
                                'subscribed' => $subscribed,
                                'joined' => $user['registration_date'] ?? '',
                                'orders_count' => $orderCount,
                                'last_order' => $user['recent_orders'][0]['date'] ?? '',
                            ];
                            $includedCount++;
                            
                            // Stop when we have enough for the current page
                            if ($includedCount >= $perPage) {
                                break;
                            }
                        }
                    } else {
                        // Debug: track why users are filtered out
                        $filteredOut[] = [
                            'id' => $user['id'],
                            'name' => $name,
                            'email' => $email,
                            'is_spam' => $isSpam,
                            'order_count' => $orderCount,
                            'has_billing' => !empty($user['wc_data']['billing_first_name']),
                            'roles' => $user['roles'] ?? []
                        ];
                    }
                }
                $debug['processing'] = [
                    'total_from_api' => count($allCustomers),
                    'included' => $includedCount,
                    'filtered_out' => count($filteredOut),
                    'sample_filtered_out' => array_slice($filteredOut, 0, 3)
                ];
            } else {
                $debug['error'] = 'No users found from API call.';
            }
            
            // If we're looking for subscribers/orders and found none, try getting older customers
            if (($filter === 'subscribers' || $filter === 'has_orders') && count($recentCustomers) === 0 && $page === 1 && !$search) {
                $debug['fallback_attempted'] = true;
                
                // Try getting older customers by searching multiple pages
                for ($fallbackPage = 5; $fallbackPage <= 10 && count($recentCustomers) < $perPage; $fallbackPage++) {
                    $fallbackResponse = Http::withHeaders([
                        'X-API-Key' => $apiKey,
                    ])->get($endpoint, [
                        'page' => $fallbackPage,
                        'per_page' => 50,
                        'order' => 'DESC',
                        'orderby' => 'registered',
                    ]);
                    
                    $fallbackData = $fallbackResponse->json();
                    if ($fallbackData && isset($fallbackData['success']) && $fallbackData['success']) {
                        $fallbackCustomers = $fallbackData['users'] ?? [];
                        
                        foreach ($fallbackCustomers as $user) {
                            if (count($recentCustomers) >= $perPage) break;
                            
                            // Apply same filtering logic
                            $email = $user['email'] ?? '';
                            $name = $user['display_name'] ?? '';
                            $isSpam = false;
                            if (
                                empty($email) ||
                                preg_match('/@(qq|emaily\.pro|vtext\.com|txt\.att\.net|vzwpix\.com|att\.net|hotmail\.com)$/i', $email) ||
                                preg_match('/^\d+@/', $email) ||
                                preg_match('/^\./', $email) ||
                                stripos($email, 'test') !== false ||
                                stripos($name, 'test') !== false
                            ) {
                                $isSpam = true;
                            }
                            
                            if ($isSpam) continue;
                            
                            $orderCount = $user['wc_data']['order_count'] ?? 0;
                            $subscribed = false;
                            if (isset($user['subscriptions']) && is_array($user['subscriptions'])) {
                                foreach ($user['subscriptions'] as $sub) {
                                    if (isset($sub['status']) && $sub['status'] === 'active') {
                                        $subscribed = true;
                                        break;
                                    }
                                }
                            }
                            
                            // Check if this user matches our filter
                            $matchesFilter = false;
                            if ($filter === 'subscribers' && $subscribed) {
                                $matchesFilter = true;
                            } elseif ($filter === 'has_orders' && $orderCount > 0) {
                                $matchesFilter = true;
                            }
                            
                            if ($matchesFilter) {
                                $recentCustomers[] = [
                                    'id' => $user['id'],
                                    'name' => $name,
                                    'email' => $email,
                                    'subscribed' => $subscribed,
                                    'joined' => $user['registration_date'] ?? '',
                                    'orders_count' => $orderCount,
                                    'last_order' => $user['recent_orders'][0]['date'] ?? '',
                                ];
                            }
                        }
                    }
                }
                
                $debug['fallback_results'] = count($recentCustomers);
            }
            
            // Calculate pagination info
            $totalUsers = $data['total_users'] ?? 0;
            $totalPages = ceil($totalUsers / $perPage);
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_users' => $totalUsers,
                'total_pages' => $totalPages,
                'showing_from' => (($page - 1) * $perPage) + 1,
                'showing_to' => min($page * $perPage, $totalUsers),
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages,
                'prev_page' => max(1, $page - 1),
                'next_page' => min($totalPages, $page + 1),
            ];
        } catch (\Exception $e) {
            $debug['exception'] = $e->getMessage();
        }
        return view('admin.customers.index', [
            'recentCustomers' => $recentCustomers,
            'debug' => $debug,
            'pagination' => $pagination ?? null,
            'search' => $search,
            'perPage' => $perPage,
            'filter' => $filter,
            'orderFilter' => $orderFilter,
            'dateFilter' => $dateFilter,
        ]);
    }

    public function details($userId)
    {
        // TODO: Fetch user details
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $userId,
                // Add more user details here
            ]
        ]);
    }

    public function switchToUser(Request $request, $userId)
    {
        try {
            $baseUrl = config('services.wordpress.api_base');
            $apiKey = config('services.wordpress.api_key');
            
            if (!$baseUrl || !$apiKey) {
                return response()->json([
                    'success' => false,
                    'error' => 'Missing MWF API configuration'
                ]);
            }
            
            // Call the MWF plugin switch endpoint
            $endpoint = $baseUrl . '/users/switch';
            
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])->post($endpoint, [
                'user_id' => $userId,
                'admin_user_id' => auth()->id() ?? 1, // Current admin user
            ]);

            $data = $response->json();
            
            if ($data && isset($data['success']) && $data['success']) {
                return response()->json([
                    'success' => true,
                    'switch_url' => $data['switch_url'] ?? 'https://middleworldfarms.org/my-account/',
                    'message' => 'User switch initiated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $data['message'] ?? 'User switching failed'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ]);
        }
    }
}
