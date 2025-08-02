from fastapi import FastAPI, Request
from pydantic import BaseModel
from typing import Optional

app = FastAPI()

class AskRequest(BaseModel):
    question: str
    context: Optional[str] = None

@app.get("/")
def root():
    return {"message": "AI Crop Planning Service is running."}

@app.post("/ask")
def ask_ai(request: AskRequest):
    # Placeholder logic: echo the question
    return {
        "answer": f"You asked: '{request.question}'. (AI logic will go here.)",
        "context": request.context
    }
