#!/bin/bash

# RAG System Testing Commands

# Function to test RAG with different models
test_rag() {
    local model="$1"
    local question="$2"
    
    if [ -z "$model" ] || [ -z "$question" ]; then
        echo "Usage: test_rag <model> <question>"
        echo "Models: mistral, phi3:mini, gemma2:2b, tinyllama"
        echo "Example: test_rag mistral 'What are biodynamic preparations?'"
        return 1
    fi
    
    echo "🧠 Testing RAG with $model"
    echo "❓ Question: $question"
    echo "⏱️  Please wait..."
    echo ""
    
    cd ai_service
    python3 -c "
import sys
sys.path.append('.')
from app.services.rag_service import rag_service

response = rag_service.get_augmented_response('$question')
if response:
    print('🤖 RAG Response:')
    print(response)
else:
    print('❌ RAG failed, trying fallback...')
    print(rag_service.get_fallback_wisdom('$question'))
"
    cd ..
}

# Test vector database
test_vector_db() {
    echo "🔍 Testing Vector Database..."
    cd ai_service
    python3 -c "
import sys
sys.path.append('.')
from app.services.llm_service import LLMService

llm = LLMService()
if llm.store:
    print('✅ Vector store available')
    # Test retrieval
    results = llm.retrieve_context('biodynamic preparations', top_k=2)
    print(f'📊 Found {len(results)} relevant chunks')
    for i, result in enumerate(results):
        print(f'   {i+1}. {result.text[:100]}...')
else:
    print('❌ Vector store not available')
"
    cd ..
}

# Ingest knowledge base
ingest_knowledge() {
    echo "📚 Ingesting biodynamic knowledge..."
    cd ai_service
    python3 -c "
import sys
sys.path.append('.')
from app.services.llm_service import LLMService

llm = LLMService()
if llm.store:
    try:
        with open('biodynamic_principles_core.txt', 'r') as f:
            content = f.read()
        chunks = llm.chunk_text(content, max_chars=800, overlap=100)
        count = llm.ingest_corpus(chunks, 'biodynamic_principles_core.txt')
        print(f'✅ Ingested {count} chunks of biodynamic knowledge')
    except Exception as e:
        print(f'❌ Ingestion failed: {e}')
else:
    print('❌ Vector store not available')
"
    cd ..
}

# Test embeddings
test_embeddings() {
    echo "🔤 Testing embeddings generation..."
    cd ai_service
    python3 -c "
import sys
sys.path.append('.')
from app.services.llm_service import LLMService

llm = LLMService()
test_texts = ['biodynamic preparations', 'moon phases', 'soil health']
embeddings = llm.embed_texts(test_texts)
print(f'✅ Generated embeddings for {len(test_texts)} texts')
print(f'📊 Embedding dimension: {len(embeddings[0])}')
"
    cd ..
}

# Quick RAG aliases
alias rag_mistral='test_rag mistral'
alias rag_phi='test_rag phi3:mini'  
alias rag_gemma='test_rag gemma2:2b'
alias rag_tiny='test_rag tinyllama'

# Info function
rag_info() {
    echo "🧠 RAG System Test Commands:"
    echo ""
    echo "Basic Tests:"
    echo "  test_vector_db          - Check vector database status"
    echo "  test_embeddings         - Test embedding generation"
    echo "  ingest_knowledge        - Reload biodynamic knowledge"
    echo ""
    echo "RAG Testing:"
    echo "  test_rag <model> '<question>'  - Test RAG with specific model"
    echo ""
    echo "Quick Aliases:"
    echo "  rag_mistral 'What are BD preparations?' - Test with Mistral"
    echo "  rag_phi 'When to use BD 500?'          - Test with Phi3"
    echo "  rag_gemma 'Moon phase farming?'        - Test with Gemma2"
    echo "  rag_tiny 'Soil health tips?'           - Test with TinyLLaMA"
    echo ""
    echo "Example Questions:"
    echo "  - 'What are biodynamic preparations?'"
    echo "  - 'How do moon phases affect planting?'"
    echo "  - 'What is Rudolf Steiner approach?'"
    echo "  - 'How to improve soil vitality?'"
}

echo "✅ RAG Test Commands loaded!"
echo "Run 'rag_info' to see available commands"
