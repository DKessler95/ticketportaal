"""
Test script for hybrid retrieval system
Tests vector search, BM25 search, graph search, and hybrid combination.
"""

import sys
import os

# Add parent directory to path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from hybrid_retrieval import VectorSearch, BM25Search, GraphSearch, HybridRetrieval, AdvancedReranker


def test_vector_search():
    """Test vector search functionality."""
    print("\n" + "="*60)
    print("TEST 1: Vector Search")
    print("="*60)
    
    try:
        chromadb_path = os.path.join(
            os.path.dirname(os.path.dirname(__file__)),
            'chromadb_data'
        )
        
        vector_search = VectorSearch(chromadb_path)
        
        # Test search
        query = "laptop start niet op"
        results = vector_search.search(query, top_k=3)
        
        print(f"✓ Vector search initialized")
        print(f"  Query: '{query}'")
        print(f"  Results: {len(results)}")
        
        if results:
            print(f"\n  Top result:")
            print(f"    ID: {results[0]['id']}")
            print(f"    Similarity: {results[0]['similarity_score']:.3f}")
            print(f"    Document: {results[0]['document'][:100]}...")
        
        return True
        
    except Exception as e:
        print(f"✗ Vector search failed: {e}")
        return False


def test_bm25_search():
    """Test BM25 search functionality."""
    print("\n" + "="*60)
    print("TEST 2: BM25 Search")
    print("="*60)
    
    try:
        chromadb_path = os.path.join(
            os.path.dirname(os.path.dirname(__file__)),
            'chromadb_data'
        )
        
        bm25_search = BM25Search(chromadb_path)
        
        # Test search
        query = "printer error"
        results = bm25_search.search(query, top_k=3)
        
        print(f"✓ BM25 search initialized")
        print(f"  Query: '{query}'")
        print(f"  Results: {len(results)}")
        
        if results:
            print(f"\n  Top result:")
            print(f"    ID: {results[0]['id']}")
            print(f"    BM25 Score: {results[0]['bm25_score']:.3f}")
            print(f"    Document: {results[0]['document'][:100]}...")
        
        return True
        
    except Exception as e:
        print(f"✗ BM25 search failed: {e}")
        return False


def test_graph_search():
    """Test graph search functionality."""
    print("\n" + "="*60)
    print("TEST 3: Graph Search")
    print("="*60)
    
    try:
        db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'ticketportaal'
        }
        
        graph_search = GraphSearch(db_config)
        
        # Test search
        query = "Dell laptop"
        results = graph_search.search(query, max_hops=2, top_k=3)
        
        print(f"✓ Graph search initialized")
        print(f"  Query: '{query}'")
        print(f"  Results: {len(results)}")
        
        if results:
            print(f"\n  Top result:")
            print(f"    ID: {results[0]['id']}")
            print(f"    Centrality: {results[0]['centrality_score']:.3f}")
            print(f"    Document: {results[0]['document'][:100]}...")
        
        return True
        
    except Exception as e:
        print(f"✗ Graph search failed: {e}")
        return False


def test_hybrid_search():
    """Test hybrid search combining all methods."""
    print("\n" + "="*60)
    print("TEST 4: Hybrid Search")
    print("="*60)
    
    try:
        chromadb_path = os.path.join(
            os.path.dirname(os.path.dirname(__file__)),
            'chromadb_data'
        )
        
        db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'ticketportaal'
        }
        
        hybrid = HybridRetrieval(chromadb_path, db_config)
        
        # Test search
        query = "laptop blue screen error"
        results = hybrid.search(query, top_k=5)
        
        print(f"✓ Hybrid search initialized")
        print(f"  Query: '{query}'")
        print(f"  Results: {len(results)}")
        
        if results:
            print(f"\n  Top 3 results:")
            for i, result in enumerate(results[:3], 1):
                print(f"\n  {i}. {result['id']}")
                print(f"     Combined Score: {result['combined_score']:.3f}")
                print(f"     Scores: {result.get('scores', {})}")
                print(f"     Document: {result['document'][:80]}...")
        
        return True
        
    except Exception as e:
        print(f"✗ Hybrid search failed: {e}")
        return False


