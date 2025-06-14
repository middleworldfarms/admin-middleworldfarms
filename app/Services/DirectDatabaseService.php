<?php

namespace App\Services;

use App\Models\WooCommerceOrder;
use App\Models\WordPressUser;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DirectDatabaseService
{
    protected $wpConnection;

    public function __construct()
    {
        $this->wpConnection = 'wordpress';
    }

    /**
     * Test database connection
     */
    public function testConnection()
    {
        try {
            $result = DB::connection($this->wpConnection)->select('SELECT COUNT(*) as count FROM users');
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
    public function getDeliveryScheduleData($limit = 100)
    {
        try {
            // Get only ACTIVE subscriptions - exclude trash, cancelled, on-hold
            $subscriptions = WooCommerceOrder::subscriptions()
                ->whereIn('post_status', ['wc-active', 'wc-pending']) // Only active and pending subscriptions
                ->with(['meta'])
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
                    
                    // Check if this subscription has shipping (delivery) or not (collection)
                    $shippingTotal = (float) $subscription->getMeta('_order_shipping');
                    $shippingMethod = $subscription->getMeta('_shipping_method');
                    $hasShipping = $shippingTotal > 0 || !empty($shippingMethod);
                    
                    // Determine type based on shipping
                    $data['type'] = $hasShipping ? 'delivery' : 'collection';
                    
                    // Get frequency from parent order
                    $parentOrderId = $subscription->post_parent;
                    $frequency = null;
                    $paymentOption = null;
                    if ($parentOrderId) {
                        $parentOrder = \App\Models\WooCommerceOrder::find($parentOrderId);
                        if ($parentOrder) {
                            foreach ($parentOrder->items as $item) {
                                $freqMeta = strtolower(trim($item->getMeta('frequency', '')));
                                $payOptMeta = strtolower(trim($item->getMeta('payment-option', '')));
                                if ($freqMeta) $frequency = $freqMeta;
                                if ($payOptMeta) $paymentOption = $payOptMeta;
                            }
                        }
                    }
                    
                    // Set frequency and week logic
                    $freqValue = $frequency ?: $paymentOption ?: '';
                    $isFortnightly = strpos($freqValue, 'fortnightly') !== false;
                    
                    if ($isFortnightly) {
                        $data['frequency'] = 'Fortnightly';
                        $data['frequency_badge'] = 'warning';
                        
                        // Calculate current week type (A = odd, B = even)
                        $currentWeek = (int) date('W');
                        $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
                        $data['current_week_type'] = $currentWeekType;
                        
                        // Determine customer's week type from subscription meta 
                        $storedWeekType = $subscription->getMeta('customer_week_type');
                        
                        if ($storedWeekType && in_array($storedWeekType, ['A', 'B'])) {
                            // Use stored preference if it exists
                            $customerWeekType = $storedWeekType;
                        } else {
                            // If no preference stored, assign based on parent order date ISO week
                            $parentOrderId = $subscription->post_parent;
                            if ($parentOrderId) {
                                $parentOrder = \App\Models\WooCommerceOrder::find($parentOrderId);
                                if ($parentOrder) {
                                    // Get ISO week number from parent order date
                                    $orderDate = \Carbon\Carbon::parse($parentOrder->post_date);
                                    $orderWeek = (int) $orderDate->format('W');
                                    // Odd weeks = A, Even weeks = B
                                    $customerWeekType = ($orderWeek % 2 === 1) ? 'A' : 'B';
                                    
                                    // Debug logging
                                    \Log::info("Week calculation", [
                                        'subscription_id' => $subscription->ID,
                                        'parent_order_id' => $parentOrderId,
                                        'parent_order_date' => $parentOrder->post_date,
                                        'iso_week' => $orderWeek,
                                        'week_is_odd' => ($orderWeek % 2 === 1),
                                        'assigned_week' => $customerWeekType
                                    ]);
                                } else {
                                    // Fallback to subscription date if parent not found
                                    $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                                    $subscriptionWeek = (int) $subscriptionDate->format('W');
                                    $customerWeekType = ($subscriptionWeek % 2 === 1) ? 'A' : 'B';
                                    
                                    \Log::info("Week calculation fallback (no parent)", [
                                        'subscription_id' => $subscription->ID,
                                        'subscription_date' => $subscription->post_date,
                                        'iso_week' => $subscriptionWeek,
                                        'assigned_week' => $customerWeekType
                                    ]);
                                }
                            } else {
                                // Fallback to subscription date if no parent
                                $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                                $subscriptionWeek = (int) $subscriptionDate->format('W');
                                $customerWeekType = ($subscriptionWeek % 2 === 1) ? 'A' : 'B';
                                
                                \Log::info("Week calculation fallback (no parent ID)", [
                                    'subscription_id' => $subscription->ID,
                                    'subscription_date' => $subscription->post_date,
                                    'iso_week' => $subscriptionWeek,
                                    'assigned_week' => $customerWeekType
                                ]);
                            }
                        }
                        
                        $data['customer_week_type'] = $customerWeekType;
                        
                        // Debug logging for week assignment
                        if ($isFortnightly) {
                            \Log::info("Week assignment debug", [
                                'subscription_id' => $subscription->ID,
                                'customer_email' => $data['customer_email'] ?? 'unknown',
                                'parent_order_id' => $parentOrderId,
                                'assigned_week' => $customerWeekType,
                                'stored_week' => $storedWeekType,
                                'used_stored' => !empty($storedWeekType)
                            ]);
                        }
                        
                        // Determine if delivery should happen this week
                        $data['should_deliver_this_week'] = ($customerWeekType === $currentWeekType);
                        
                        // Set week badge color
                        $data['week_badge'] = ($customerWeekType === 'A') ? 'success' : 'info';
                        
                    } elseif (strpos($freqValue, 'weekly') !== false) {
                        $data['frequency'] = 'Weekly';
                        $data['frequency_badge'] = 'success';
                        $data['customer_week_type'] = 'Weekly';
                        $data['should_deliver_this_week'] = true;
                        $data['week_badge'] = 'primary';
                    } else {
                        // Default to weekly
                        $data['frequency'] = 'Weekly';
                        $data['frequency_badge'] = 'success';
                        $data['customer_week_type'] = 'Weekly';
                        $data['should_deliver_this_week'] = true;
                        $data['week_badge'] = 'primary';
                    }
                    
                    return $data;
                });

            // Separate into deliveries and collections based on shipping
            $deliveries = $subscriptions->where('type', 'delivery');
            $collections = $subscriptions->where('type', 'collection');

            Log::info('Delivery schedule data retrieved', [
                'deliveries_count' => $deliveries->count(),
                'collections_count' => $collections->count(),
                'total_subscriptions' => $subscriptions->count()
            ]);

            return [
                'deliveries' => $deliveries,
                'collections' => $collections,
                'total_deliveries' => $deliveries->count(),
                'total_collections' => $collections->count(),
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
                ->table('options')
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
     * Generate user switching URL using the WordPress User Switching plugin
     */
    public function switchToUser($userId, $redirectTo = '/my-account/', $context = 'admin_panel')
    {
        try {
            // Validate user exists in WordPress database
            $user = DB::connection($this->wpConnection)
                ->table('users')
                ->where('ID', $userId)
                ->first();

            if (!$user) {
                Log::warning('User not found for switching', ['user_id' => $userId]);
                return null;
            }

            // Generate the admin switch key (same logic as WordPress)
            $secret = 'mwf_admin_switch_2025_secret_key';
            $adminKey = hash('sha256', $userId . $redirectTo . $secret);

            // Create the switch URL using our custom WordPress endpoint
            $baseUrl = rtrim(env('WOOCOMMERCE_URL', 'https://middleworldfarms.org'), '/');
            $ajaxUrl = $baseUrl . '/wp-admin/admin-ajax.php?' . http_build_query([
                'action' => 'mwf_generate_plugin_switch_url',
                'user_id' => $userId,
                'redirect_to' => $redirectTo,
                'admin_key' => $adminKey
            ]);

            // Make HTTP request to WordPress to get the actual auto-login URL
            $response = file_get_contents($ajaxUrl);
            
            if ($response === false) {
                Log::error('Failed to fetch switch URL from WordPress', ['ajax_url' => $ajaxUrl]);
                return null;
            }

            // Parse JSON response
            $responseData = json_decode($response, true);
            
            if (!$responseData || !isset($responseData['success']) || !$responseData['success']) {
                Log::error('WordPress returned error response', [
                    'response' => $response,
                    'ajax_url' => $ajaxUrl
                ]);
                return null;
            }

            if (!isset($responseData['data']['switch_url'])) {
                Log::error('No switch_url in WordPress response', ['response' => $responseData]);
                return null;
            }

            $finalSwitchUrl = $responseData['data']['switch_url'];

            Log::info('Generated user switch URL successfully', [
                'user_id' => $userId,
                'user_login' => $user->user_login,
                'redirect_to' => $redirectTo,
                'ajax_url' => $ajaxUrl,
                'final_switch_url' => $finalSwitchUrl
            ]);

            return $finalSwitchUrl;

        } catch (\Exception $e) {
            Log::error('Error generating switch URL', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return null;
        }
    }

    /**
     * Authenticate WordPress user
     */
    public function authenticateWPUser($email, $password)
    {
        try {
            $user = DB::connection($this->wpConnection)
                ->table('users')
                ->where('user_email', $email)
                ->first();

            if (!$user) {
                return null;
            }

            // Check password using WordPress hash
            if (!$this->checkWPPassword($password, $user->user_pass)) {
                return null;
            }

            // Get user capabilities
            $capabilities = DB::connection($this->wpConnection)
                ->table('usermeta')
                ->where('user_id', $user->ID)
                ->where('meta_key', env('WP_DB_PREFIX', 'wp_') . 'capabilities')
                ->value('meta_value');

            return [
                'ID' => $user->ID,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'display_name' => $user->display_name,
                'capabilities' => $capabilities
            ];

        } catch (Exception $e) {
            Log::error('WordPress authentication failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check WordPress password hash
     */
    private function checkWPPassword($password, $hash)
    {
        // WordPress uses MD5 and phpass
        if (strlen($hash) <= 32) {
            return hash_equals($hash, md5($password));
        }

        // For newer WordPress installations with stronger hashing
        // This is a simplified check - in production you might want to use
        // the actual WordPress password checking functions
        return password_verify($password, $hash);
    }

    /**
     * Calculate current fortnightly week (A or B)
     * Odd ISO week numbers = Week A, Even ISO week numbers = Week B
     */
    private function calculateFortnightlyWeek()
    {
        $currentWeek = (int) date('W'); // ISO week number
        return ($currentWeek % 2 === 1) ? 'A' : 'B';
    }

    /**
     * Determine if a fortnightly subscription should have delivery this week
     */
    private function isFortnightlyDeliveryWeek($subscription)
    {
        try {
            // First, check if there's an assigned week in the meta
            $assignedWeek = $subscription->getMeta('_mwf_fortnightly_week');
            
            if ($assignedWeek) {
                // If we have an assigned week (A or B), use that
                $currentWeek = $this->calculateFortnightlyWeek();
                return $assignedWeek === $currentWeek;
            }
            
            // Fallback: Use subscription start date to determine its Week A/B cycle
            $startDate = $subscription->post_date;
            $startWeek = (int) date('W', strtotime($startDate));
            
            // Determine if subscription started on Week A or Week B
            $subscriptionStartWeek = ($startWeek % 2 === 1) ? 'A' : 'B';
            
            // Current week
            $currentWeek = $this->calculateFortnightlyWeek();
            
            // Check if current week matches subscription's cycle
            return $subscriptionStartWeek === $currentWeek;
            
        } catch (Exception $e) {
            Log::warning('Could not determine fortnightly delivery week', [
                'subscription_id' => $subscription->ID ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Default to true if we can't determine
            return true;
        }
    }

    /**
     * Get fortnightly delivery schedule for a specific week
     */
    public function getFortnightlySchedule($weekType = null)
    {
        if (!$weekType) {
            $weekType = $this->calculateFortnightlyWeek();
        }
        
        try {
            $fortnightlySubscriptions = WooCommerceOrder::subscriptions()
                ->active()
                ->with(['meta'])
                ->whereIn('post_status', ['wc-active', 'wc-on-hold'])
                ->get()
                ->filter(function ($subscription) use ($weekType) {
                    // Check MWF fortnightly meta field (correct field that exists)
                    $mwfFortnightly = $subscription->getMeta('_mwf_fortnightly');
                    
                    if ($mwfFortnightly !== 'yes') {
                        return false;
                    }
                    
                    return $this->isFortnightlyDeliveryWeek($subscription);
                })
                ->map(function ($subscription) use ($weekType) {
                    $data = $subscription->getFormattedData();
                    $data['frequency'] = 'Fortnightly';
                    $data['frequency_badge'] = 'warning'; // Orange badge
                    $data['delivery_week'] = $weekType;
                    $data['assigned_week'] = $subscription->getMeta('_mwf_fortnightly_week') ?: 'A';
                    $data['type'] = 'fortnightly_subscription';
                    return $data;
                });

            return [
                'week_type' => $weekType,
                'current_iso_week' => date('W'),
                'subscriptions' => $fortnightlySubscriptions->values(),
                'count' => $fortnightlySubscriptions->count()
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to get fortnightly schedule', [
                'week_type' => $weekType,
                'error' => $e->getMessage()
            ]);
            
            return [
                'week_type' => $weekType,
                'current_iso_week' => date('W'),
                'subscriptions' => collect(),
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get count of weekly subscriptions
     */
    public function getWeeklySubscriptionsCount()
    {
        try {
            $weeklyCount = WooCommerceOrder::subscriptions()
                ->active()
                ->with(['meta'])
                ->whereIn('post_status', ['wc-active', 'wc-on-hold'])
                ->get()
                ->filter(function ($subscription) {
                    // Check MWF fortnightly meta field - if not "yes", it's weekly
                    $mwfFortnightly = $subscription->getMeta('_mwf_fortnightly');
                    return $mwfFortnightly !== 'yes';
                })
                ->count();
            
            return $weeklyCount;
            
        } catch (Exception $e) {
            Log::error('Failed to get weekly subscriptions count', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    /**
     * Get frequency badge color based on frequency type
     */
    private function getFrequencyBadge($frequency)
    {
        switch (strtolower($frequency)) {
            case 'weekly':
                return 'success';
            case 'fortnightly':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    /**
     * Get WordPress option from database
     */
    private function getWordPressOption($optionName)
    {
        try {
            $result = DB::connection($this->wpConnection)->table('options')
                ->where('option_name', $optionName)
                ->value('option_value');
            
            return $result;
        } catch (Exception $e) {
            Log::error('Failed to get WordPress option', [
                'option' => $optionName,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
