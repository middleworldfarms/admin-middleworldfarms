# Hugging Face Transformers AI Options for Succession Planning

## Current Need
Replace slow Ollama Phi-3 (40+ seconds) with faster inference for succession planning AI.

## Option 1: Agriculture-Focused Models

### recobo/agriculture-bert-uncased (NOT SUITABLE)
```python
# This is BERT for fill-mask, not text generation
from transformers import pipeline
pipe = pipeline("fill-mask", model="recobo/agriculture-bert-uncased")
# Only fills missing words, can't generate full responses
```

## Option 2: CPU-Optimized Text Generation Models

### microsoft/DialoGPT-small (Good for Chat)
```python
from transformers import pipeline, AutoTokenizer, AutoModelForCausalLM
import torch

# CPU-optimized setup
tokenizer = AutoTokenizer.from_pretrained("microsoft/DialoGPT-small")
model = AutoModelForCausalLM.from_pretrained("microsoft/DialoGPT-small")

# Generate response
def generate_response(prompt):
    inputs = tokenizer.encode(prompt + tokenizer.eos_token, return_tensors='pt')
    outputs = model.generate(
        inputs, 
        max_length=1000,
        num_beams=5,
        temperature=0.7,
        do_sample=True,
        pad_token_id=tokenizer.eos_token_id
    )
    response = tokenizer.decode(outputs[:, inputs.shape[-1]:][0], skip_special_tokens=True)
    return response
```

### google/flan-t5-base (Good for Instructions)
```python
from transformers import pipeline

# This model follows instructions well
generator = pipeline(
    "text2text-generation", 
    model="google/flan-t5-base",
    device=-1  # CPU
)

def get_succession_plan(crop, region, season):
    prompt = f"""Plan succession planting for {crop} in {region} during {season}. 
    Include timing, varieties, and spacing recommendations."""
    
    result = generator(
        prompt, 
        max_length=512, 
        temperature=0.7,
        do_sample=True
    )
    return result[0]['generated_text']
```

## Option 3: Lightweight Chat Models

### microsoft/phi-2 (Smaller than Phi-3)
```python
from transformers import AutoModelForCausalLM, AutoTokenizer
import torch

tokenizer = AutoTokenizer.from_pretrained("microsoft/phi-2", trust_remote_code=True)
model = AutoModelForCausalLM.from_pretrained("microsoft/phi-2", trust_remote_code=True)

def chat_response(question):
    prompt = f"Question: {question}\nAnswer:"
    inputs = tokenizer(prompt, return_tensors="pt")
    
    with torch.no_grad():
        outputs = model.generate(
            **inputs,
            max_new_tokens=256,
            temperature=0.7,
            do_sample=True,
            pad_token_id=tokenizer.eos_token_id
        )
    
    response = tokenizer.decode(outputs[0][inputs.input_ids.shape[1]:], skip_special_tokens=True)
    return response
```

## Performance Comparison (Estimated on AMD EPYC-Milan)

| Model | Size | Speed (Est.) | Quality | Agriculture Focus |
|-------|------|--------------|---------|-------------------|
| recobo/agriculture-bert | 110MB | 1-2s | N/A (fill-mask only) | High |
| microsoft/DialoGPT-small | 117MB | 3-8s | Medium | Low |
| google/flan-t5-base | 248MB | 5-12s | High | Medium |
| microsoft/phi-2 | 2.7GB | 15-25s | High | Medium |
| **Current Ollama Phi-3** | 2.2GB | **40+s** | High | Medium |

## Recommendation

**Try google/flan-t5-base first:**
- Good at following instructions
- Reasonable size (248MB)
- Estimated 5-12 seconds (much better than 40+)
- Works well for agricultural planning tasks

## Integration Plan

1. Create new service: `transformers_ai_service.py`
2. Use same FastAPI interface as current `phi3_ai_service.py`
3. Keep existing Laravel integration unchanged
4. A/B test performance vs current Ollama setup

Would you like me to implement the flan-t5-base integration?
