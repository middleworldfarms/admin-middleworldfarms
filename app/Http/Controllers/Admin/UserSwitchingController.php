<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserSwitchingController extends Controller
{
    protected WpApiService $wpApi;

    public function __construct(WpApiService $wpApi)
    {
        $this->wpApi = $wpApi;
    }

    /**
     * Display the user switching interface
     */
    public function index(Request $request)
    {
        $recentUsers = $this->wpApi->getRecentUsers(20);
        $searchResults = [];
        $searchQuery = '';

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $searchQuery = $request->search;
            $searchResults = $this->wpApi->searchUsers($searchQuery, 50);
        }

        return view('admin.user-switching.index', compact(
            'recentUsers',
            'searchResults', 
            'searchQuery'
        ));
    }

    /**
     * Search users via AJAX
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 20);

        if (empty($query)) {
            return response()->json([
                'success' => false,
                'error' => 'Search query is required'
            ]);
        }

        $users = $this->wpApi->searchUsers($query, $limit);

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
    }

    /**
     * Get user details
     */
    public function getUserDetails($userId)
    {
        $user = $this->wpApi->getUserById($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found'
            ]);
        }

        // Get user funds balance
        $funds = 0;
        if (!empty($user['email'])) {
            $funds = $this->wpApi->getUserFunds($user['email']);
        }

        $user['account_funds'] = $funds;

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Switch to a user
     */
    public function switchToUser(Request $request, $userId)
    {
        try {
            // Validate user ID
            if (!$userId || !is_numeric($userId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid user ID provided'
                ], 400);
            }

            $redirectTo = $request->get('redirect_to', '/my-account/');
            
            $switchUrl = $this->wpApi->generateUserSwitchUrl(
                $userId, 
                $redirectTo,
                'laravel_admin_panel'
            );

            if (!$switchUrl) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate switch URL - user may not exist or API connection failed'
                ], 400);
            }

            \Log::info("User switch successful", [
                'user_id' => $userId,
                'redirect_to' => $redirectTo,
                'switch_url' => substr($switchUrl, 0, 50) . '...'
            ]);

            return response()->json([
                'success' => true,
                'switch_url' => $switchUrl,
                'message' => 'Switch URL generated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error("User switch failed", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Switch to user and redirect (for direct links)
     */
    public function switchAndRedirect($userId, Request $request)
    {
        $redirectTo = $request->get('redirect_to', '/my-account/');
        
        $switchUrl = $this->wpApi->generateUserSwitchUrl(
            $userId,
            $redirectTo,
            'laravel_admin_direct'
        );

        if (!$switchUrl) {
            return redirect()->back()->with('error', 'Failed to switch to user');
        }

        // Redirect to the switch URL
        return redirect($switchUrl);
    }

    /**
     * Get recent users for dashboard widget
     */
    public function getRecentUsers(Request $request)
    {
        $limit = $request->get('limit', 10);
        $users = $this->wpApi->getRecentUsers($limit);

        return response()->json([
            'success' => true,
            'users' => $users,
            'count' => count($users)
        ]);
    }

    /**
     * Switch to user by email (for delivery schedule integration)
     */
    public function switchByEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'customer_name' => 'sometimes|string'
        ]);

        $email = $request->input('email');
        $customerName = $request->input('customer_name', 'Customer');
        $redirectTo = $request->input('redirect_to', '/my-account/');

        try {
            // First, search for the user by exact email
            Log::info("UserSwitching: Starting search", [
                'email' => $email,
                'customer_name' => $customerName
            ]);
            
            $users = $this->wpApi->searchUsers($email, 1);
            
            Log::info("UserSwitching: Search results", [
                'email' => $email,
                'users_found' => $users ? $users->count() : 0,
                'users_data' => $users ? $users->toArray() : null
            ]);
            
            if (empty($users) || $users->isEmpty()) {
                // Fallback 1: Try searching by customer name if provided
                if (!empty($customerName) && $customerName !== 'Customer') {
                    Log::info("Email not found, trying name search", ['email' => $email, 'name' => $customerName]);
                    $users = $this->wpApi->searchUsers($customerName, 5);
                    
                    if (!empty($users) && $users->count() > 0) {
                        // Found users by name, use the first one
                        $user = $users->first();
                        $foundBy = "name '{$customerName}'";
                    }
                }
                
                // Fallback 2: Try partial email search (remove domain)
                if ((empty($users) || $users->isEmpty()) && strpos($email, '@') !== false) {
                    $emailParts = explode('@', $email);
                    $username = $emailParts[0];
                    Log::info("Name search failed, trying username search", ['username' => $username]);
                    $users = $this->wpApi->searchUsers($username, 5);
                    
                    if (!empty($users) && $users->count() > 0) {
                        $user = $users->first();
                        $foundBy = "username '{$username}'";
                    }
                }
                
                // If still no users found, return helpful error
                if (empty($users) || $users->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'error' => "No WordPress user found for email: {$email}",
                        'suggestion' => "This customer email may not have a WordPress user account. Try creating a user account for this customer first."
                    ]);
                }
            } else {
                $user = $users->first();
                $foundBy = "email '{$email}'";
            }
            $userId = $user['id'] ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'error' => "Invalid user data for email: {$email}"
                ]);
            }

            // Generate switch URL using the user ID
            $switchUrl = $this->wpApi->generateUserSwitchUrl(
                $userId,
                $redirectTo,
                'delivery_schedule_admin'
            );

            if (!$switchUrl) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate switch URL'
                ]);
            }

            return response()->json([
                'success' => true,
                'switch_url' => $switchUrl,
                'message' => "Switch URL generated for {$customerName} (found by {$foundBy})",
                'user' => $user,
                'found_by' => $foundBy ?? 'email'
            ]);

        } catch (\Exception $e) {
            Log::error('Error switching user by email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Server error while switching user'
            ]);
        }
    }

    /**
     * Redirect to user switching
     */
    public function redirect(int $userId)
    {
        $switchUrl = $this->wpApi->switchToUser(
            $userId,
            '/my-account/',
            'admin_panel'
        );

        if (!$switchUrl) {
            return redirect()->back()->with('error', 'Failed to switch to user');
        }

        return redirect($switchUrl);
    }
}
