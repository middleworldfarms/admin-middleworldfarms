#!/usr/bin/env python3
"""
Direct Transformers AI Service for Laravel Succession Planning
Using DialoGPT-medium locally via transformers library
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Dict, Any, Optional
import logging
import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Transformers AI Service", 
    description="Laravel bridge to DialoGPT via Transformers library",
    version="4.0.0"
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

# Global variables for model
tokenizer = None
model = None
model_loaded = False

def load_model():
    """Load DialoGPT model and tokenizer"""
    global tokenizer, model, model_loaded
    
    if model_loaded:
        return True
    
    try:
        logger.info("Loading GPT-2 model...")
        
        # Import here to avoid startup delays if not needed
        from transformers import AutoTokenizer, AutoModelForCausalLM
        import torch
        
        # Load tokenizer and model - using GPT2 for better instruction following
        tokenizer = AutoTokenizer.from_pretrained("gpt2")
        model = AutoModelForCausalLM.from_pretrained("gpt2")
        
        # Add padding token if needed
        if tokenizer.pad_token is None:
            tokenizer.pad_token = tokenizer.eos_token
        
        # Use CPU (since we don't have GPU)
        model = model.to('cpu')
        model.eval()  # Set to evaluation mode
        
        model_loaded = True
        logger.info("GPT-2 model loaded successfully!")
        return True
        
    except Exception as e:
        logger.error(f"Failed to load model: {str(e)}")
        return False

def generate_response(prompt: str, max_length: int = 200) -> str:
    """Generate response using DialoGPT"""
    global tokenizer, model
    
    if not model_loaded:
        raise Exception("Model not loaded")
    
    try:
        import torch
        
        # Encode the input
        inputs = tokenizer.encode(prompt + tokenizer.eos_token, return_tensors='pt')
        
        # Generate response
        with torch.no_grad():
            outputs = model.generate(
                inputs,
                max_length=inputs.shape[1] + max_length,
                num_beams=3,
                temperature=0.7,
                do_sample=True,
                pad_token_id=tokenizer.eos_token_id,
                no_repeat_ngram_size=2
            )
        
        # Decode only the new tokens
        response = tokenizer.decode(outputs[:, inputs.shape[-1]:][0], skip_special_tokens=True)
        return response.strip()
        
    except Exception as e:
        logger.error(f"Generation failed: {str(e)}")
        raise

def build_agricultural_prompt(question: str, context: str = None) -> str:
    """Build enhanced agricultural prompt for better responses"""
    
    base_prompt = f"""Agricultural Question: {question}

Expert agricultural advice:
- For succession planting, plant new crops every 2-3 weeks
- Consider your climate zone and frost dates  
- Popular succession crops: lettuce, radishes, spinach, beans
- Plant timing depends on variety maturity rates

Specific answer:"""
    
    if context:
        base_prompt = f"""Agricultural Question: {question}
Context: {context}

Expert agricultural advice:
- For succession planting, plant new crops every 2-3 weeks
- Consider your climate zone and frost dates  
- Popular succession crops: lettuce, radishes, spinach, beans
- Plant timing depends on variety maturity rates

Specific answer:"""
    
    return base_prompt

@app.on_event("startup")
async def startup_event():
    """Load model on startup"""
    logger.info("Starting up Transformers AI Service...")
    success = load_model()
    if not success:
        logger.error("Failed to load model during startup!")

@app.get("/")
async def root():
    return {
        "message": "Transformers AI Service Ready",
        "model": "gpt2",
        "description": "Local GPT-2 for succession planning", 
        "status": "operational" if model_loaded else "loading",
        "model_loaded": model_loaded,
        "features": ["succession_planning", "biodynamic_farming", "local_processing"]
    }

@app.post("/ask")
async def ask_dialogpt(request: AskRequest):
    """Main endpoint - uses local DialoGPT model"""
    
    # Ensure model is loaded
    if not model_loaded:
        logger.info("Model not loaded, attempting to load...")
        success = load_model()
        if not success:
            raise HTTPException(status_code=500, detail="Model failed to load")
    
    try:
        logger.info(f"Processing question: {request.question[:100]}...")
        
        # Build enhanced prompt for agricultural context
        prompt = build_agricultural_prompt(request.question, request.context)
        
        # Generate response with DialoGPT
        answer = generate_response(prompt, max_length=300)
        
        logger.info(f"DialoGPT response: {answer[:100]}...")
        
        return {
            "answer": answer,
            "model": "gpt2",
            "success": True,
            "context": request.context,
            "confidence": "high",
            "response_time": "5-15 seconds",
            "source": "local_transformers"
        }
        
    except Exception as e:
        logger.error(f"Transformers AI service error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"AI service error: {str(e)}")

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy" if model_loaded else "loading", 
        "model_loaded": model_loaded,
        "model": "gpt2"
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8005)
