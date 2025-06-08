<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserSwitchingService;
use Illuminate\Http\Request;

class UserSwitchingController extends Controller
{
    protected UserSwitchingService $userSwitchingService;

    public function __construct(UserSwitchingService $userSwitchingService)
    {
        $this->userSwitchingService = $userSwitchingService;
    }

    /**
     * Test the user switching API connection
     */
    public function test()
    {
        $result = $this->userSwitchingService->testConnection();
        
        if ($result['success']) {
            $recentUsers = $this->userSwitchingService->getRecentUsers(5);
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'recent_users_count' => count($recentUsers),
                'sample_users' => $recentUsers
            ]);
        }

        return response()->json($result, 500);
    }

    /**
     * Display the user switching interface
     */
    public function index(Request $request)
    {
        $recentUsers = $this->userSwitchingService->getRecentUsers(20);
        $searchResults = [];
        $searchQuery = '';

        // Handle search
        if ($request->has('search') && !empty($request->search)) {
            $searchQuery = $request->search;
            $searchResults = $this->userSwitchingService->searchUsers($searchQuery, 50);
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

        $users = $this->userSwitchingService->searchUsers($query, $limit);

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
        $user = $this->userSwitchingService->getUserById($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User not found'
            ]);
        }

        // Get user funds balance
        $funds = 0;
        if (!empty($user['email'])) {
            $funds = $this->userSwitchingService->getUserFunds($user['email']);
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
        $redirectTo = $request->get('redirect_to', '/my-account/');
        
        $switchUrl = $this->userSwitchingService->switchToUser(
            $userId, 
            $redirectTo,
            'laravel_admin_panel'
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
            'message' => 'Switch URL generated successfully'
        ]);
    }

    /**
     * Switch to user and redirect (for direct links)
     */
    public function switchAndRedirect($userId, Request $request)
    {
        $redirectTo = $request->get('redirect_to', '/my-account/');
        
        $switchUrl = $this->userSwitchingService->switchToUser(
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
        $users = $this->userSwitchingService->getRecentUsers($limit);

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
            // First, search for the user by email to get their ID
            $users = $this->userSwitchingService->searchUsers($email, 1);
            
            if (empty($users)) {
                return response()->json([
                    'success' => false,
                    'error' => "No user found with email: {$email}"
                ]);
            }

            $user = $users[0];
            $userId = $user['id'] ?? null;

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'error' => "Invalid user data for email: {$email}"
                ]);
            }

            // Generate switch URL using the user ID
            $switchUrl = $this->userSwitchingService->switchToUser(
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
                'message' => "Switch URL generated for {$customerName}",
                'user' => $user
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
        $switchUrl = $this->userSwitchingService->switchToUser(
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
