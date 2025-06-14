<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DirectDatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    protected $directDbService;

    public function __construct(DirectDatabaseService $directDbService)
    {
        $this->directDbService = $directDbService;
    }

    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
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

                // Log the login
                \Log::info('Admin login successful', [
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                $welcomeMessage = $user['role'] === 'super_admin' ? 
                    'Welcome back, ' . $user['name'] . '! (Super Admin)' : 
                    'Welcome to MWF Admin Dashboard';

                return redirect()->intended('/admin')->with('success', $welcomeMessage);
            }
        }

        // Check against WordPress admin users as fallback
        try {
            $wpUser = $this->directDbService->authenticateWPUser($request->email, $request->password);
            if ($wpUser && $this->isWPUserAdmin($wpUser)) {
                Session::put('admin_authenticated', true);
                Session::put('admin_user', [
                    'name' => $wpUser['display_name'],
                    'email' => $wpUser['user_email'],
                    'role' => 'wp_admin',
                    'wp_user_id' => $wpUser['ID'],
                    'login_time' => now(),
                    'ip_address' => $request->ip()
                ]);

                \Log::info('WordPress admin login successful', [
                    'email' => $wpUser['user_email'],
                    'wp_id' => $wpUser['ID']
                ]);

                return redirect()->intended('/admin')->with('success', 'Welcome ' . $wpUser['display_name']);
            }
        } catch (\Exception $e) {
            \Log::warning('WordPress authentication failed', ['error' => $e->getMessage()]);
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
        
        \Log::info('Admin logout', [
            'email' => $adminUser['email'] ?? 'unknown',
            'session_duration' => $adminUser['login_time'] ? 
                now()->diffInMinutes($adminUser['login_time']) . ' minutes' : 'unknown'
        ]);

        Session::forget('admin_authenticated');
        Session::forget('admin_user');
        Session::invalidate();
        Session::regenerateToken();

        return redirect('/admin/login')->with('message', 'You have been logged out successfully.');
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
}