#!/bin/bash

# Fixed Biodynamic AI Testing with proper JSON parsing

test_bd() {
    local model="$1"
    local question="$2"
    
    if [ -z "$model" ] || [ -z "$question" ]; then
        echo "Usage: test_bd <model> <question>"
        echo "Models: mistral, phi3:mini, gemma2:2b, tinyllama"
        return 1
    fi
    
    echo "🧠 Testing $model"
    echo "❓ $question"
    echo "⏱️  Processing..."
    echo ""
    
    # Enhanced biodynamic prompt
    prompt="You are Symbiosis, a holistic agricultural AI with deep expertise in biodynamic farming principles developed by Rudolf Steiner. 

Core biodynamic knowledge:
- BD 500: Cow manure preparation buried in cow horn, used to stimulate soil life and root growth
- BD 501: Silica preparation (ground quartz in cow horn) for plant development and disease resistance  
- BD 502-508: Herbal preparations (yarrow, chamomile, nettle, oak bark, dandelion, valerian) for compost
- Farm organism: The farm as a self-sustaining living system
- Cosmic rhythms: Planting by moon phases and planetary influences
- Anthroposophy: Spiritual science approach to agriculture

Question: $question

Provide accurate, practical biodynamic advice:"

    # Make API call and parse response
    response=$(curl -s -X POST http://localhost:11434/api/generate \
        -H "Content-Type: application/json" \
        -d "{\"model\": \"$model\", \"prompt\": $(echo "$prompt" | python3 -c "import json, sys; print(json.dumps(sys.stdin.read()))"), \"stream\": false}")
    
    # Extract and display response
    echo "$response" | python3 -c "
import json, sys
try:
    data = json.load(sys.stdin)
    response = data.get('response', 'No response')
    print('🌱 Biodynamic AI Response:')
    print(response)
except Exception as e:
    print(f'❌ Error parsing response: {e}')
"
    echo ""
    echo "✅ Complete"
}

# Model-specific shortcuts
bd_mistral() { test_bd mistral "$1"; }
bd_phi() { test_bd phi3:mini "$1"; }
bd_gemma() { test_bd gemma2:2b "$1"; }
bd_tiny() { test_bd tinyllama "$1"; }

# Test all models with same question
test_all_models() {
    local question="$1"
    if [ -z "$question" ]; then
        echo "Usage: test_all_models 'question'"
        return 1
    fi
    
    echo "🧪 Testing all models with: $question"
    echo "================================="
    
    echo "1️⃣ TinyLLaMA (fastest):"
    bd_tiny "$question"
    echo ""
    
    echo "2️⃣ Gemma2 (fast):"
    bd_gemma "$question"
    echo ""
    
    echo "3️⃣ Phi3 (balanced):"
    bd_phi "$question"
    echo ""
    
    echo "4️⃣ Mistral (best quality):"
    bd_mistral "$question"
}

# Info function
bd_help() {
    echo "🌱 Biodynamic AI Testing Commands:"
    echo ""
    echo "Single Model Tests:"
    echo "  bd_tiny 'question'     - TinyLLaMA (5-10s)"
    echo "  bd_gemma 'question'    - Gemma2 (10-20s)"
    echo "  bd_phi 'question'      - Phi3 (15-30s)"
    echo "  bd_mistral 'question'  - Mistral (40-60s)"
    echo ""
    echo "Multi-Model Test:"
    echo "  test_all_models 'question'  - Test all 4 models"
    echo ""
    echo "Example Questions:"
    echo "  'What is BD 500?'"
    echo "  'How do I make biodynamic compost?'"
    echo "  'When should I plant by moon phases?'"
    echo "  'What is the farm organism concept?'"
    echo "  'How do biodynamic preparations work?'"
}

echo "🌱 Fixed Biodynamic AI Testing loaded!"
echo "Run 'bd_help' for commands"
