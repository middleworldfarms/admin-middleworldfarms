#!/bin/bash

# Simple RAG testing without vector database dependency

test_ai_knowledge() {
    local model="$1"
    local question="$2"
    
    if [ -z "$model" ] || [ -z "$question" ]; then
        echo "Usage: test_ai_knowledge <model> <question>"
        echo "Models: mistral, phi3:mini, gemma2:2b, tinyllama"
        return 1
    fi
    
    echo "🧠 Testing $model with biodynamic context"
    echo "❓ $question"
    echo "⏱️  Processing..."
    echo ""
    
    # Enhanced prompt with biodynamic context
    enhanced_prompt="You are Symbiosis, a holistic agricultural AI with deep knowledge of biodynamic farming principles developed by Rudolf Steiner. 

Key biodynamic concepts you know:
- BD 500 (cow manure preparation) for soil vitality
- BD 501 (silica preparation) for plant growth and light
- The farm as a living organism
- Cosmic rhythms and moon phases in farming
- Nine biodynamic preparations (500-508)
- Anthroposophic principles
- Working with life forces and energies

Question: $question

Provide practical, wise advice that honors both scientific understanding and spiritual dimensions of agriculture."

    curl -s -X POST http://localhost:11434/api/generate \
        -H "Content-Type: application/json" \
        -d "{\"model\": \"$model\", \"prompt\": \"$enhanced_prompt\", \"stream\": false}" \
        | jq -r '.response' 2>/dev/null | head -c 1000

    echo -e "\n\n✅ Response complete"
}

# Quick test functions
test_mistral_bd() { test_ai_knowledge mistral "$1"; }
test_phi_bd() { test_ai_knowledge phi3:mini "$1"; }  
test_gemma_bd() { test_ai_knowledge gemma2:2b "$1"; }
test_tiny_bd() { test_ai_knowledge tinyllama "$1"; }

# Knowledge test
bd_info() {
    echo "🌱 Biodynamic AI Knowledge Testing"
    echo ""
    echo "Commands:"
    echo "  test_mistral_bd 'question'  - Best quality (40-60s)"
    echo "  test_phi_bd 'question'      - Good balance (15-30s)"  
    echo "  test_gemma_bd 'question'    - Fast responses (10-20s)"
    echo "  test_tiny_bd 'question'     - Fastest (5-10s)"
    echo ""
    echo "Sample Questions:"
    echo "  'What is BD 500 and how do I use it?'"
    echo "  'When should I apply biodynamic preparations?'"
    echo "  'How do moon phases affect plant growth?'"
    echo "  'What is the farm organism concept?'"
    echo "  'How to make compost biodynamically?'"
}

echo "🌱 Simple Biodynamic AI Testing loaded!"
echo "Run 'bd_info' for commands"
