<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthentication
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if admin is authenticated
        if (!Session::get('admin_authenticated', false)) {
            // Store the intended URL for redirect after login
            Session::put('url.intended', $request->url());
            
            // Log unauthorized access attempt
            \Log::warning('Unauthorized admin access attempt', [
                'ip' => $request->ip(),
                'url' => $request->url(),
                'user_agent' => $request->userAgent()
            ]);

            // Redirect to login with message
            return redirect('/admin/login')->with('error', 'Please log in to access the admin panel.');
        }

        // Simple session check without timeout for now
        // Update last activity timestamp
        Session::put('admin_last_activity', now());

        return $next($request);
    }
}