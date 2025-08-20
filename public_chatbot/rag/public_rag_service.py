#!/usr/bin/env python3
"""
Public Chatbot RAG Service
Separate from admin AI system - for customer-facing chatbot
"""

import os
import sys
import psycopg2
import logging
from typing import List, Dict, Optional
from sentence_transformers import SentenceTransformer
import numpy as np
from datetime import datetime

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class PublicRAGService:
    """
    RAG service for public chatbot - separate from admin system
    """
    
    def __init__(self):
        self.embedding_model = None
        self.db_connection = None
        self._initialize_db()
        self._load_embedding_model()
        self._ensure_tables()
        
    def _initialize_db(self):
        """Initialize PostgreSQL connection to public chatbot database"""
        try:
            self.db_connection = psycopg2.connect(
                host=os.getenv('CHATBOT_DB_HOST', 'localhost'),
                port=os.getenv('CHATBOT_DB_PORT', '5432'),
                database=os.getenv('CHATBOT_DB_NAME', 'public_chatbot_db'),
                user=os.getenv('CHATBOT_DB_USER', 'chatbot_user'),
                password=os.getenv('CHATBOT_DB_PASSWORD', 'r3gZszE+aztJVz5tKDH0Z3y1ZA03bSdL'),
            )
            logger.info("✅ Public chatbot database connected successfully")
        except Exception as e:
            logger.error(f"❌ Database connection failed: {e}")
            raise
            
    def _load_embedding_model(self):
        """Load sentence transformer model"""
        try:
            model_name = os.getenv('EMBEDDING_MODEL', 'all-MiniLM-L6-v2')
            self.embedding_model = SentenceTransformer(model_name)
            logger.info(f"✅ Embedding model loaded: {model_name}")
        except Exception as e:
            logger.error(f"❌ Failed to load embedding model: {e}")
            raise
            
    def _ensure_tables(self):
        """Create necessary tables for public chatbot"""
        try:
            with self.db_connection.cursor() as cursor:
                # Public knowledge base table
                cursor.execute("""
                    CREATE TABLE IF NOT EXISTS public_knowledge (
                        id SERIAL PRIMARY KEY,
                        content TEXT NOT NULL,
                        category VARCHAR(100),
                        source VARCHAR(255),
                        embedding vector(384),
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                """)
                
                # Conversation history table
                cursor.execute("""
                    CREATE TABLE IF NOT EXISTS conversations (
                        id SERIAL PRIMARY KEY,
                        session_id VARCHAR(255),
                        user_message TEXT,
                        bot_response TEXT,
                        model_used VARCHAR(50),
                        response_time FLOAT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                """)
                
                # Analytics table
                cursor.execute("""
                    CREATE TABLE IF NOT EXISTS chatbot_analytics (
                        id SERIAL PRIMARY KEY,
                        event_type VARCHAR(50),
                        data JSONB,
                        ip_address INET,
                        user_agent TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                """)
                
                self.db_connection.commit()
                logger.info("✅ Public chatbot tables ensured")
                
        except Exception as e:
            logger.error(f"❌ Table creation failed: {e}")
            self.db_connection.rollback()
            raise
            
    def add_knowledge(self, content: str, category: str = None, source: str = None) -> bool:
        """Add knowledge to public chatbot database"""
        try:
            # Generate embedding
            embedding = self.embedding_model.encode(content)
            embedding_list = embedding.tolist()
            
            with self.db_connection.cursor() as cursor:
                cursor.execute("""
                    INSERT INTO public_knowledge (content, category, source, embedding)
                    VALUES (%s, %s, %s, %s)
                """, (content, category, source, embedding_list))
                
                self.db_connection.commit()
                logger.info(f"✅ Added knowledge: {content[:50]}...")
                return True
                
        except Exception as e:
            logger.error(f"❌ Failed to add knowledge: {e}")
            self.db_connection.rollback()
            return False
            
    def search_knowledge(self, query: str, limit: int = 5, category: str = None) -> List[Dict]:
        """Search for relevant knowledge using vector similarity"""
        try:
            # Generate query embedding
            query_embedding = self.embedding_model.encode(query)
            query_embedding_list = query_embedding.tolist()
            
            with self.db_connection.cursor() as cursor:
                # Build SQL with optional category filter
                sql = """
                    SELECT 
                        content, 
                        category, 
                        source,
                        1 - (embedding <=> %s::vector) as similarity
                    FROM public_knowledge
                """
                params = [query_embedding_list]
                
                if category:
                    sql += " WHERE category = %s"
                    params.append(category)
                    
                sql += """
                    ORDER BY embedding <=> %s::vector
                    LIMIT %s
                """
                params.extend([query_embedding_list, limit])
                
                cursor.execute(sql, params)
                results = cursor.fetchall()
                
                knowledge_items = []
                for row in results:
                    knowledge_items.append({
                        'content': row[0],
                        'category': row[1],
                        'source': row[2],
                        'similarity': float(row[3])
                    })
                    
                logger.info(f"✅ Found {len(knowledge_items)} relevant knowledge items")
                return knowledge_items
                
        except Exception as e:
            logger.error(f"❌ Knowledge search failed: {e}")
            return []
            
    def enhance_prompt(self, user_message: str, model_type: str = "general") -> str:
        """Enhance user prompt with relevant knowledge"""
        try:
            # Search for relevant knowledge
            relevant_knowledge = self.search_knowledge(user_message, limit=3)
            
            if not relevant_knowledge:
                return user_message
                
            # Build enhanced prompt with context
            threshold = float(os.getenv('SIMILARITY_THRESHOLD', '0.3'))
            context = "\n".join([
                f"- {item['content']}" 
                for item in relevant_knowledge 
                if item['similarity'] > threshold
            ])
            
            # Debug logging
            logger.info(f"🔍 Similarity threshold: {threshold}")
            for item in relevant_knowledge:
                logger.info(f"🔍 Knowledge item similarity: {item['similarity']:.3f}")
            
            if not context:
                logger.warning("⚠️ No knowledge items passed similarity threshold")
                return user_message
                
            enhanced_prompt = f"""You are a helpful agricultural consultant specializing in biodynamic farming and sustainable agriculture. 

Relevant knowledge context:
{context}

Customer question: {user_message}

Please provide a helpful, accurate response based on the context above. Focus on practical advice for farming and gardening."""

            logger.info("✅ Prompt enhanced with knowledge context")
            return enhanced_prompt
            
        except Exception as e:
            logger.error(f"❌ Prompt enhancement failed: {e}")
            return user_message
            
    def log_conversation(self, session_id: str, user_message: str, bot_response: str, 
                        model_used: str, response_time: float):
        """Log conversation for analytics"""
        try:
            with self.db_connection.cursor() as cursor:
                cursor.execute("""
                    INSERT INTO conversations 
                    (session_id, user_message, bot_response, model_used, response_time)
                    VALUES (%s, %s, %s, %s, %s)
                """, (session_id, user_message, bot_response, model_used, response_time))
                
                self.db_connection.commit()
                logger.info("✅ Conversation logged")
                
        except Exception as e:
            logger.error(f"❌ Conversation logging failed: {e}")
            self.db_connection.rollback()
            
    def get_conversation_history(self, session_id: str, limit: int = 10) -> List[Dict]:
        """Get recent conversation history for context"""
        try:
            with self.db_connection.cursor() as cursor:
                cursor.execute("""
                    SELECT user_message, bot_response, model_used, created_at
                    FROM conversations 
                    WHERE session_id = %s
                    ORDER BY created_at DESC
                    LIMIT %s
                """, (session_id, limit))
                
                results = cursor.fetchall()
                history = []
                
                for row in results:
                    history.append({
                        'user_message': row[0],
                        'bot_response': row[1],
                        'model_used': row[2],
                        'timestamp': row[3].isoformat()
                    })
                    
                return list(reversed(history))  # Return chronological order
                
        except Exception as e:
            logger.error(f"❌ History retrieval failed: {e}")
            return []
            
    def close(self):
        """Close database connection"""
        if self.db_connection:
            self.db_connection.close()
            logger.info("✅ Database connection closed")

# Initialize service
def create_rag_service():
    """Factory function to create RAG service"""
    try:
        return PublicRAGService()
    except Exception as e:
        logger.error(f"❌ Failed to initialize RAG service: {e}")
        return None

if __name__ == "__main__":
    # Test the RAG service
    print("🧪 Testing Public RAG Service...")
    
    rag = create_rag_service()
    if rag:
        print("✅ RAG service initialized successfully")
        
        # Test adding knowledge
        test_content = "Biodynamic farming uses natural preparations like BD 500 (horn manure) to enhance soil vitality and plant growth."
        if rag.add_knowledge(test_content, "biodynamic", "test"):
            print("✅ Test knowledge added")
            
            # Test search
            results = rag.search_knowledge("What is BD 500?")
            print(f"✅ Search results: {len(results)} items found")
            
            # Test prompt enhancement
            enhanced = rag.enhance_prompt("How do I use BD 500?")
            print(f"✅ Prompt enhanced: {len(enhanced)} characters")
            
        rag.close()
    else:
        print("❌ RAG service initialization failed")
