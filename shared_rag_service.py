#!/usr/bin/env python3
"""
Shared RAG Service for All AI Models
Provides vector database integration for Phi-3, Gemma2, and TinyLlama services
"""

import os
import logging
from typing import List, Dict, Any, Optional
from dataclasses import dataclass

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Try to import required packages
try:
    import psycopg2
    import psycopg2.extras
    from sentence_transformers import SentenceTransformer
    DEPENDENCIES_AVAILABLE = True
except ImportError as e:
    logger.warning(f"RAG dependencies not available: {e}")
    DEPENDENCIES_AVAILABLE = False

@dataclass
class VectorRecord:
    text: str
    embedding: List[float]
    metadata: Dict[str, Any]

class SharedRAGService:
    """Shared RAG service that all AI models can use"""
    
    def __init__(self):
        self.enabled = os.getenv('ENABLE_VECTOR_DB', 'true').lower() == 'true'
        self.conn = None
        self.embedding_model = None
        
        if self.enabled and DEPENDENCIES_AVAILABLE:
            self._initialize_db()
            self._initialize_embeddings()
        else:
            logger.info("RAG service disabled or dependencies unavailable")
    
    def _initialize_db(self):
        """Initialize PostgreSQL connection"""
        try:
            conn = psycopg2.connect(
                host=os.getenv('PGVECTOR_HOST', 'localhost'),
                port=os.getenv('PGVECTOR_PORT', '5432'),
                database=os.getenv('PGVECTOR_DB', 'vector_db'),
                user=os.getenv('PGVECTOR_USER', 'vector_user'),
                password=os.getenv('PGVECTOR_PASSWORD', 'v2WyfrCHBF0CruQ+PpAiQ+Y6w4Q4hIsExcYzfU4aAIo='),
            )
            self._ensure_table()
            logger.info("✅ Vector database connected successfully")
        except Exception as e:
            logger.warning(f"Vector database connection failed: {e}")
            self.conn = None
            self.enabled = False
    
    def _ensure_table(self):
        """Ensure the vectors table exists"""
        if not self.conn:
            return
            
        with self.conn.cursor() as cur:
            cur.execute('''
                CREATE TABLE IF NOT EXISTS vectors (
                    id SERIAL PRIMARY KEY,
                    text TEXT,
                    embedding VECTOR(384),
                    metadata JSONB
                );
            ''')
            self.conn.commit()
    
    def _initialize_embeddings(self):
        """Initialize sentence transformer model"""
        try:
            model_name = os.getenv('EMBEDDING_MODEL', 'all-MiniLM-L6-v2')
            self.embedding_model = SentenceTransformer(model_name)
            logger.info(f"✅ Embedding model loaded: {model_name}")
        except Exception as e:
            logger.warning(f"Failed to load embedding model: {e}")
            self.embedding_model = None
            self.enabled = False
    
    def embed_texts(self, texts: List[str]) -> List[List[float]]:
        """Generate embeddings for texts"""
        if not self.embedding_model:
            return []
        return self.embedding_model.encode(texts, normalize_embeddings=True).tolist()
    
    def add_documents(self, texts: List[str], source: str = "biodynamic_knowledge") -> int:
        """Add documents to vector database"""
        if not self.enabled or not self.conn:
            logger.info("RAG not enabled, skipping document addition")
            return 0
        
        try:
            embeddings = self.embed_texts(texts)
            metadatas = [{"source": source, "chunk_index": i} for i in range(len(texts))]
            
            with self.conn.cursor() as cur:
                for text, embedding, metadata in zip(texts, embeddings, metadatas):
                    cur.execute(
                        "INSERT INTO vectors (text, embedding, metadata) VALUES (%s, %s, %s)",
                        (text, embedding, metadata)
                    )
                self.conn.commit()
            
            logger.info(f"Added {len(texts)} documents to vector database")
            return len(texts)
        except Exception as e:
            logger.error(f"Failed to add documents: {e}")
            return 0
    
    def retrieve_context(self, query: str, top_k: int = 1) -> List[VectorRecord]:  # Reduced from 3 to 1
        """Retrieve relevant context for a query - OPTIMIZED FOR SPEED"""
        if not self.enabled or not self.conn:
            return []
        
        # Skip RAG for simple/short queries to save time
        if len(query.split()) < 5:
            logger.info("Skipping RAG for simple query")
            return []
        
        try:
            query_embedding = self.embed_texts([query])[0]
            
            with self.conn.cursor(cursor_factory=psycopg2.extras.DictCursor) as cur:
                cur.execute(
                    """
                    SELECT text, embedding, metadata, (embedding <#> %s::vector) AS distance
                    FROM vectors
                    ORDER BY embedding <#> %s::vector ASC
                    LIMIT %s
                    """,
                    (query_embedding, query_embedding, top_k)
                )
                rows = cur.fetchall()
                
                results = [
                    VectorRecord(
                        text=row['text'], 
                        embedding=list(row['embedding']), 
                        metadata=row['metadata']
                    ) 
                    for row in rows
                ]
                
                logger.info(f"Retrieved {len(results)} relevant documents (FAST MODE)")
                return results
        except Exception as e:
            logger.error(f"Failed to retrieve context: {e}")
            return []
    
    def enhance_prompt_with_context(self, question: str, base_prompt: str) -> str:
        """Enhance a prompt with relevant context from vector database"""
        if not self.enabled:
            return base_prompt
        
        # Retrieve relevant context
        context_docs = self.retrieve_context(question, top_k=3)
        
        if not context_docs:
            return base_prompt
        
        # Build context section
        context_section = "\n\nRELEVANT KNOWLEDGE:\n"
        for i, doc in enumerate(context_docs, 1):
            context_section += f"{i}. {doc.text}\n"
        
        # Combine with original prompt
        enhanced_prompt = base_prompt + context_section + f"\n\nBased on the above knowledge and your expertise, please answer: {question}"
        
        return enhanced_prompt

