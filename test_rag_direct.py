#!/usr/bin/env python3
"""
Direct test of RAG system with lightweight model
"""

import os
import sys
sys.path.append('/opt/sites/admin.middleworldfarms.org/ai_service')

from app.services.rag_service import rag_service

def test_rag_system():
    print("🌱 Testing RAG System - Symbiosis AI 🌙")
    print("=" * 50)
    
    # Test questions
    questions = [
        "What is BD 500?",
        "How to make biodynamic compost?", 
        "When to plant with lunar cycles?",
        "What are the main biodynamic preparations?"
    ]
    
    for i, question in enumerate(questions, 1):
        print(f"\n📍 Test {i}: {question}")
        print("-" * 30)
        
        # Get RAG response
        response = rag_service.get_augmented_response(question)
        
        if response:
            print(f"✅ Response: {response[:200]}{'...' if len(response) > 200 else ''}")
        else:
            print("❌ No response received")
            # Try fallback wisdom
            fallback = rag_service.get_fallback_wisdom(question.lower())
            print(f"🔄 Fallback: {fallback}")
    
    print(f"\n🌟 RAG System Test Complete!")
    print(f"Knowledge Ingested: {rag_service.knowledge_ingested}")

if __name__ == "__main__":
    test_rag_system()
