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
            $baseUrl = config('services.wp_api.base_url');
            $apiKey = config('services.wp_api.key');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 25);
            $search = $request->input('q', '');
            
            if (!$baseUrl || !$apiKey) {
                $debug['error'] = 'Missing MWF API configuration';
                return view('admin.customers.index', [
                    'recentCustomers' => $recentCustomers,
                    'debug' => $debug
                ]);
            }
            
            $endpoint = $baseUrl . '/users/list';
            $debug['endpoint'] = $endpoint;
            
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])->get($endpoint, [
                'q' => $search,
                'role' => 'customer',
                'page' => $page,
                'per_page' => $perPage,
            ]);

            $data = $response->json();
            $debug['endpoint'] = $endpoint;
            $debug['raw_response'] = $response->body();
            if ($data) {
                if (isset($data['success']) && $data['success'] && isset($data['users']) && is_array($data['users'])) {
                    foreach ($data['users'] as $user) {
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
                        if (!$isSpam && ($orderCount > 0 || $subscribed)) {
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
                } else {
                    $debug['error'] = 'Invalid response from MWF endpoint.';
                }
            } else {
                $debug['error'] = 'No response from MWF endpoint.';
            }
        } catch (\Exception $e) {
            $debug['exception'] = $e->getMessage();
        }
        return view('admin.customers.index', [
            'recentCustomers' => $recentCustomers,
            'debug' => $debug
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
        // TODO: Implement user switching logic
        return response()->json([
            'success' => true,
            'switch_url' => url('/my-account/')
        ]);
    }
}
