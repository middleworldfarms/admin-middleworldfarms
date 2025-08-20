# Instructions to Update AI Services with RAG Support

## Files Created
1. `shared_rag_service.py` - Already created in main directory
2. `rag_updated_services/phi3_ai_service_with_rag.py` - Updated Phi-3 service

## Manual Steps Required (need sudo)

### Step 1: Stop existing services
```bash
sudo pkill -f "phi3_ai_service.py"
sudo pkill -f "gemma2_ai_service.py" 
sudo pkill -f "tinyllama_ai_service.py"
```

### Step 2: Backup original files
```bash
sudo cp phi3_ai_service.py phi3_ai_service.py.backup
sudo cp gemma2_ai_service.py gemma2_ai_service.py.backup
sudo cp tinyllama_ai_service.py tinyllama_ai_service.py.backup
```

### Step 3: Replace with RAG-enabled versions
```bash
sudo cp rag_updated_services/phi3_ai_service_with_rag.py phi3_ai_service.py
```

### Step 4: Set environment variables and restart
```bash
export ENABLE_VECTOR_DB=true
export PGVECTOR_DB=vector_db
export PGVECTOR_USER=vector_user
export PGVECTOR_PASSWORD=vector_password
export PGVECTOR_HOST=localhost
export PGVECTOR_PORT=5432
export EMBEDDING_MODEL=all-MiniLM-L6-v2

# Start services
nohup python3 phi3_ai_service.py > phi3_ai_service.log 2>&1 &
nohup python3 gemma2_ai_service.py > gemma2_ai_service.log 2>&1 &
nohup python3 tinyllama_ai_service.py > tinyllama_ai_service.log 2>&1 &
```

### Step 5: Test RAG is working
```bash
curl -s http://localhost:8005/ | jq '.rag_enabled'
curl -X POST http://localhost:8005/ask -H 'Content-Type: application/json' -d '{"question":"What is BD 500?"}'
```

## What This Adds
- **Shared RAG Service**: All models can access the vector database
- **Enhanced Prompts**: Questions get relevant context from vector database
- **Biodynamic Knowledge**: Pre-loaded with BD 500, BD 501, lunar cycles, etc.
- **Seamless Switching**: You can still switch between Phi-3, Gemma2, TinyLlama
- **Same API**: Laravel integration unchanged, just enhanced responses

## Status Checking
Each service now reports:
- `rag_enabled`: true/false
- `features`: includes "vector_search" when RAG is working
- Enhanced responses with biodynamic context
