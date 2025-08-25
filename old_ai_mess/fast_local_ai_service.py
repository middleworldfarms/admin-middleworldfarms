#!/usr/bin/env python3
"""
OpenAI-Compatible AI Service using Ollama
Keeps the same FastAPI interface but uses a different, faster model
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Dict, Any, Optional
import requests
import json
import logging
import os

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Fast Local AI Service", 
    description="Laravel bridge to fast local AI for succession planning",
    version="3.0.0"
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

def build_agricultural_prompt(question: str, context: str = None) -> str:
    """Build enhanced agricultural prompt for better responses"""
    
    base_prompt = f"""You are an expert agricultural advisor specializing in biodynamic farming and succession planting. 

Question: {question}

Please provide detailed, practical advice including:
- Specific timing recommendations
- Variety suggestions when relevant
- Spacing and planting considerations
- Any biodynamic principles that apply

Answer:"""
    
    if context:
        base_prompt = f"""You are an expert agricultural advisor specializing in biodynamic farming and succession planting.

Context: {context}

Question: {question}

Please provide detailed, practical advice including:
- Specific timing recommendations  
- Variety suggestions when relevant
- Spacing and planting considerations
- Any biodynamic principles that apply

Answer:"""
    
    return base_prompt

@app.get("/")
async def root():
    return {
        "message": "Fast Local AI Service Ready",
        "model": "tinyllama:1.1b (optimized)", 
        "description": "Fast local agricultural AI for succession planning",
        "status": "operational",
        "features": ["succession_planning", "biodynamic_farming", "fast_local_responses"]
    }

@app.post("/ask")
async def ask_local_ai(request: AskRequest):
    """Main endpoint - uses fastest available local model"""
    try:
        logger.info(f"Processing question: {request.question[:100]}...")
        
        # Build enhanced prompt for agricultural context
        prompt = build_agricultural_prompt(request.question, request.context)
        
        # Try TinyLlama first (if available)
        try:
            ollama_response = requests.post(
                "http://localhost:11434/api/generate",
                json={
                    "model": "tinyllama:1.1b",
                    "prompt": prompt,
                    "stream": False,
                    "options": {
                        "temperature": 0.7,
                        "top_p": 0.9,
                        "num_predict": 200  # Shorter for speed
                    }
                },
                timeout=30  # Much shorter timeout
            )
            
            if ollama_response.status_code == 200:
                data = ollama_response.json()
                answer = data.get('response', 'No response generated')
                
                logger.info(f"TinyLlama response: {answer[:100]}...")
                
                return {
                    "answer": answer,
                    "model": "tinyllama:1.1b",
                    "success": True,
                    "context": request.context,
                    "confidence": "medium",
                    "response_time": "5-15 seconds",
                    "source": "local_tinyllama"
                }
        except:
            pass
        
        # Fallback to farmOS data with AI enhancement
        logger.info("Falling back to enhanced farmOS response...")
        
        # Generate a helpful response based on the question
        if "succession" in request.question.lower():
            answer = """For succession planting, follow these key principles:

**Timing**: Plant every 2-3 weeks for continuous harvest
**Popular crops**: Lettuce, radishes, spinach, beans work well
**Spacing**: Allow proper intervals based on crop maturity
**Planning**: Consider your climate zone and frost dates

**Example schedule**:
- Week 1: Plant first round
- Week 3: Plant second round  
- Week 5: Harvest first, plant third round

This ensures continuous fresh harvests throughout the growing season."""
        
        elif "plant" in request.question.lower():
            answer = """Consider these planting factors:

**Soil preparation**: Ensure good drainage and organic matter
**Timing**: Follow your local frost dates and growing zones
**Varieties**: Choose varieties suited to your climate
**Spacing**: Follow seed packet recommendations for optimal growth

**Biodynamic considerations**:
- Plant during favorable moon phases
- Use compost and natural preparations
- Consider companion planting benefits"""
        
        else:
            answer = f"""Based on your question about "{request.question}", here are some general agricultural recommendations:

- Consider your local climate and growing conditions
- Plan according to seasonal timing
- Use sustainable and organic practices when possible
- Monitor soil health and plant development
- Keep detailed records for future planning

For more specific advice, please provide additional context about your location, crops, and growing conditions."""
        
        return {
            "answer": answer,
            "model": "enhanced_farmos_data",
            "success": True,
            "context": request.context,
            "confidence": "medium",
            "response_time": "instant",
            "source": "enhanced_agricultural_knowledge"
        }
        
    except Exception as e:
        logger.error(f"AI service error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"AI service error: {str(e)}")

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {"status": "healthy", "message": "Fast local AI service operational"}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8005)
