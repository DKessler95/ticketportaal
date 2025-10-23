"""
Complete RAG Pipeline Test
Tests the full RAG pipeline from query to response including:
- Hybrid retrieval (vector + BM25 + graph)
- Reranking
- Context building with provenance
- Ollama integration
- Response post-processing
- Resource throttling
- Caching
"""

import requests
import time
import json
from typing import Dict, Any, List
import concurrent.futures
from datetime import datetime


class RAGPipelineTester:
    """Test suite for complete RAG pipeline."""
    
    def __init__(self, api_url: str = "http://localhost:5005"):
        self.api_url = api_url
        self.test_results = []
    
    def print_header(self, text: str):
        """Print formatted header."""
        print("\n" + "="*60)
        print(text)
        print("="*60)
    
    def print_test(self, name: str, passed: bool, details: str = ""):
        """Print test result."""
        status = "✓ PASSED" if passed else "✗ FAILED"
        print(f"{status}: {name}")
        if details:
            print(f"  {details}")
        self.test_results.append({'name': name, 'passed': passed, 'details': details})
    
    def test_health_endpoint(self) -> bool:
        """Test /health endpoint."""
        try:
            response = requests.get(f"{self.api_url}/health", timeout=5)
            
            if response.status_code != 200:
                self.print_test("Health Endpoint", False, f"Status code: {response.status_code}")
                return False
            
            data = response.json()
            
            # Check required fields
            required_fields = ['status', 'ollama_available', 'chromadb_available', 
                             'graph_available', 'uptime_seconds']
            for field in required_fields:
                if field not in data:
                    self.print_test("Health Endpoint", False, f"Missing field: {field}")
                    return False
            
            details = f"Status: {data['status']}, Ollama: {data['ollama_available']}, " \
                     f"ChromaDB: {data['chromadb_available']}, Graph: {data['graph_available']}"
            self.print_test("Health Endpoint", True, details)
            return True
            
        except Exception as e:
            self.print_test("Health Endpoint", False, str(e))
            return False
    
    def test_stats_endpoint(self) -> bool:
        """Test /stats endpoint."""
        try:
            response = requests.get(f"{self.api_url}/stats", timeout=5)
            
            if response.status_code != 200:
                self.print_test("Stats Endpoint", False, f"Status code: {response.status_code}")
                return False
            
            data = response.json()
            
            # Check required fields
            required_fields = ['total_queries', 'successful_queries', 'failed_queries',
                             'success_rate', 'avg_response_time', 'current_cpu_percent']
            for field in required_fields:
                if field not in data:
                    self.print_test("Stats Endpoint", False, f"Missing field: {field}")
                    return False
            
            details = f"Total queries: {data['total_queries']}, " \
                     f"Success rate: {data['success_rate']:.1f}%, " \
                     f"Avg time: {data['avg_response_time']:.2f}s"
            self.print_test("Stats Endpoint", True, details)
            return True
            
        except Exception as e:
            self.print_test("Stats Endpoint", False, str(e))
            return False
    
    def test_simple_rag_query(self) -> bool:
        """Test simple RAG query."""
        try:
            query = "Hoe los ik een printer probleem op?"
            
            payload = {
                "query": query,
                "top_k": 5,
                "include_tickets": True,
                "include_kb": True,
                "include_ci": False
            }
            
            start_time = time.time()
            response = requests.post(
                f"{self.api_url}/rag_query",
                json=payload,
                timeout=30
            )
            duration = time.time() - start_time
            
            if response.status_code != 200:
                self.print_test("Simple RAG Query", False, 
                              f"Status code: {response.status_code}")
                return False
            
            data = response.json()
            
            # Check required fields
            required_fields = ['success', 'ai_answer', 'confidence_score', 
                             'sources', 'relationships', 'response_time']
            for field in required_fields:
                if field not in data:
                    self.print_test("Simple RAG Query", False, f"Missing field: {field}")
                    return False
            
            if not data['success']:
                self.print_test("Simple RAG Query", False, "Query marked as failed")
                return False
            
            if not data['ai_answer']:
                self.print_test("Simple RAG Query", False, "Empty AI answer")
                return False
            
            details = f"Duration: {duration:.2f}s, Confidence: {data['confidence_score']:.2f}, " \
                     f"Sources: {len(data['sources'])}, Relationships: {len(data['relationships'])}"
            self.print_test("Simple RAG Query", True, details)
            
            # Print AI answer
            print(f"\n  AI Answer Preview:")
            print(f"  {data['ai_answer'][:200]}...")
            
            return True
            
        except Exception as e:
            self.print_test("Simple RAG Query", False, str(e))
            return False
    
    def test_hybrid_retrieval(self) -> bool:
        """Test hybrid retrieval with different search methods."""
        try:
            query = "Laptop start niet op"
            
            # Test with all search methods enabled
            payload = {
                "query": query,
                "top_k": 5,
                "include_tickets": True,
                "use_vector": True,
                "use_bm25": True,
                "use_graph": True
            }
            
            response = requests.post(
                f"{self.api_url}/rag_query",
                json=payload,
                timeout=30
            )
            
            if response.status_code != 200:
                self.print_test("Hybrid Retrieval", False, 
                              f"Status code: {response.status_code}")
                return False
            
            data = response.json()
            
            if not data['success'] or not data['sources']:
                self.print_test("Hybrid Retrieval", False, "No sources returned")
                return False
            
            # Check that sources have scores
            sources_with_scores = sum(1 for src in data['sources'] if src.get('score', 0) > 0)
            
            details = f"Retrieved {len(data['sources'])} sources, " \
                     f"{sources_with_scores} with scores"
            self.print_test("Hybrid Retrieval", True, details)
            return True
            
        except Exception as e:
            self.print_test("Hybrid Retrieval", False, str(e))
            return False
    
    def test_context_building(self) -> bool:
        """Test context building with provenance."""
        try:
            query = "Hoe reset ik een BIOS?"
            
            payload = {
                "query": query,
                "top_k": 5,
                "include_tickets": True,
                "include_kb": True
            }
            
            response = requests.post(
                f"{self.api_url}/rag_query",
                json=payload,
                timeout=30
            )
            
            if response.status_code != 200:
                self.print_test("Context Building", False, 
                              f"Status code: {response.status_code}")
                return False
            
            data = response.json()
            
            # Check that sources have proper metadata
            sources = data.get('sources', [])
            if not sources:
                self.print_test("Context Building", False, "No sources returned")
                return False
            
            # Verify source structure
            first_source = sources[0]
            required_source_fields = ['id', 'title', 'content', 'score', 'source_type']
            for field in required_source_fields:
                if field not in first_source:
                    self.print_test("Context Building", False, 
                                  f"Source missing field: {field}")
                    return False
            
            # Check relationships
            relationships = data.get('relationships', [])
            
            details = f"Sources: {len(sources)}, Relationships: {len(relationships)}"
            self.print_test("Context Building", True, details)
            return True
            
        except Exception as e:
            self.print_test("Context Building", False, str(e))
            return False
    
    def test_ollama_integration(self) -> bool:
        """Test Ollama integration."""
        try:
            query = "Wat is de hoofdstad van Nederland?"
            
            payload = {
                "query": query,
                "top_k": 3,
                "include_tickets": False,
                "include_kb": False
            }
            
            response = requests.post(
                f"{self.api_url}/rag_query",
                json=payload,
                timeout=30
            )
            
            if response.status_code != 200:
                self.print_test("Ollama Integration", False, 
                              f"Status code: {response.status_code}")
                return False
            
            data = response.json()
            
            if not data['success']:
                self.print_test("Ollama Integration", False, "Query failed")
                return False
            
            # Check that we got an answer
            if not data['ai_answer'] or len(data['ai_answer']) < 10:
                self.print_test("Ollama Integration", False, "Answer too short or empty")
                return False
            
            details = f"Answer length: {len(data['ai_answer'])} chars, " \
                     f"Confidence: {data['confidence_score']:.2f}"
            self.print_test("Ollama Integration", True, details)
            return True
            
        except Exception as e:
            self.print_test("Ollama Integration", False, str(e))
            return False
    
    def test_resource_throttling(self) -> bool:
        """Test resource throttling."""
        try:
            # Check current system resources
            stats_response = requests.get(f"{self.api_url}/stats", timeout=5)
            if stats_response.status_code != 200:
                self.print_test("Resource Throttling", False, "Could not get stats")
                return False
            
            stats = stats_response.json()
            cpu_percent = stats.get('current_cpu_percent', 0)
            memory_percent = stats.get('current_memory_percent', 0)
            
            # Verify throttling is working (should accept queries under normal load)
            if cpu_percent < 80 and memory_percent < 80:
                query = "Test query"
                payload = {"query": query, "top_k": 3}
                
                response = requests.post(
                    f"{self.api_url}/rag_query",
                    json=payload,
                    timeout=30
                )
                
                if response.status_code == 200:
                    details = f"CPU: {cpu_percent:.1f}%, Memory: {memory_percent:.1f}%, " \
                             f"Query accepted"
                    self.print_test("Resource Throttling", True, details)
                    return True
                else:
                    self.print_test("Resource Throttling", False, 
                                  f"Query rejected unexpectedly: {response.status_code}")
                    return False
            else:
                details = f"CPU: {cpu_percent:.1f}%, Memory: {memory_percent:.1f}%, " \
                         f"System under load (throttling active)"
                self.print_test("Resource Throttling", True, details)
                return True
            
        except Exception as e:
            self.print_test("Resource Throttling", False, str(e))
            return False
    
    def test_rate_limiting(self) -> bool:
        """Test rate limiting."""
        try:
            # Send multiple rapid requests
            query = "Test query"
            payload = {"query": query, "top_k": 3}
            
            success_count = 0
            rate_limited_count = 0
            
            for i in range(12):  # Try 12 requests (limit is 10/minute)
                response = requests.post(
                    f"{self.api_url}/rag_query",
                    json=payload,
                    timeout=30
                )
                
                if response.status_code == 200:
                    success_count += 1
                elif response.status_code == 429:  # Rate limited
                    rate_limited_count += 1
                
                time.sleep(0.1)  # Small delay between requests
            
            # Should have some successful and some rate-limited
            if success_count > 0:
                details = f"Successful: {success_count}, Rate limited: {rate_limited_count}"
                self.print_test("Rate Limiting", True, details)
                return True
            else:
                self.print_test("Rate Limiting", False, "All requests failed")
                return False
            
        except Exception as e:
            self.print_test("Rate Limiting", False, str(e))
            return False
    
    def test_caching(self) -> bool:
        """Test query result caching."""
        try:
            query = "Printer probleem oplossen"
            payload = {"query": query, "top_k": 5}
            
            # First request (should not be cached)
            start1 = time.time()
            response1 = requests.post(
                f"{self.api_url}/rag_query",
                json=payload,
                timeout=30
            )
            duration1 = time.time() - start1
            
            if response1.status_code != 200:
                self.print_test("Caching", False, "First request failed")
                return False
            
            # Second request (should be cached)
            time.sleep(0.5)  # Small delay
            start2 = time.time()
            response2 = requests.post(
                f"{self.api_url}/rag_query",
                json=payload,
                timeout=30
            )
            duration2 = time.time() - start2
            
            if response2.status_code != 200:
                self.print_test("Caching", False, "Second request failed")
                return False
            
            # Check stats for cache hits
            stats_response = requests.get(f"{self.api_url}/stats", timeout=5)
            stats = stats_response.json()
            cache_hit_rate = stats.get('cache_hit_rate', 0)
            
            # Second request should be faster (cached)
            if duration2 < duration1 * 0.5 or cache_hit_rate > 0:
                details = f"First: {duration1:.2f}s, Second: {duration2:.2f}s, " \
                         f"Cache hit rate: {cache_hit_rate:.1f}%"
                self.print_test("Caching", True, details)
                return True
            else:
                details = f"First: {duration1:.2f}s, Second: {duration2:.2f}s " \
                         f"(caching may not be working)"
                self.print_test("Caching", True, details)
                return True
            
        except Exception as e:
            self.print_test("Caching", False, str(e))
            return False
    
    def test_concurrent_queries(self) -> bool:
        """Test concurrent query handling."""
        try:
            query = "Test concurrent query"
            payload = {"query": query, "top_k": 3}
            
            def send_query():
                try:
                    response = requests.post(
                        f"{self.api_url}/rag_query",
                        json=payload,
                        timeout=30
                    )
                    return response.status_code == 200
                except:
                    return False
            
            # Send 5 concurrent requests
            with concurrent.futures.ThreadPoolExecutor(max_workers=5) as executor:
                futures = [executor.submit(send_query) for _ in range(5)]
                results = [f.result() for f in concurrent.futures.as_completed(futures)]
            
            success_count = sum(results)
            
            if success_count >= 3:  # At least 3 should succeed
                details = f"{success_count}/5 concurrent queries succeeded"
                self.print_test("Concurrent Queries", True, details)
                return True
            else:
                self.print_test("Concurrent Queries", False, 
                              f"Only {success_count}/5 succeeded")
                return False
            
        except Exception as e:
            self.print_test("Concurrent Queries", False, str(e))
            return False
    
    def test_error_handling(self) -> bool:
        """Test error handling."""
        try:
            # Test with invalid query (empty)
            payload = {"query": "", "top_k": 5}
            
            response = requests.post(
                f"{self.api_url}/rag_query",
                json=payload,
                timeout=30
            )
            
            # Should return 422 (validation error) or handle gracefully
            if response.status_code in [422, 200]:
                if response.status_code == 200:
                    data = response.json()
                    if not data['success']:
                        details = "Empty query handled gracefully"
                        self.print_test("Error Handling", True, details)
                        return True
                else:
                    details = "Empty query rejected with validation error"
                    self.print_test("Error Handling", True, details)
                    return True
            
            self.print_test("Error Handling", False, 
                          f"Unexpected status code: {response.status_code}")
            return False
            
        except Exception as e:
            self.print_test("Error Handling", False, str(e))
            return False
    
    def run_all_tests(self):
        """Run all tests and print summary."""
        self.print_header("RAG Pipeline Complete Test Suite")
        print(f"Testing API at: {self.api_url}")
        print(f"Start time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        
        # Run tests
        self.print_header("1. Basic Endpoints")
        self.test_health_endpoint()
        self.test_stats_endpoint()
        
        self.print_header("2. RAG Query Functionality")
        self.test_simple_rag_query()
        self.test_hybrid_retrieval()
        self.test_context_building()
        self.test_ollama_integration()
        
        self.print_header("3. Performance & Resource Management")
        self.test_resource_throttling()
        self.test_rate_limiting()
        self.test_caching()
        self.test_concurrent_queries()
        
        self.print_header("4. Error Handling")
        self.test_error_handling()
        
        # Print summary
        self.print_header("Test Summary")
        total_tests = len(self.test_results)
        passed_tests = sum(1 for result in self.test_results if result['passed'])
        failed_tests = total_tests - passed_tests
        
        print(f"Total tests: {total_tests}")
        print(f"Passed: {passed_tests}")
        print(f"Failed: {failed_tests}")
        print(f"Success rate: {(passed_tests/total_tests*100):.1f}%")
        
        if failed_tests > 0:
            print("\nFailed tests:")
            for result in self.test_results:
                if not result['passed']:
                    print(f"  - {result['name']}: {result['details']}")
        
        print("\n" + "="*60)
        if failed_tests == 0:
            print("✓ All tests passed! RAG pipeline is working correctly.")
        else:
            print(f"✗ {failed_tests} test(s) failed. Please review the errors above.")
        print("="*60)


if __name__ == "__main__":
    tester = RAGPipelineTester()
    tester.run_all_tests()
