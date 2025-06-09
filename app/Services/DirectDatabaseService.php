<?php

namespace App\Services;

use App\Models\WordPressUser;
use App\Models\WooCommerceOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class DirectDatabaseService
{
    protected $wpConnection;
    protected $prefix;

    public function __construct()
    {
        $this->wpConnection = 'wordpress';
        // Use env() directly as a fallback if config() isn't available
        $this->prefix = config('database.connections.wordpress.prefix', env('WP_DB_PREFIX', 'wp_'));
    }

    /**
     * Test database connection
     */
    public function testConnection()
    {
        try {
            $result = DB::connection($this->wpConnection)->select('SELECT COUNT(*) as count FROM ' . $this->prefix . 'users');
            return [
                'success' => true,
                'message' => 'WordPress database connected successfully',
                'user_count' => $result[0]->count ?? 0
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to WordPress database: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Search users - much faster than API
     */
    public function searchUsers($query, $limit = 20)
    {
        try {
            $users = WordPressUser::search($query, $limit);
            return $users->map(function ($user) {
                return $user->getFormattedData();
            });
        } catch (Exception $e) {
            Log::error('User search failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get user by ID - direct database access
     */
    public function getUserById($userId)
    {
        try {
            $user = WordPressUser::find($userId);
            return $user ? $user->getFormattedData() : null;
        } catch (Exception $e) {
            Log::error('Get user failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent users - much faster than API
     */
    public function getRecentUsers($limit = 10, $role = null)
    {
        try {
            // If no role specified, get recent users from all roles
            // Common roles in your system: 'delicious_recipes_subscriber', 'administrator'
            $users = WordPressUser::getRecent($limit, $role);
            return $users->map(function ($user) {
                return $user->getFormattedData();
            });
        } catch (Exception $e) {
            Log::error('Get recent users failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get delivery schedule data - direct from WooCommerce tables
     */
    public function getDeliveryScheduleData($limit = 50)
    {
        try {
            // Get recent one-time orders (deliveries) - these are actual orders that need delivery
            $orders = WooCommerceOrder::orders()
                ->active()
                ->with(['meta'])
                ->whereIn('post_status', ['wc-processing']) // Only processing orders for delivery
                ->orderBy('post_date', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($order) {
                    $data = $order->getFormattedData();
                    // Add delivery-specific data
                    $data['delivery_date'] = $order->getMeta('_delivery_date');
                    $data['delivery_slot'] = $order->getMeta('_delivery_slot');
                    $data['delivery_notes'] = $order->getMeta('_delivery_notes');
                    $data['special_instructions'] = $order->getMeta('_customer_notes') ?: $order->post_excerpt;
                    $data['type'] = 'order'; // Mark as one-time order
                    return $data;
                });

            // Get active subscriptions (collections) - these are recurring weekly collections
            $subscriptions = WooCommerceOrder::subscriptions()
                ->active()
                ->with(['meta'])
                ->whereIn('post_status', ['wc-active', 'wc-on-hold']) // Active subscriptions for collection
                ->orderBy('post_date', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($subscription) {
                    $data = $subscription->getFormattedData();
                    // Add subscription-specific data
                    $data['next_payment'] = $subscription->getMeta('_schedule_next_payment');
                    $data['billing_period'] = $subscription->getMeta('_billing_period');
                    $data['billing_interval'] = $subscription->getMeta('_billing_interval');
                    $data['subscription_status'] = str_replace('wc-', '', $subscription->post_status);
                    $data['type'] = 'subscription'; // Mark as recurring subscription
                    return $data;
                });

            Log::info('Delivery schedule data retrieved', [
                'orders_count' => $orders->count(),
                'subscriptions_count' => $subscriptions->count()
            ]);

            return [
                'deliveries' => $orders,
                'collections' => $subscriptions,
                'total_deliveries' => $orders->count(),
                'total_collections' => $subscriptions->count(),
                'data_source' => 'direct_database'
            ];
        } catch (Exception $e) {
            Log::error('Get delivery schedule failed: ' . $e->getMessage());
            return [
                'deliveries' => collect(),
                'collections' => collect(),
                'total_deliveries' => 0,
                'total_collections' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate user switching URL - using WordPress User Switching plugin
     */
    public function generateUserSwitchUrl($userId, $redirectTo = '/my-account/')
    {
        try {
            $user = WordPressUser::find($userId);
            if (!$user) {
                throw new Exception('User not found');
            }

            // Generate switch URL using WordPress User Switching plugin format
            $homeUrl = rtrim(config('services.wordpress.url', 'https://middleworldfarms.org'), '/');
            
            // Create the switch URL that WordPress will handle
            $switchUrl = $homeUrl . '/wp-admin/admin.php?action=switch_to_user&user_id=' . $userId;
            $switchUrl .= '&redirect_to=' . urlencode($homeUrl . $redirectTo);
            $switchUrl .= '&_wpnonce=' . $this->generateSwitchNonce($userId);

            return [
                'success' => true,
                'switch_url' => $switchUrl,
                'user' => $user->getFormattedData(),
                'method' => 'direct_wordpress_switching'
            ];
        } catch (Exception $e) {
            Log::error('Generate switch URL failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate a nonce for user switching (simplified version)
     * In production, you'd want to properly implement WordPress nonce generation
     */
    protected function generateSwitchNonce($userId)
    {
        // This is a simplified nonce - in production you'd want to use WordPress's wp_create_nonce
        // For now, we'll create a basic hash
        $action = 'switch_to_user_' . $userId;
        $salt = 'your_wordpress_salt_here'; // This should come from WordPress config
        return substr(md5($action . $salt . time()), 0, 10);
    }

    /**
     * Get WordPress options (settings)
     */
    public function getOption($optionName, $default = null)
    {
        try {
            $result = DB::connection($this->wpConnection)
                ->table($this->prefix . 'options')
                ->where('option_name', $optionName)
                ->first();
            
            return $result ? $result->option_value : $default;
        } catch (Exception $e) {
            Log::error('Get option failed: ' . $e->getMessage());
            return $default;
        }
    }

    /**
     * Get WooCommerce settings
     */
    public function getWooCommerceSettings()
    {
        try {
            return [
                'currency' => $this->getOption('woocommerce_currency', 'GBP'),
                'currency_symbol' => $this->getOption('woocommerce_currency_symbol', '£'),
                'store_address' => $this->getOption('woocommerce_store_address'),
                'store_city' => $this->getOption('woocommerce_store_city'),
                'store_postcode' => $this->getOption('woocommerce_store_postcode'),
            ];
        } catch (Exception $e) {
            Log::error('Get WooCommerce settings failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user orders - direct database query
     */
    public function getUserOrders($userId, $limit = 10)
    {
        try {
            $orders = WooCommerceOrder::orders()
                ->whereHas('meta', function ($query) use ($userId) {
                    $query->where('meta_key', '_customer_user')
                          ->where('meta_value', $userId);
                })
                ->orderBy('post_date', 'desc')
                ->limit($limit)
                ->get();

            return $orders->map(function ($order) {
                return $order->getFormattedData();
            });
        } catch (Exception $e) {
            Log::error('Get user orders failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get user subscriptions - direct database query
     */
    public function getUserSubscriptions($userId, $limit = 10)
    {
        try {
            $subscriptions = WooCommerceOrder::subscriptions()
                ->whereHas('meta', function ($query) use ($userId) {
                    $query->where('meta_key', '_customer_user')
                          ->where('meta_value', $userId);
                })
                ->orderBy('post_date', 'desc')
                ->limit($limit)
                ->get();

            return $subscriptions->map(function ($subscription) {
                return $subscription->getFormattedData();
            });
        } catch (Exception $e) {
            Log::error('Get user subscriptions failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get user funds/balance - simplified implementation
     * In a real system, this would connect to your payment/wallet system
     */
    public function getUserFunds($userEmail)
    {
        try {
            // For now, return 0 as we don't have funds system in direct database
            // This would need to be connected to your actual funds/wallet system
            return 0.00;
        } catch (Exception $e) {
            Log::error('Get user funds failed: ' . $e->getMessage());
            return 0.00;
        }
    }

    /**
     * Generate user switching URL using the MWF Integration plugin
     */
    public function switchToUser($userId, $redirectTo = '/my-account/', $context = 'admin_panel')
    {
        try {
            $user = WordPressUser::find($userId);
            if (!$user) {
                throw new Exception('User not found');
            }

            // Use the MWF Integration API for switching since it's already set up
            $mwfApiKey = config('services.wordpress.api_key');
            $mwfApiUrl = config('services.wordpress.api_url', 'https://middleworldfarms.org/wp-json/mwf/v1');

            $response = Http::withHeaders([
                'X-API-Key' => $mwfApiKey,
                'Content-Type' => 'application/json'
            ])->post($mwfApiUrl . '/users/switch', [
                'user_id' => $userId,
                'redirect_to' => $redirectTo,
                'admin_context' => $context
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['preview_url'] ?? null;
            }

            Log::error('MWF API switch failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (Exception $e) {
            Log::error('Switch to user failed: ' . $e->getMessage());
            return null;
        }
    }
}
