#!/usr/bin/env python3
"""
Claude AI Service for Laravel Succession Planning
Super fast cloud-based AI replacing slow local Ollama setup
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Dict, Any, Optional
import requests
import json
import logging
import os
from dotenv import load_dotenv

# Load environment variables from .env file
load_dotenv()

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Claude AI Service", 
    description="Laravel bridge to Claude API for succession planning",
    version="2.0.0"
)

# Enable CORS for Laravel requests
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Claude configuration
CLAUDE_API_KEY = os.getenv('CLAUDE_API_KEY')
CLAUDE_API_URL = "https://api.anthropic.com/v1/messages"

class AskRequest(BaseModel):
    question: str
    context: Optional[str] = None

class ClaudeClient:
    def __init__(self, api_key: str):
        self.api_key = api_key
        self.headers = {
            "x-api-key": api_key,
            "anthropic-version": "2023-06-01",
            "content-type": "application/json"
        }
    
    def ask_claude(self, prompt: str, max_tokens: int = 400) -> str:
        """Query Claude 3.5 Haiku via Anthropic API"""
        
        payload = {
            "model": "claude-3-5-haiku-20241022",
            "max_tokens": max_tokens,
            "messages": [
                {
                    "role": "user",
                    "content": prompt
                }
            ]
        }
        
        try:
            response = requests.post(
                CLAUDE_API_URL, 
                headers=self.headers, 
                json=payload, 
                timeout=30
            )
            
            if response.status_code == 200:
                data = response.json()
                if 'content' in data and len(data['content']) > 0:
                    return data['content'][0]['text']
                else:
                    raise Exception("No content in response")
            else:
                logger.error(f"Claude API error: {response.status_code} - {response.text}")
                raise Exception(f"API returned {response.status_code}")
                
        except requests.exceptions.Timeout:
            raise Exception("Claude API timeout")
        except Exception as e:
            logger.error(f"Claude API call failed: {str(e)}")
            raise

# Initialize Claude client
claude_client = None
if CLAUDE_API_KEY:
    claude_client = ClaudeClient(CLAUDE_API_KEY)
    logger.info("Claude client initialized successfully")
else:
    logger.warning("CLAUDE_API_KEY not found - API calls will fail")

def build_agricultural_prompt(question: str, context: str = None) -> str:
    """Build enhanced agricultural prompt for better responses"""
    
    base_prompt = f"""You are an expert agricultural advisor specializing in biodynamic farming and succession planting. 
    
Question: {question}
    
Please provide detailed, practical advice including:
- Specific timing recommendations
- Variety suggestions when relevant
- Spacing and planting considerations
- Any biodynamic principles that apply

Keep your response focused and actionable (under 300 words)."""
    
    if context:
        base_prompt = f"""You are an expert agricultural advisor specializing in biodynamic farming and succession planting.

Context: {context}

Question: {question}

Please provide detailed, practical advice including:
- Specific timing recommendations  
- Variety suggestions when relevant
- Spacing and planting considerations
- Any biodynamic principles that apply

Keep your response focused and actionable (under 300 words)."""
    
    return base_prompt

@app.get("/")
async def root():
    return {
        "message": "Claude AI Service Ready",
        "model": "claude-3-5-haiku-20241022",
        "description": "Lightning-fast agricultural AI for succession planning", 
        "status": "operational",
        "api_connected": claude_client is not None,
        "features": ["succession_planning", "biodynamic_farming", "ultra_fast_responses"]
    }

@app.post("/ask")
async def ask_claude_model(request: AskRequest):
    """Main endpoint - connects Laravel to Claude API"""
    
    if not claude_client:
        raise HTTPException(status_code=500, detail="Claude API key not configured")
    
    try:
        logger.info(f"Processing question: {request.question[:100]}...")
        
        # Build enhanced prompt for agricultural context
        prompt = build_agricultural_prompt(request.question, request.context)
        
        # Call Claude API
        answer = claude_client.ask_claude(prompt, max_tokens=500)
        
        logger.info(f"Claude response: {answer[:100]}...")
        
        return {
            "answer": answer.strip(),
            "model": "claude-3-5-haiku-20241022",
            "success": True,
            "context": request.context,
            "confidence": "high",
            "response_time": "1-2 seconds",
            "source": "claude_api"
        }
        
    except Exception as e:
        logger.error(f"Claude AI service error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"AI service error: {str(e)}")

@app.get("/health")
async def health_check():
    """Health check endpoint"""
    try:
        if claude_client:
            # Quick test call
            test_response = claude_client.ask_claude("Test connection", max_tokens=10)
            return {
                "status": "healthy", 
                "api_connected": True,
                "test_response_length": len(test_response)
            }
        else:
            return {"status": "unhealthy", "error": "API key not configured"}
    except Exception as e:
        return {"status": "unhealthy", "error": str(e)}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8005)