# Global instance that all services can import
rag_service = SharedRAGService()

# Convenience functions for easy integration
def get_enhanced_prompt(question: str, base_prompt: str) -> str:
    """Get an enhanced prompt with RAG context"""
    return rag_service.enhance_prompt_with_context(question, base_prompt)

def add_biodynamic_knowledge(texts: List[str]) -> int:
    """Add biodynamic knowledge to the vector database"""
    return rag_service.add_documents(texts, "biodynamic_principles")

def is_rag_enabled() -> bool:
    """Check if RAG is enabled and working"""
    return rag_service.enabled

# Initialize with some basic biodynamic knowledge if database is empty
def initialize_biodynamic_knowledge():
    """Initialize the vector database with basic biodynamic knowledge"""
    if not rag_service.enabled:
        return
    
    basic_knowledge = [
        "BD 500 is a biodynamic preparation made from cow manure that has been packed into cow horns and buried in the earth over winter. It enhances soil vitality and promotes healthy root development.",
        "BD 501 is a biodynamic preparation made from ground quartz (silica) that has been packed into cow horns and buried during summer. It enhances plant growth, light reception, and fruit/flower development.",
        "The nine biodynamic preparations (BD 500-508) work together to create a holistic farming system that treats the farm as a living organism.",
        "Moon phases affect plant growth in biodynamic farming. New moon to full moon is ideal for above-ground growth, while full moon to new moon favors root development.",
        "Companion planting in biodynamic systems uses plant relationships to create beneficial growing environments and pest management.",
        "Composting in biodynamic farming involves specific preparations (BD 502-507) that enhance the decomposition process and create highly fertile soil amendments.",
        "Succession planting ensures continuous harvests by staggering sowings at regular intervals, typically every 14-21 days for fast-growing crops.",
        "Crop rotation in biodynamic systems follows patterns that maintain soil health and break pest/disease cycles while considering planetary influences."
    ]
    
    added = add_biodynamic_knowledge(basic_knowledge)
    if added > 0:
        logger.info(f"Initialized vector database with {added} pieces of biodynamic knowledge")

# Auto-initialize when module is imported
if __name__ != "__main__":
    try:
        initialize_biodynamic_knowledge()
    except Exception as e:
        logger.warning(f"Failed to initialize biodynamic knowledge: {e}")
