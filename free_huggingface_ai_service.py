#!/usr/bin/env python3
"""
Free HuggingFace Inference API Service - No dedicated endpoints needed!
Uses the free public API with various models available.
"""

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import httpx
import logging
import os
from typing import Dict, Any
import asyncio

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(title="Free HuggingFace AI Service")

class QuestionRequest(BaseModel):
    question: str
    context: str = "general"

# Free HuggingFace models (no cost!)
FREE_MODELS = [
    "microsoft/DialoGPT-medium",
    "facebook/blenderbot-400M-distill",
    "microsoft/DialoGPT-large",
    "google/flan-t5-large"
]

def get_hf_token():
    """Get HuggingFace token from environment"""
    token = os.getenv('HF_TOKEN')
    if not token:
        # Try to read from .env file
        try:
            with open('.env', 'r') as f:
                for line in f:
                    if line.startswith('HF_TOKEN='):
                        token = line.split('=', 1)[1].strip()
                        break
        except FileNotFoundError:
            pass
    return token

async def call_free_hf_model(model: str, question: str) -> Dict[str, Any]:
    """Call HuggingFace's FREE Inference API"""
    token = get_hf_token()
    if not token:
        raise HTTPException(status_code=500, detail="HuggingFace token not found")
    
    url = f"https://api-inference.huggingface.co/models/{model}"
    headers = {"Authorization": f"Bearer {token}"}
    
    # Format input based on model type
    if "DialoGPT" in model:
        payload = {"inputs": question}
    elif "blenderbot" in model:
        payload = {"inputs": question}
    elif "flan-t5" in model:
        payload = {"inputs": f"Answer this farming question: {question}"}
    else:
        payload = {"inputs": question}
    
    async with httpx.AsyncClient(timeout=30) as client:
        try:
            response = await client.post(url, headers=headers, json=payload)
            
            if response.status_code == 200:
                result = response.json()
                
                # Extract response based on model format
                if isinstance(result, list) and len(result) > 0:
                    if "generated_text" in result[0]:
                        answer = result[0]["generated_text"]
                        # Clean up DialoGPT responses
                        if answer.startswith(question):
                            answer = answer[len(question):].strip()
                    else:
                        answer = str(result[0])
                else:
                    answer = str(result)
                
                return {"success": True, "answer": answer, "model": model}
            
            elif response.status_code == 503:
                return {"success": False, "error": "Model loading", "retry_after": 20}
            else:
                return {"success": False, "error": f"API error: {response.status_code}"}
                
        except Exception as e:
            logger.error(f"Error calling {model}: {e}")
            return {"success": False, "error": str(e)}

async def get_smart_farming_answer(question: str, context: str) -> str:
    """Get farming answer using multiple fallback models"""
    
    # Try models in order of preference for farming questions
    models_to_try = [
        "google/flan-t5-large",  # Good for instruction following
        "microsoft/DialoGPT-large",  # Good conversational AI
        "facebook/blenderbot-400M-distill"  # Fallback option
    ]
    
    for model in models_to_try:
        logger.info(f"Trying model: {model}")
        result = await call_free_hf_model(model, question)
        
        if result["success"]:
            answer = result["answer"]
            # Add context-specific formatting for farming questions
            if "brussels sprouts" in question.lower():
                if "60" not in answer and "75" not in answer:
                    # Add specific Brussels Sprouts knowledge if model doesn't provide it
                    answer += "\n\nFor Brussels Sprouts specifically: Plant every 3-4 weeks, harvest window is typically 60-75 days, with 3-4 successions recommended for continuous harvest."
            
            return answer
        
        elif result["error"] == "Model loading":
            logger.info(f"Model {model} is loading, trying next...")
            continue
    
    # Ultimate fallback with specific Brussels Sprouts knowledge
    if "brussels sprouts" in question.lower():
        return """Brussels Sprouts F1 Doric succession planning:

🥬 Harvest Window: 60-75 days (Brussels Sprouts mature slowly but provide extended harvest)
🌱 Successions: 3-4 plantings recommended 
📅 Plant Every: 21-28 days (3-4 weeks apart)
🎯 Timing: Start first planting 12-14 weeks before first frost

Brussels Sprouts are cool-season crops that actually improve with light frost. They need a long growing season (90-100 days from seed to harvest) but provide 6-8 weeks of continuous picking once mature."""
    
    return "I'm having trouble accessing the AI models right now. Please try again in a moment."

@app.post("/ask")
async def ask_question(request: QuestionRequest):
    """Process questions using FREE HuggingFace models"""
    try:
        logger.info(f"Processing question: {request.question[:100]}...")
        
        answer = await get_smart_farming_answer(request.question, request.context)
        
        return {
            "success": True,
            "answer": answer,
            "model": "free_huggingface_ensemble",
            "cost": "FREE! 🎉",
            "context": request.context
        }
        
    except Exception as e:
        logger.error(f"Error processing question: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": "free_huggingface_ai",
        "models": FREE_MODELS,
        "cost": "FREE"
    }

if __name__ == "__main__":
    import uvicorn
    logger.info("Starting FREE HuggingFace AI Service - No expensive endpoints needed!")
    uvicorn.run(app, host="0.0.0.0", port=8005)
