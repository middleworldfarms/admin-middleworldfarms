# Multi-Model AI Setup Instructions

## ✅ COMPLETED:
1. ✅ Installed 4 AI models: Mistral, Phi3, Gemma2, TinyLLaMA
2. ✅ Created AI models config file: config/ai_models.php
3. ✅ Updated SymbiosisAIService.php to support multiple models
4. ✅ Created JavaScript model selector: ai-model-test.js

## 🔧 TO ENABLE MODEL SWITCHING:

### 1. Update SuccessionPlanningController.php chat method:

Find this line in the validation array:
```php
'message' => 'required|string|max:1000',
```

Add this line after it:
```php
'model' => 'nullable|string|in:mistral,phi3:mini,gemma2:2b,tinyllama',
```

### 2. In the same chat method, find this line:
```php
->post('http://localhost:8005/ask', [
```

Change it to:
```php
->post('http://localhost:8005/ask', [
    'model' => $validated['model'] ?? 'mistral',  // Add this line
```

### 3. Test the model selector:

1. Go to: https://admin.middleworldfarms.org:8444/admin/farmos/succession-planning
2. Open browser console (F12)
3. Copy/paste the content of ai-model-test.js
4. Select different models and test!

## 📊 MODEL COMPARISON:
- **Mistral (4.4GB)**: Best answers, 40-60 seconds
- **Phi3 (2.2GB)**: Good balance, 15-30 seconds  
- **Gemma2 (1.6GB)**: Fast responses, 10-20 seconds
- **TinyLLaMA (637MB)**: Lightning fast, 5-10 seconds

## 🚀 NEXT STEPS:
1. Fix controller permissions and make the above changes
2. Add permanent model selector to the UI
3. Save user's preferred model choice
4. Add model performance metrics
