"""
Quick test script for RAG API
"""
import requests
import json

# Test query
query = "Mijn laptop start niet op, wat kan ik doen?"

print("Testing RAG API...")
print(f"Query: {query}\n")

try:
    response = requests.post(
        "http://localhost:5005/rag_query",
        json={
            "query": query,
            "top_k": 5,
            "search_tickets": True,  # Now enabled - sync works!
            "search_kb": True,
            "search_cis": True
        },
        timeout=30
    )
    
    if response.status_code == 200:
        result = response.json()
        print("✓ Success!")
        print(f"\nAI Answer:\n{result['ai_answer']}\n")
        print(f"Confidence: {result['confidence_score']:.2f}")
        print(f"Response time: {result['response_time']:.2f}s")
        print(f"\nSources: {len(result['sources'])}")
        for i, source in enumerate(result['sources'][:3], 1):
            print(f"  {i}. {source['title']} (score: {source['score']:.3f})")
    else:
        print(f"✗ Error: {response.status_code}")
        print(response.text)
        
except Exception as e:
    print(f"✗ Error: {e}")
