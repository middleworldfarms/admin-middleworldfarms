# Symbiosis AI System - Complete Documentation

## System Overview

Your biodynamic farming AI system consists of three main components:

1. **Laravel Frontend** (Succession Planning Assistant)
2. **FastAPI AI Service** (Python backend on port 8005)
3. **Ollama + Mistral 7B** (Local LLM on port 11434)

## How It Works

```
Frontend (JavaScript) → AI Service (FastAPI) → Ollama (Mistral 7B) → Response
     Port 80/443           Port 8005              Port 11434
```

### 1. Frontend Request Flow
- User types question in succession planning chat
- JavaScript sends POST to `http://localhost:8005/ask`
- **Timeout: 50 seconds** (enough for Mistral's ~42s response time)
- Shows typing indicator while waiting

### 2. AI Service Processing
- FastAPI receives request at `/ask` endpoint
- Calls Ollama API: `http://localhost:11434/api/generate`
- Uses model: `mistral:latest`
- **Timeout: 120 seconds** on Ollama call
- Returns structured response with biodynamic context

### 3. Mistral Response
- Takes approximately **42 seconds** to generate response
- Returns detailed, professional farming advice
- Much longer and more specific than fallback wisdom

## Key Files

### AI Service Main File
```
/opt/sites/admin.middleworldfarms.org/ai_service/main.py
```
- **Port**: 8005 (line ~402: `uvicorn.run(app, host="0.0.0.0", port=8005)`)
- **Model**: mistral:latest
- **Ollama URL**: http://localhost:11434/api/generate

### Frontend JavaScript
```
/opt/sites/admin.middleworldfarms.org/resources/views/admin/farmos/succession-planning.blade.php
```
- **AI URL**: http://localhost:8005/ask (around line 1105)
- **Timeout**: 50000ms (50 seconds) (around line 1118)

### Laravel Config
```
/opt/sites/admin.middleworldfarms.org/.env
```
- `AI_SERVICE_URL=http://localhost:8005/ask`

## How to Identify What's Working

### ✅ Working Mistral Response (Real AI)
- **Length**: Long, detailed paragraphs
- **Tone**: "As a responsible and detail-oriented biodynamic farming expert..."
- **Content**: Specific measurements, temperatures, spacing
- **Time**: Takes ~42 seconds to appear

### ❌ Fallback Response (Not Working)
- **Length**: Short, mystical phrases
- **Content**: "💫 🌱 For your crops in summer: Consider moon phases..."
- **Pattern**: Always mentions Fibonacci spacing: 1, 1, 2, 3, 5, 8 inches
- **Time**: Appears immediately or after timeout

## Troubleshooting Guide

### Problem 1: Getting Fallback Responses
**Symptoms**: Short mystical responses instead of detailed farming advice

**Check in order**:

1. **Is AI Service Running?**
   ```bash
   netstat -tlnp | grep 8005
   ```
   Should show: `tcp 0.0.0.0:8005 LISTEN [PID]/python3`

2. **If not running, start it:**
   ```bash
   cd /opt/sites/admin.middleworldfarms.org/ai_service
   python3 main.py &
   ```

3. **Is Ollama Running?**
   ```bash
   curl http://localhost:11434/api/tags
   ```
   Should show: `"name":"mistral:latest"`

4. **If Ollama not running:**
   ```bash
   ollama serve &
   ```

5. **Test AI Service Directly:**
   ```bash
   curl -X POST http://localhost:8005/ask \
     -H "Content-Type: application/json" \
     -d '{"question": "What is preparation 500?"}' \
     --max-time 60
   ```
   Should return detailed Mistral response in ~42 seconds.

### Problem 2: Frontend Timeout Issues
**Symptoms**: Long wait then fallback response

**Fix timeout in frontend**:
```javascript
// In succession-planning.blade.php around line 1118
signal: AbortSignal.timeout(50000) // 50 second timeout
```

### Problem 3: AI Service Crashes
**Symptoms**: Service was working, now getting connection errors

**Check logs and restart**:
```bash
cd /opt/sites/admin.middleworldfarms.org/ai_service
python3 main.py  # Run in foreground to see errors
```

Common crash causes:
- Missing dependencies: `pip install --break-system-packages requests`
- Port conflict: Kill other processes on 8005
- Syntax errors: Check main.py for corruption

### Problem 4: Wrong Port Configuration
**Symptoms**: Service runs but frontend can't connect

**Verify ports match**:
- AI Service: `port=8005` in main.py (line ~402)
- Frontend: `http://localhost:8005/ask` in blade.php (line ~1105)
- Laravel: `AI_SERVICE_URL=http://localhost:8005/ask` in .env

## Emergency Reset Procedure

If everything breaks and you need to start over:

```bash
# 1. Stop everything
pkill -f "python.*main.py"
pkill -f ollama

# 2. Reset to last working commit
cd /opt/sites/admin.middleworldfarms.org
git stash
git reset --hard HEAD

# 3. Fix the port to 8005
# Edit ai_service/main.py line ~402: port=8005

# 4. Start services
ollama serve &
cd ai_service && python3 main.py &

# 5. Test
curl -X POST http://localhost:8005/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "test"}' --max-time 60
```

## Monitoring Commands

```bash
# Check all services
netstat -tlnp | grep -E "(8005|11434)"

# Monitor AI service logs
cd /opt/sites/admin.middleworldfarms.org/ai_service
python3 main.py  # Shows real-time logs

# Test Ollama
curl http://localhost:11434/api/tags

# Test AI service
curl -X POST http://localhost:8005/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "What should I plant?"}' \
  --max-time 60
```

## Expected Behavior

1. **User asks question** → Typing indicator appears
2. **Wait ~42 seconds** → Mistral thinks
3. **Detailed response appears** → Professional farming advice
4. **No mystical fallback** → Real AI working

The system is working correctly when you get detailed, professional farming responses that take about 42 seconds to generate.

## Quick Start Commands

```bash
# Start everything (run these if system is down)
cd /opt/sites/admin.middleworldfarms.org

# Start Ollama
ollama serve &

# Start AI Service
cd ai_service && python3 main.py &

# Test it works
curl -X POST http://localhost:8005/ask \
  -H "Content-Type: application/json" \
  -d '{"question": "What should I plant this week?"}' \
  --max-time 60
```

## File Locations Summary

| Component | File Path | Key Settings |
|-----------|-----------|--------------|
| AI Service | `/opt/sites/admin.middleworldfarms.org/ai_service/main.py` | Port 8005, Mistral model |
| Frontend | `/opt/sites/admin.middleworldfarms.org/resources/views/admin/farmos/succession-planning.blade.php` | 50s timeout, AI URL |
| Laravel Config | `/opt/sites/admin.middleworldfarms.org/.env` | AI_SERVICE_URL |
| This Documentation | `/opt/sites/admin.middleworldfarms.org/SYMBIOSIS_AI_DOCUMENTATION.md` | This file |

---

**Last Updated**: August 13, 2025  
**System Status**: Operational with Mistral 7B integration  
**Response Time**: ~42 seconds for detailed farming advice
