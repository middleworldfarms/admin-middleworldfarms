<?php

/**
 * Stripe Connection Test Script
 * Run this after adding your Stripe keys to .env
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Test Stripe connection
$stripeSecret = $_ENV['STRIPE_SECRET'] ?? 'not_set';

if ($stripeSecret === 'not_set' || empty($stripeSecret)) {
    echo "❌ STRIPE_SECRET not found in .env file\n";
    echo "Please add your Stripe secret key to the .env file\n";
    exit(1);
}

echo "Testing Stripe API Connection...\n";
echo "Using secret key: " . substr($stripeSecret, 0, 7) . "...\n\n";

try {
    \Stripe\Stripe::setApiKey($stripeSecret);
    
    // Test API call - get recent charges
    $charges = \Stripe\Charge::all(['limit' => 3]);
    
    echo "✅ Connection successful!\n";
    echo "Found " . count($charges->data) . " recent charges\n";
    
    if (count($charges->data) > 0) {
        echo "\nRecent payments:\n";
        foreach ($charges->data as $charge) {
            $amount = $charge->amount / 100;
            $currency = strtoupper($charge->currency);
            $date = date('M j, Y g:i A', $charge->created);
            $status = $charge->status;
            
            echo "- {$date}: £{$amount} {$currency} ({$status})\n";
        }
    }
    
    echo "\n🎉 Your Stripe integration is ready!\n";
    echo "You can now visit /admin/stripe in your browser.\n";
    
} catch (\Stripe\Exception\InvalidRequestException $e) {
    echo "❌ Invalid API request: " . $e->getMessage() . "\n";
} catch (\Stripe\Exception\AuthenticationException $e) {
    echo "❌ Authentication failed: Check your API key\n";
    echo "Error: " . $e->getMessage() . "\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Next steps:\n";
echo "1. Add your Stripe keys to .env if not done already\n";
echo "2. Run: php test-stripe-connection.php\n";  
echo "3. Visit /admin/stripe in your browser\n";
echo str_repeat("=", 50) . "\n";

?>
