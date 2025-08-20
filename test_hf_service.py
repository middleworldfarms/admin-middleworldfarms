#!/usr/bin/env python3
"""
Test Hugging Face AI Service directly
"""

import requests
import json
import time

def test_hf_service():
    print("Testing Hugging Face AI Service...")
    
    # Test data
    test_question = "What is the best timing for succession planting of lettuce in spring?"
    
    # Test the service
    try:
        start_time = time.time()
        
        response = requests.post(
            "http://localhost:8005/ask",
            json={
                "question": test_question,
                "context": "We are in a temperate climate zone"
            },
            timeout=60
        )
        
        end_time = time.time()
        response_time = end_time - start_time
        
        if response.status_code == 200:
            data = response.json()
            print(f"✅ SUCCESS!")
            print(f"⚡ Response time: {response_time:.2f} seconds")
            print(f"🤖 Model: {data.get('model', 'Unknown')}")
            print(f"📝 Answer: {data.get('answer', 'No answer')[:200]}...")
            print(f"🎯 Confidence: {data.get('confidence', 'Unknown')}")
            return True
        else:
            print(f"❌ HTTP Error: {response.status_code}")
            print(f"Response: {response.text}")
            return False
            
    except requests.exceptions.ConnectionError:
        print("❌ Connection failed - is the service running on port 8005?")
        return False
    except requests.exceptions.Timeout:
        print("❌ Request timeout - API took too long to respond")
        return False
    except Exception as e:
        print(f"❌ Test failed: {str(e)}")
        return False

def test_health_endpoint():
    print("\nTesting health endpoint...")
    try:
        response = requests.get("http://localhost:8005/health", timeout=10)
        if response.status_code == 200:
            data = response.json()
            print(f"Health Status: {data.get('status', 'Unknown')}")
            print(f"API Connected: {data.get('api_connected', False)}")
            return data.get('status') == 'healthy'
        else:
            print(f"Health check failed: {response.status_code}")
            return False
    except Exception as e:
        print(f"Health check error: {str(e)}")
        return False

if __name__ == "__main__":
    print("🚀 Starting Hugging Face AI Service Test")
    print("=" * 50)
    
    # Test health first
    health_ok = test_health_endpoint()
    
    # Test main functionality
    if health_ok:
        success = test_hf_service()
        if success:
            print("\n✅ All tests passed! Ready to replace slow Ollama setup.")
        else:
            print("\n❌ Service test failed.")
    else:
        print("\n❌ Health check failed - check API key configuration.")
