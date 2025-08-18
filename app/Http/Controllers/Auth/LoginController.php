<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Exception;

class LoginController extends Controller
{
    protected WpApiService $wpApiService;

    public function __construct(WpApiService $wpApiService)
    {
        $this->wpApiService = $wpApiService;
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Get WordPress email for admin authentication
     * Maps Laravel admin emails to their corresponding WordPress emails
     */
    private function getWordPressEmailForAdmin($adminEmail)
    {
        // Email mapping for admin users
        $emailMapping = [
            'martin@middleworldfarms.org' => 'middleworldfarms@gmail.com',
            // Add more mappings here if needed in the future
            // 'other-admin@middleworldfarms.org' => 'their-wp-email@domain.com',
        ];
        
        return $emailMapping[$adminEmail] ?? $adminEmail;
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Get admin users from config
        $adminUsers = config('admin_users.users', []);

        // Check against configured admin users
        foreach ($adminUsers as $user) {
            if (!$user['active']) {
                continue;
            }

            if ($request->email === $user['email'] && $request->password === $user['password']) {
                // Store admin session
                Session::put('admin_authenticated', true);
                Session::put('admin_user', [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'login_time' => now(),
                    'ip_address' => $request->ip()
                ]);

                // Authenticate admin with WordPress automatically using email mapping
                $wordpressEmail = $this->getWordPressEmailForAdmin($user['email']);
                $wpAuthResult = $this->wpApiService->authenticateAdminWithWordPress($wordpressEmail, $user['name']);
                
                if ($wpAuthResult['success']) {
                    // Store WordPress session info
                    Session::put('wp_authenticated', true);
                    Session::put('wp_integration_status', 'authenticated');
                    Session::put('wp_admin_url', $wpAuthResult['wp_admin_url'] ?? 'https://middleworldfarms.org/wp-admin/');
                    Session::put('wp_user', $wpAuthResult['wp_user'] ?? null);
                    Session::put('wp_auth_cookie', $wpAuthResult['wp_auth_cookie'] ?? null);
                    
                    // Log successful WordPress authentication
                    Log::info('Admin login with WordPress authentication successful', [
                        'admin_email' => $user['email'],
                        'wordpress_email' => $wordpressEmail,
                        'role' => $user['role'],
                        'wp_authentication' => 'success',
                        'wp_user_id' => $wpAuthResult['wp_user']['id'] ?? null,
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent()
                    ]);
                } else {
                    // Log WordPress authentication failure but continue with admin login
                    Log::warning('WordPress authentication failed during admin login', [
                        'admin_email' => $user['email'],
                        'wordpress_email' => $wordpressEmail,
                        'error' => $wpAuthResult['error'] ?? 'Authentication failed'
                    ]);
                    
                    // Store partial session info for manual login
                    Session::put('wp_authenticated', false);
                    Session::put('wp_integration_status', 'failed');
                    Session::put('wp_admin_url', 'https://middleworldfarms.org/wp-admin/');
                }

                // Log the admin login (always successful)
                Log::info('Admin login successful', [
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'wp_integrated' => $wpAuthResult['success'] ?? false,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                $welcomeMessage = $user['role'] === 'super_admin' ? 
                    'Welcome back, ' . $user['name'] . '! (Super Admin)' : 
                    'Welcome to MWF Admin Dashboard';
                
                // Add WordPress integration status to welcome message
                if ($wpAuthResult['success']) {
                    $welcomeMessage .= ' - WordPress authentication successful!';
                } else {
                    $welcomeMessage .= ' - WordPress authentication failed (manual login required)';
                }

                return redirect()->intended(route('admin.dashboard'))->with('success', $welcomeMessage);
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        $adminUser = Session::get('admin_user');
        $wpAuthenticated = Session::get('wp_authenticated', false);
        
        // Log logout with WordPress integration status
        Log::info('Admin logout', [
            'email' => $adminUser['email'] ?? 'unknown',
            'wp_integrated' => $wpAuthenticated,
            'session_duration' => $adminUser['login_time'] ? 
                now()->diffInMinutes($adminUser['login_time']) . ' minutes' : 'unknown'
        ]);

        // Clear both admin and WordPress sessions
        Session::forget('admin_authenticated');
        Session::forget('admin_user');
        Session::forget('wp_authenticated');
        Session::forget('wp_user');
        Session::forget('wp_admin_url');
        Session::invalidate();
        Session::regenerateToken();

        return redirect(config('app.url') . '/admin/login')->with('message', 'You have been logged out successfully.');
    }

    /**
     * Check if WordPress user has admin privileges
     */
    private function isWPUserAdmin($user)
    {
        // Check if user has administrator role
        $capabilities = $user['capabilities'] ?? '';
        return str_contains($capabilities, 'administrator') || 
               str_contains($capabilities, 'manage_options');
    }

    /**
     * Get current admin user
     */
    public static function getAdminUser()
    {
        return Session::get('admin_user');
    }

    /**
     * Check if current user is authenticated admin
     */
    public static function isAdminAuthenticated()
    {
        return Session::get('admin_authenticated', false);
    }

    /**
     * Retry WordPress authentication for current admin session
     */
    public function retryWordPressAuth(Request $request)
    {
        if (!Session::get('admin_authenticated', false)) {
            return response()->json(['success' => false, 'error' => 'Not authenticated as admin']);
        }
        
        $adminUser = Session::get('admin_user');
        if (!$adminUser) {
            return response()->json(['success' => false, 'error' => 'Admin user data not found']);
        }
        
        try {
            $wpAuthResult = $this->wpApiService->authenticateAdminWithWordPress($adminUser['email'], $adminUser['name']);
            
            if ($wpAuthResult['success']) {
                // Update session with WordPress data
                Session::put('wp_authenticated', true);
                Session::put('wp_user', $wpAuthResult['wp_user'] ?? null);
                Session::put('wp_admin_url', $wpAuthResult['wp_admin_url'] ?? null);
                
                Log::info('WordPress authentication retry successful', [
                    'email' => $adminUser['email'],
                    'wp_user_id' => $wpAuthResult['wp_user']['id'] ?? null
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'WordPress authentication successful',
                    'wp_admin_url' => $wpAuthResult['wp_admin_url'] ?? null
                ]);
            } else {
                Log::warning('WordPress authentication retry failed', [
                    'email' => $adminUser['email'],
                    'error' => $wpAuthResult['error'] ?? 'Unknown error'
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => $wpAuthResult['error'] ?? 'WordPress authentication failed'
                ]);
            }
        } catch (Exception $e) {
            Log::error('WordPress authentication retry exception', [
                'email' => $adminUser['email'],
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Authentication error: ' . $e->getMessage()
            ]);
        }
    }

}