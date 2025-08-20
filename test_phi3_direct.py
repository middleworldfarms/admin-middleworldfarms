#!/usr/bin/env python3
"""
Direct test of the Phi-3 AI service timeout fix
Tests the actual AI service endpoint that Laravel calls
"""

import requests
import time
import json

def test_phi3_direct():
    print("🧪 Direct Phi-3 AI Service Test")
    print("===============================\n")
    
    # Phi-3 service endpoint
    phi3_url = "http://localhost:8005/ask"
    
    # Build the same prompt that Laravel would send
    prompt = """You are an expert agricultural consultant with deep knowledge of crop varieties, seasonal timing, and succession planting.

🌱 AUTHORITATIVE VARIETY DATA (UK Seed Companies & RHS):
Brussels Sprout F1 Doric (Thompson & Morgan/Suttons):
- VARIETY TYPE: Winter hardy hybrid
- SOW: February-April (protected) or May-June (direct)
- HARVEST: November through February (WINTER CROP)
- MATURITY: 28-32 weeks from sowing
- HARDINESS: Extremely cold hardy, bred for winter harvest
- YIELD PERIOD: 3-4 months continuous picking
- CLASSIFICATION: Late season winter variety
- RHS AWARD: AGM (Award of Garden Merit) for reliability

FARM CONTEXT (Middle World Farms, Lincoln, UK):
- Location: Lincoln, Lincolnshire, UK (53.2307°N, 0.5406°W)
- Climate Zone: Temperate oceanic (UK Zone 8-9)
- Current Date: 2024-12-19 (Season: Winter)
- Soil Type: Lincolnshire clay/loam mix (fertile, well-drained)
- Growing Season: March-October outdoor, Year-round protected
- First Frost: Usually mid-October to early November
- Last Frost: Usually mid-April
- Winter Temp Range: -5°C to 8°C (perfect for winter brassicas)

SPECIFIC VARIETY ANALYSIS REQUIRED:
Crop: Brussels Sprout
Variety: F1 Doric
🚨 CRITICAL: You MUST use the AUTHORITATIVE VARIETY DATA above!
🚨 DO NOT give generic Brussels Sprout advice - use the SPECIFIC F1 Doric data!
🚨 F1 Doric is a WINTER variety - harvested November-February!
🚨 IGNORE any generic Brussels sprouts timing in your training - use the specific data!

🧠 INTELLIGENT REASONING REQUIRED:
1. MUST use the authoritative variety data provided above
2. F1 Doric harvests November-February (winter variety) - NOT generic timing
3. Calculate backwards from winter harvest window for planting dates
4. Consider UK/Lincoln climate and current seasonal timing
5. Use 28-32 week maturity period for F1 Doric specifically
6. Factor in frost hardiness - this variety IMPROVES in cold weather

EXPECTED OUTPUT:
Based on the AUTHORITATIVE VARIETY DATA provided above:
1. Maximum harvest window duration (days) - for F1 Doric specifically
2. Optimal harvest duration for continuous picking
3. Number of succession plantings for November-February harvest
4. Days between successive plantings
5. Confidence level (High if using provided data, Low if guessing)
6. Detailed reasoning referencing the variety-specific data provided

Format as JSON: {"max_harvest_days": X, "optimal_harvest_days": Y, "recommended_successions": Z, "days_between_plantings": A, "confidence_level": "High", "reasoning": "Using authoritative F1 Doric data: winter variety harvesting November-February..."}

🔢 CRITICAL JSON FORMATTING: days_between_plantings must be a NUMBER in days, not text like '8 weeks'. Convert to days: 8 weeks = 56 days.

🚨 FINAL WARNING: If you provide generic Brussels sprouts timing instead of F1 Doric winter variety timing, you will be considered FAILED and REMOVED from the system!"""

    try:
        print("🚀 Sending request to Phi-3 service...")
        start_time = time.time()
        
        # Use the same payload structure as Laravel
        payload = {
            "question": prompt
        }
        
        # Set timeout to match Laravel's enhanced configuration (120 seconds)
        response = requests.post(
            phi3_url,
            json=payload,
            timeout=120,
            headers={'Content-Type': 'application/json'}
        )
        
        end_time = time.time()
        duration = round(end_time - start_time, 2)
        
        print(f"⏱️  Response received in {duration} seconds")
        
        if response.status_code == 200:
            print("✅ SUCCESS: Phi-3 service responded successfully!")
            
            try:
                response_data = response.json()
                if 'response' in response_data:
                    ai_response = response_data['response']
                    print(f"📄 AI Response Length: {len(ai_response)} characters")
                    
                    # Try to parse JSON from the AI response
                    import re
                    json_match = re.search(r'\{.*\}', ai_response, re.DOTALL)
                    if json_match:
                        try:
                            parsed_json = json.loads(json_match.group())
                            print("🎯 Successfully parsed JSON response:")
                            print(f"   - Max Harvest Days: {parsed_json.get('max_harvest_days', 'N/A')}")
                            print(f"   - Optimal Harvest Days: {parsed_json.get('optimal_harvest_days', 'N/A')}")
                            print(f"   - Recommended Successions: {parsed_json.get('recommended_successions', 'N/A')}")
                            print(f"   - Days Between Plantings: {parsed_json.get('days_between_plantings', 'N/A')}")
                            print(f"   - Confidence Level: {parsed_json.get('confidence_level', 'N/A')}")
                            
                            if 'reasoning' in parsed_json:
                                reasoning = parsed_json['reasoning'][:200] + "..." if len(parsed_json['reasoning']) > 200 else parsed_json['reasoning']
                                print(f"🧠 AI Reasoning: {reasoning}")
                                
                        except json.JSONDecodeError as e:
                            print(f"⚠️  JSON parsing error: {e}")
                            print(f"📄 Raw JSON attempt: {json_match.group()[:500]}...")
                    else:
                        print("⚠️  No JSON structure found in AI response")
                        print(f"📄 Raw response preview: {ai_response[:500]}...")
                        
                else:
                    print("❌ No 'response' field in Phi-3 service response")
                    print(f"📄 Response structure: {response_data}")
                    
            except json.JSONDecodeError as e:
                print(f"❌ Failed to parse Phi-3 service response as JSON: {e}")
                print(f"📄 Raw response: {response.text[:500]}...")
                
        else:
            print(f"❌ FAILED: HTTP {response.status_code}")
            print(f"📄 Error response: {response.text}")
            
        # Performance evaluation
        if duration < 60:
            print(f"🚀 Excellent performance: {duration}s (< 60s)")
        elif duration < 120:
            print(f"✅ Good performance: {duration}s (< 120s)")
        else:
            print(f"⚠️  Slow performance: {duration}s (> 120s)")
            
    except requests.exceptions.Timeout:
        print("❌ TIMEOUT: Request exceeded 120 seconds")
    except requests.exceptions.ConnectionError:
        print("❌ CONNECTION ERROR: Cannot connect to Phi-3 service")
        print("🔍 Check if Phi-3 is running: curl http://localhost:8005/health")
    except Exception as e:
        print(f"❌ UNEXPECTED ERROR: {e}")

def check_phi3_health():
    """Check if Phi-3 service is running"""
    try:
        response = requests.get("http://localhost:8005/health", timeout=5)
        if response.status_code == 200:
            print("✅ Phi-3 service is running")
            return True
        else:
            print(f"⚠️  Phi-3 service responded with status {response.status_code}")
            return False
    except:
        print("❌ Phi-3 service is not responding")
        return False

if __name__ == "__main__":
    print("🔍 Checking Phi-3 service status...")
    if check_phi3_health():
        print()
        test_phi3_direct()
    else:
        print("\n🛠️  Start Phi-3 service first:")
        print("   cd /opt/sites/admin.middleworldfarms.org")
        print("   python phi3_ai_service.py")
