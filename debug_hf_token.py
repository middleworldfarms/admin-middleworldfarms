#!/usr/bin/env python3
"""
Simple test to debug Hugging Face API access
"""

import requests
import os
from dotenv import load_dotenv

load_dotenv()

# Test different token formats and endpoints
def test_hf_access():
    token = os.getenv('HUGGINGFACE_API_KEY')
    print(f"Testing token: {token[:10]}...")
    
    # Test 1: WHO AM I
    print("\n1. Testing whoami endpoint:")
    headers = {"Authorization": f"Bearer {token}"}
    response = requests.get("https://huggingface.co/api/whoami", headers=headers)
    print(f"Status: {response.status_code}")
    print(f"Response: {response.text}")
    
    # Test 2: Try a simple model
    print("\n2. Testing simple model endpoint:")
    try:
        response = requests.post(
            "https://api-inference.huggingface.co/models/gpt2",
            headers=headers,
            json={"inputs": "The answer is"},
            timeout=30
        )
        print(f"Status: {response.status_code}")
        print(f"Response: {response.text}")
    except Exception as e:
        print(f"Error: {e}")
    
    # Test 3: List models
    print("\n3. Testing models list:")
    try:
        response = requests.get("https://huggingface.co/api/models?limit=1", headers=headers)
        print(f"Status: {response.status_code}")
        print(f"Response length: {len(response.text) if response.text else 0}")
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    test_hf_access()
