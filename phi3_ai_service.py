#!/usr/bin/env python3
"""
Phi-3 Mini AI Service for Laravel Succession Planning
Clean bridge between Laravel and Ollama Phi-3 Mini with RAG support
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Dict, Any, Optional
import requests
import json
import logging

# RAG disabled for performance optimization
RAG_AVAILABLE = False
logging.info("RAG service disabled for optimal Phi-3 performance")

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Phi-3 Mini AI Service", 
    description="Laravel bridge to Phi-3 Mini for succession planning",
    version="1.0.0"
)

# Enable CORS for Laravel requests
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class AskRequest(BaseModel):
    question: str
    context: Optional[str] = None

@app.get("/")
async def root():
    return {
        "message": "Phi-3 Mini AI Service Ready",
        "model": "phi3:mini", 
        "description": "High-quality agricultural AI for succession planning",
        "status": "operational",
        "rag_enabled": False,
        "features": ["succession_planning", "biodynamic_farming"]
    }

@app.post("/ask")
async def ask_phi3(request: AskRequest):
    """Main endpoint - connects Laravel to Phi-3 Mini"""
    try:
        logger.info(f"Processing question: {request.question[:100]}...")
        
        # Build enhanced prompt for agricultural context
        prompt = build_agricultural_prompt(request.question, request.context)
        
        # Call Ollama Phi-3 Mini directly
        ollama_response = requests.post(
            "http://localhost:11434/api/generate",
            json={
                "model": "phi3:mini",
                "prompt": prompt,
                "stream": False,
                "options": {
                    "temperature": 0.7,
                    "top_p": 0.9,
                    "num_predict": 1000
                }
            },
            timeout=150
        )
        
        if ollama_response.status_code == 200:
            data = ollama_response.json()
            answer = data.get('response', 'No response generated')
            
            logger.info(f"Phi-3 Mini response: {answer[:100]}...")
            
            return {
                "answer": answer,
                "model": "phi3:mini",
                "success": True,
                "context": request.context,
                "confidence": "high"
            }
        else:
            logger.error(f"Ollama error: {ollama_response.status_code}")
            raise HTTPException(status_code=500, detail="AI model unavailable")
            
    except requests.exceptions.Timeout:
        logger.error("Phi-3 Mini timeout")
        raise HTTPException(status_code=408, detail="AI processing timeout")
    except Exception as e:
        logger.error(f"AI service error: {str(e)}")
        raise HTTPException(status_code=500, detail="AI service error")

def build_agricultural_prompt(question: str, context: str = None) -> str:
    """Build enhanced prompt with comprehensive agricultural context and RAG"""
    
    # Enhanced variety database
    variety_database = """
VARIETY DATABASE:
Brussels Sprout F1 Doric: 64-68 days transplant to harvest, 45cm x 30cm spacing, 1.2-1.5kg yield, -2°C frost tolerance, premium variety
Lettuce Salanova: 45-50 days, 20cm x 20cm, 200-300g heads, bolt resistant, continuous harvest
Radish Cherry Belle: 25-30 days, 5cm spacing, bunching variety, succession plant every 10 days

COMMERCIAL PARAMETERS:
- Market Garden Scale: 160 beds/fields
- Succession Intervals: 14-21 days optimal
- Labor Efficiency: 45 minutes/bed harvest
- Premium Varieties: 20-30% price premium
- Cold Storage: Extends season 4-6 weeks
    """
    
    base_prompt = f"""You are a professional agricultural consultant specializing in commercial market garden operations, succession planning, and biodynamic farming principles.

{variety_database}

ANALYSIS REQUIREMENTS:
- Provide SPECIFIC numbers (exact days, spacing, yields)
- Include commercial metrics (labor, pricing, efficiency) 
- Address 160-field scale operations
- Give succession timing recommendations
- Include biodynamic and sustainable practices
- Consider lunar cycles and natural rhythms where relevant
- Include risk factors and mitigation

Focus on precision over generalization. Use the variety database for exact specifications.
"""
    
    if context and "succession" in context.lower():
        base_prompt += """
SUCCESSION PLANNING CONTEXT:
- Calculate optimal planting intervals
- Consider harvest labor scheduling
- Account for market demand timing  
- Plan for cold storage utilization
- Include biodynamic timing considerations
"""
    
    # Use RAG to enhance prompt if available - DISABLED FOR SPEED (USER PREFERS FAST PHI-3)
    if False: # RAG_AVAILABLE and is_rag_enabled():
        try:
            enhanced_prompt = get_enhanced_prompt(question, base_prompt)
            logger.info("Enhanced prompt with RAG context")
            return enhanced_prompt
        except Exception as e:
            logger.warning(f"RAG enhancement failed: {e}")
            
    return base_prompt + f"\n\nQuestion: {question}"

class GenerateRequest(BaseModel):
    prompt: str
    context: Optional[str] = None

@app.post("/generate")
async def generate(request: GenerateRequest):
    """
    Generate endpoint for direct testing - converts to /ask format
    """
    ask_request = AskRequest(question=request.prompt, context=request.context)
    result = await ask_phi3(ask_request)
    return {"response": result.get("answer", "No response generated"), "model": "phi3:mini"}

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    try:
        # Test Ollama connection
        response = requests.get("http://localhost:11434/api/version", timeout=5)
        ollama_ok = response.status_code == 200
        
        return {
            "status": "healthy" if ollama_ok else "degraded",
            "ollama": "connected" if ollama_ok else "disconnected",
            "model": "phi3:mini"
        }
    except:
        return {
            "status": "unhealthy",
            "ollama": "disconnected",
            "model": "phi3:mini"
        }

if __name__ == "__main__":
    import uvicorn
    print("🌱 Starting Phi-3 Mini AI Service for Succession Planning...")
    print("🚀 Ready to serve Laravel on http://localhost:8005")
    uvicorn.run(app, host="0.0.0.0", port=8005)
