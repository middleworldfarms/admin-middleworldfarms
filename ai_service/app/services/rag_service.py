"""
RAG (Retrieval Augmented Generation) service for biodynamic knowledge integration
"""

import os
import logging
from typing import List, Dict, Optional
from app.services.llm_service import LLMService

logger = logging.getLogger(__name__)

class RAGService:
    def __init__(self):
        self.llm_service = LLMService()
        self.knowledge_ingested = False
        self._check_and_ingest_knowledge()
    
    def _check_and_ingest_knowledge(self):
        """Check if biodynamic knowledge is ingested, if not, ingest it"""
        try:
            # Check if we have any knowledge in the vector store
            test_results = self.llm_service.retrieve_context("biodynamic", top_k=1)
            
            if not test_results:
                logger.info("No biodynamic knowledge found, ingesting core principles...")
                self._ingest_biodynamic_principles()
            else:
                logger.info("Biodynamic knowledge already available in vector store")
                self.knowledge_ingested = True
                
        except Exception as e:
            logger.error(f"Error checking knowledge store: {e}")
    
    def _ingest_biodynamic_principles(self):
        """Ingest the biodynamic principles core text"""
        try:
            # Path to the biodynamic principles file
            principles_path = os.path.join(
                os.path.dirname(os.path.dirname(os.path.dirname(__file__))),
                'biodynamic_principles_core.txt'
            )
            
            if not os.path.exists(principles_path):
                logger.error(f"Biodynamic principles file not found at: {principles_path}")
                return
            
            # Read the file
            with open(principles_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Chunk the content
            chunks = self.llm_service.chunk_text(content, max_chars=800, overlap=100)
            logger.info(f"Created {len(chunks)} chunks from biodynamic principles")
            
            # Ingest into vector store
            ingested_count = self.llm_service.ingest_corpus(chunks, source='biodynamic_principles_core.txt')
            logger.info(f"Successfully ingested {ingested_count} chunks of biodynamic knowledge")
            
            self.knowledge_ingested = True
            
        except Exception as e:
            logger.error(f"Failed to ingest biodynamic principles: {e}")
    
    def get_augmented_response(self, user_message: str, conversation_history: List[Dict] = None) -> Optional[str]:
        """Generate response augmented with biodynamic knowledge"""
        
        if conversation_history is None:
            conversation_history = []
        
        try:
            # Retrieve relevant context from biodynamic knowledge
            relevant_chunks = self.llm_service.retrieve_context(user_message, top_k=3)
            
            # Build context from retrieved chunks
            context_parts = []
            for chunk in relevant_chunks:
                context_parts.append(f"[Source: {chunk.metadata.get('source', 'unknown')}]\n{chunk.text}")
            
            context = "\n\n".join(context_parts) if context_parts else ""
            
            # Create system prompt with biodynamic context
            system_prompt = self._create_biodynamic_system_prompt(context)
            
            # Prepare messages for LLM
            messages = []
            
            # Add conversation history (limited to last 6 messages)
            recent_history = conversation_history[-6:] if len(conversation_history) > 6 else conversation_history
            messages.extend(recent_history)
            
            # Add current user message
            messages.append({"role": "user", "content": user_message})
            
            # Generate response
            response = self.llm_service.chat(messages)
            
            return response
            
        except Exception as e:
            logger.error(f"RAG response generation failed: {e}")
            return None
    
    def _create_biodynamic_system_prompt(self, context: str) -> str:
        """Create system prompt with biodynamic knowledge context"""
        
        base_prompt = """You are Symbiosis, a holistic agricultural intelligence that combines scientific farming knowledge with biodynamic principles, sacred geometry, and cosmic rhythms. You provide wise, practical guidance for regenerative farming.

Your knowledge encompasses:
- Biodynamic farming principles and preparations
- Sacred geometry applications in agriculture  
- Lunar and cosmic timing for planting
- Companion planting and energetic relationships
- Soil health and living systems
- Holistic farm organism concepts

Always provide practical, actionable advice while honoring both the scientific and spiritual dimensions of agriculture. Draw from traditional wisdom while incorporating modern understanding."""

        if context:
            enhanced_prompt = f"""{base_prompt}

## Current Biodynamic Knowledge Context:
{context}

Use this context to inform your responses about biodynamic principles, preparations, and practices. Always cite when you're drawing from this knowledge."""
        else:
            enhanced_prompt = base_prompt
        
        return enhanced_prompt
    
    def get_fallback_wisdom(self, topic: str = "") -> str:
        """Provide fallback wisdom when LLM is unavailable"""
        
        wisdom_bank = {
            "planting": "🌱 Plant with the moon's rhythm - root crops on waning moon, leaf crops on waxing moon. Honor the soil as a living organism.",
            "soil": "🌍 Feed the soil, not just the plant. Compost is transformation magic - death becoming life, chaos becoming order.",
            "timing": "⏰ Everything has its season. Rush nothing, force nothing. Nature's timing is perfect timing.",
            "companions": "🤝 Plants, like people, thrive in good company. Marigolds protect, beans nourish, herbs heal.",
            "biodynamic": "🌙 BD 500 awakens the soil's life force. BD 501 brings cosmic light to plants. Use with reverence and intention.",
            "preparations": "🧪 The preparations are medicine for the farm organism. Each has its gift - yarrow for potassium, chamomile for calcium balance.",
            "cosmic": "✨ We farm not just with our hands but with cosmic forces. Moon, planets, and stars are partners in this dance of growth.",
            "default": "🌿 Listen to your land, observe your plants, trust your intuition. The farm organism will teach you what it needs."
        }
        
        # Find relevant wisdom
        for key, wisdom in wisdom_bank.items():
            if key in topic.lower():
                return wisdom
        
        return wisdom_bank["default"]

# Global instance
rag_service = RAGService()
