#!/usr/bin/env python3
"""
TinyLlama AI Service for Agricultural Succession Planning
Provides lightweight AI responses for farming questions using Ollama TinyLlama model
"""

import asyncio
import json
import logging
import os
import requests
from datetime import datetime
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import Optional

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# FastAPI app
app = FastAPI(
    title="TinyLlama Agricultural AI Service",
    description="Lightweight AI service for agricultural succession planning using TinyLlama",
    version="1.0.0"
)

class QueryRequest(BaseModel):
    question: str
    context: Optional[str] = None

class QueryResponse(BaseModel):
    response: str
    model: str = "tinyllama"
    timestamp: str
    processing_time: Optional[float] = None

def query_ollama_tinyllama(prompt: str) -> str:
    """Query TinyLlama model via Ollama API"""
    try:
        # Enhanced agricultural prompt
        enhanced_prompt = f"""You are a helpful agricultural consultant specializing in sustainable farming practices. 
        
Question: {prompt}

Please provide a practical, clear answer focused on farming and agricultural practices:"""

        payload = {
            "model": "tinyllama:latest",
            "prompt": enhanced_prompt,
            "stream": False,
            "options": {
                "temperature": 0.7,
                "num_predict": 200,
                "top_k": 40,
                "top_p": 0.9
            }
        }
        
        response = requests.post(
            "http://localhost:11434/api/generate",
            json=payload,
            timeout=30
        )
        response.raise_for_status()
        
        result = response.json()
        return result.get("response", "No response generated")
        
    except requests.exceptions.Timeout:
        logger.error("TinyLlama timeout")
        raise HTTPException(status_code=408, detail="AI processing timeout")
    except requests.exceptions.RequestException as e:
        logger.error(f"TinyLlama request error: {e}")
        raise HTTPException(status_code=503, detail="AI service unavailable")
    except Exception as e:
        logger.error(f"TinyLlama error: {e}")
        raise HTTPException(status_code=500, detail="Internal server error")

@app.get("/")
async def root():
    """Service status and information"""
    return {
        "service": "TinyLlama AI Service",
        "model": "tinyllama",
        "port": 8007,
        "endpoints": ["/ask", "/health"],
        "purpose": "Agricultural succession planning - lightweight responses",
        "status": "operational",
        "timestamp": datetime.now().isoformat()
    }

@app.post("/ask", response_model=QueryResponse)
async def ask_question(request: QueryRequest):
    """Process agricultural questions using TinyLlama"""
    start_time = datetime.now()
    
    logger.info(f"Processing question: {request.question[:50]}...")
    
    try:
        # Get AI response
        ai_response = query_ollama_tinyllama(request.question)
        
        processing_time = (datetime.now() - start_time).total_seconds()
        
        logger.info(f"TinyLlama response: {ai_response[:100]}...")
        
        return QueryResponse(
            response=ai_response,
            model="tinyllama",
            timestamp=datetime.now().isoformat(),
            processing_time=processing_time
        )
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Error processing question: {e}")
        raise HTTPException(status_code=500, detail="Failed to process question")

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    try:
        # Test Ollama connection
        response = requests.get("http://localhost:11434/api/version", timeout=5)
        ollama_status = "connected" if response.status_code == 200 else "disconnected"
    except:
        ollama_status = "disconnected"
    
    return {
        "status": "healthy",
        "service": "TinyLlama AI Service",
        "model": "tinyllama",
        "ollama_connection": ollama_status,
        "timestamp": datetime.now().isoformat()
    }

if __name__ == "__main__":
    import uvicorn
    logger.info("🦙 Starting TinyLlama AI Service on port 8007...")
    uvicorn.run(app, host="0.0.0.0", port=8007)
