<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ConversationLogger;
use App\Models\Conversation;

echo "=== Conversation Logging System Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Log different types of conversations
echo "1. Testing conversation logging..." . PHP_EOL;
$chat = ConversationLogger::logChat("User: How do I plant carrots?", 1, "farming_conv_001");
$training = ConversationLogger::logTraining("Carrot planting guide: Plant seeds 1/4 inch deep, 2 inches apart.");
$feedback = ConversationLogger::logFeedback("The AI's carrot advice was excellent!", 1, "farming_conv_001");

echo "   - Chat logged (ID: {$chat->id})" . PHP_EOL;
echo "   - Training logged (ID: {$training->id})" . PHP_EOL;
echo "   - Feedback logged (ID: {$feedback->id})" . PHP_EOL . PHP_EOL;

// Test 2: Retrieve conversation thread
echo "2. Testing conversation thread retrieval..." . PHP_EOL;
$thread = ConversationLogger::getConversationThread("farming_conv_001");
echo "   - Found " . $thread->count() . " messages in conversation 'farming_conv_001'" . PHP_EOL . PHP_EOL;

// Test 3: Get training data
echo "3. Testing training data retrieval..." . PHP_EOL;
$trainingData = ConversationLogger::getTrainingData();
echo "   - Found " . $trainingData->count() . " training entries" . PHP_EOL . PHP_EOL;

// Test 4: Export for training
echo "4. Testing export for training..." . PHP_EOL;
$export = ConversationLogger::exportForTraining();
echo "   - Exported " . strlen($export) . " characters of training data" . PHP_EOL . PHP_EOL;

// Test 5: Statistics
echo "5. Database statistics..." . PHP_EOL;
$totalConversations = Conversation::count();
$chatCount = Conversation::where('type', 'chat')->count();
$trainingCount = Conversation::where('type', 'training')->count();
$feedbackCount = Conversation::where('type', 'feedback')->count();

echo "   - Total conversations: {$totalConversations}" . PHP_EOL;
echo "   - Chat messages: {$chatCount}" . PHP_EOL;
echo "   - Training entries: {$trainingCount}" . PHP_EOL;
echo "   - Feedback entries: {$feedbackCount}" . PHP_EOL . PHP_EOL;

echo "=== Test Complete! ===" . PHP_EOL;
echo "The conversation logging system is fully operational and ready to make Symbiosis a 'sponge' for all conversations and training materials!" . PHP_EOL;
