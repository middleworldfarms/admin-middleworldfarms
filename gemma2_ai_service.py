#!/usr/bin/env python3
"""
Gemma2:2b AI Service for Laravel Succession Planning
Port 8006 - Comparison/Testing Service with RAG support
"""

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import httpx
import logging
import uvicorn

# Import shared RAG service
try:
    from shared_rag_service import get_enhanced_prompt, is_rag_enabled
    RAG_AVAILABLE = True
except ImportError:
    RAG_AVAILABLE = False
    logging.warning("RAG service not available")

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="Gemma2:2b AI Service", description="Agricultural AI for succession planning")

class AIRequest(BaseModel):
    prompt: str
    context: str = ""

class AIResponse(BaseModel):
    response: str
    model: str = "gemma2:2b"

@app.post("/ask", response_model=AIResponse)
async def ask_ai(request: AIRequest):
    """
    Process agricultural succession planning requests using Gemma2:2b
    """
    try:
        # Simplified prompt for Gemma2:2b (complex prompts cause timeouts)
        if request.context:
            enhanced_prompt = f"Agricultural question: {request.prompt}\nContext: {request.context}\nProvide farming advice:"
        else:
            enhanced_prompt = f"Agricultural question: {request.prompt}\nProvide farming advice:"

        # Call Ollama API with Gemma2:2b model
        async with httpx.AsyncClient(timeout=30.0) as client:
            response = await client.post(
                "http://localhost:11434/api/generate",
                json={
                    "model": "gemma2:2b",
                    "prompt": enhanced_prompt,
                    "stream": False
                }
            )
            response.raise_for_status()
            
            result = response.json()
            ai_response = result.get("response", "No response generated")
            
            logger.info(f"Gemma2:2b response generated successfully")
            
            return AIResponse(
                response=ai_response,
                model="gemma2:2b"
            )
            
    except httpx.TimeoutException:
        logger.error("Gemma2:2b request timed out")
        raise HTTPException(status_code=504, detail="AI service timeout - please try again")
    except httpx.HTTPStatusError as e:
        logger.error(f"Gemma2:2b HTTP error: {e}")
        raise HTTPException(status_code=502, detail=f"AI service error: {e}")
    except Exception as e:
        logger.error(f"Gemma2:2b unexpected error: {e}")
        raise HTTPException(status_code=500, detail=f"Internal server error: {str(e)}")

@app.post("/generate", response_model=AIResponse)
async def generate(request: AIRequest):
    """
    Generate endpoint for direct testing - same as /ask but matches test format
    """
    return await ask_ai(request)

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {"status": "healthy", "model": "gemma2:2b", "port": 8006}

@app.get("/")
async def root():
    """Root endpoint info"""
    return {
        "service": "Gemma2:2b AI Service",
        "model": "gemma2:2b", 
        "port": 8006,
        "endpoints": ["/ask", "/health"],
        "purpose": "Agricultural succession planning - testing/comparison service"
    }

if __name__ == "__main__":
    print("🌾 Starting Gemma2:2b AI Service for Succession Planning...")
    print("🚀 Ready to serve Laravel on http://localhost:8006")
    uvicorn.run(app, host="0.0.0.0", port=8006)
