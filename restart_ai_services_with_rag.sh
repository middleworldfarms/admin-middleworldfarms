#!/bin/bash
# Update Martin's AI services with RAG support
# Preserves existing functionality while adding vector database integration

# Environment variables for RAG
export ENABLE_VECTOR_DB=true
export PGVECTOR_DB=vector_db
export PGVECTOR_USER=vector_user
export PGVECTOR_PASSWORD=vector_password
export PGVECTOR_HOST=localhost
export PGVECTOR_PORT=5432
export EMBEDDING_MODEL=all-MiniLM-L6-v2

echo "🔄 Updating Martin's AI services with RAG support..."
echo "⚠️  This will preserve existing functionality and add vector database features"

# Check current status
echo "Current AI services status:"
ps aux | grep -E "(phi3_ai_service|gemma2_ai_service|tinyllama_ai_service)" | grep -v grep || echo "No services found"

echo ""
echo "Checking current service responses:"
curl -s http://localhost:8005/ | jq -r '.message' 2>/dev/null || echo "Port 8005: Not responding"
curl -s http://localhost:8006/ | jq -r '.service' 2>/dev/null || echo "Port 8006: Not responding"  
curl -s http://localhost:8007/ | jq -r '.service' 2>/dev/null || echo "Port 8007: Not responding"

echo ""
echo "📋 To complete RAG integration, Martin needs to:"
echo "1. Copy the updated files with sudo permissions"
echo "2. Restart services with environment variables"
echo "3. Test RAG functionality"

echo ""
echo "Files ready for update:"
ls -la /opt/sites/admin.middleworldfarms.org/rag_updated_services/
echo ""
echo "Shared RAG service:"
ls -la /opt/sites/admin.middleworldfarms.org/shared_rag_service.py

echo ""
echo "📖 See RAG_INTEGRATION_INSTRUCTIONS.md for detailed steps"