def test_reranking():
    """Test advanced reranking."""
    print("\n" + "="*60)
    print("TEST 5: Advanced Reranking")
    print("="*60)
    
    try:
        chromadb_path = os.path.join(
            os.path.dirname(os.path.dirname(__file__)),
            'chromadb_data'
        )
        
        db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'ticketportaal'
        }
        
        hybrid = HybridRetrieval(chromadb_path, db_config)
        reranker = AdvancedReranker()
        
        # Get results
        query = "network connection problem"
        results = hybrid.search(query, top_k=10)
        
        # Rerank
        reranked = reranker.rerank(results, top_n=5)
        
        print(f"✓ Reranking initialized")
        print(f"  Query: '{query}'")
        print(f"  Original results: {len(results)}")
        print(f"  Reranked results: {len(reranked)}")
        
        if reranked:
            print(f"\n  Top 3 reranked results:")
            for i, result in enumerate(reranked[:3], 1):
                print(f"\n  {i}. {result['id']}")
                print(f"     Final Score: {result['final_score']:.3f}")
                rerank_scores = result.get('rerank_scores', {})
                print(f"     Similarity: {rerank_scores.get('similarity', 0):.3f}")
                print(f"     BM25: {rerank_scores.get('bm25', 0):.3f}")
                print(f"     Centrality: {rerank_scores.get('centrality', 0):.3f}")
                print(f"     Recency: {rerank_scores.get('recency', 0):.3f}")
                print(f"     Feedback: {rerank_scores.get('feedback', 0):.3f}")
        
        return True
        
    except Exception as e:
        print(f"✗ Reranking failed: {e}")
        return False


def test_custom_weights():
    """Test custom weight configuration."""
    print("\n" + "="*60)
    print("TEST 6: Custom Weights")
    print("="*60)
    
    try:
        chromadb_path = os.path.join(
            os.path.dirname(os.path.dirname(__file__)),
            'chromadb_data'
        )
        
        db_config = {
            'host': 'localhost',
            'user': 'root',
            'password': '',
            'database': 'ticketportaal'
        }
        
        hybrid = HybridRetrieval(chromadb_path, db_config)
        
        # Set custom weights (prioritize vector search)
        hybrid.set_weights(vector=0.7, bm25=0.2, graph=0.1)
        
        query = "software installation issue"
        results = hybrid.search(query, top_k=3)
        
        print(f"✓ Custom weights set")
        print(f"  Weights: vector=0.7, bm25=0.2, graph=0.1")
        print(f"  Results: {len(results)}")
        
        return True
        
    except Exception as e:
        print(f"✗ Custom weights test failed: {e}")
        return False


def main():
    """Run all tests."""
    print("="*60)
    print("HYBRID RETRIEVAL TEST SUITE")
    print("="*60)
    
    results = []
    
    # Run tests
    results.append(("Vector Search", test_vector_search()))
    results.append(("BM25 Search", test_bm25_search()))
    results.append(("Graph Search", test_graph_search()))
    results.append(("Hybrid Search", test_hybrid_search()))
    results.append(("Advanced Reranking", test_reranking()))
    results.append(("Custom Weights", test_custom_weights()))
    
    # Summary
    print("\n" + "="*60)
    print("TEST SUMMARY")
    print("="*60)
    
    passed = sum(1 for _, result in results if result)
    total = len(results)
    
    for test_name, result in results:
        status = "✓ PASS" if result else "✗ FAIL"
        print(f"{status}: {test_name}")
    
    print("="*60)
    print(f"Results: {passed}/{total} tests passed")
    
    if passed == total:
        print("\n✓ All tests passed! Hybrid retrieval system is working correctly.")
        return 0
    else:
        print(f"\n✗ {total - passed} test(s) failed. Check errors above.")
        return 1


if __name__ == "__main__":
    sys.exit(main())
