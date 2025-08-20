// Quick AI Model Selector for Testing - Copy/paste into browser console

function createModelSelector() {
    const models = {
        'mistral': 'Mistral 7B (Best Quality, 40-60s)',
        'phi3:mini': 'Phi3 Mini (Good Balance, 15-30s)', 
        'gemma2:2b': 'Gemma2 2B (Fast, 10-20s)',
        'tinyllama': 'TinyLLaMA (Fastest, 5-10s)'
    };

    const selectorHTML = \`
        <div id="ai-model-selector" style="background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 8px; border: 1px solid #dee2e6;">
            <label style="font-weight: bold; margin-right: 10px;">🤖 AI Model:</label>
            <select id="selected-ai-model" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                \${Object.entries(models).map(([key, name]) => 
                    \`<option value="\${key}" \${key === 'mistral' ? 'selected' : ''}>\${name}</option>\`
                ).join('')}
            </select>
            <button onclick="testSelectedModel()" style="margin-left: 10px; padding: 5px 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Test Model
            </button>
        </div>
    \`;

    const aiChatSection = document.querySelector('.ai-chat-section');
    if (aiChatSection) {
        aiChatSection.insertAdjacentHTML('beforebegin', selectorHTML);
        console.log('✅ Model selector added!');
    }
}

function testSelectedModel() {
    const model = document.getElementById('selected-ai-model').value;
    console.log(\`🧪 Testing model: \${model}\`);
    
    // Modify the AI chat to use selected model
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        if (url.includes('succession-planning/chat')) {
            const body = JSON.parse(options.body);
            body.model = model;  // Add model selection
            options.body = JSON.stringify(body);
            console.log(\`🚀 Using model: \${model}\`);
        }
        return originalFetch.apply(this, arguments);
    };
    
    const chatInput = document.getElementById('aiChatInput');
    if (chatInput) {
        chatInput.value = \`Test \${model}: What is optimal lettuce spacing?\`;
        if (window.askHolisticAI) {
            askHolisticAI();
        }
    }
}

// Run it
createModelSelector();
console.log('🤖 AI Model Selector ready! Test different models now.');
