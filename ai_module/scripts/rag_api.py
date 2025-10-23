"""
RAG API Service
FastAPI service that combines hybrid retrieval with Ollama LLM for intelligent ticket assistance.

This service provides:
- /health endpoint for service monitoring
- /stats endpoint for system statistics
- /rag_query endpoint for AI-powered ticket assistance
- Context building with provenance tracking
- Ollama integration for answer generation
"""

import os
import sys
from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import List, Dict, Optional, Any, Tuple
import logging
from datetime import datetime
import requests
import json
import time
import psutil
import asyncio
from functools import lru_cache
import hashlib
from collections import defaultdict

# Add parent directory to path
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from hybrid_retrieval import HybridRetrieval, AdvancedReranker
from qdrant_vector_search import QdrantVectorSearch

# Configure logging
log_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'logs')
os.makedirs(log_dir, exist_ok=True)

log_file = os.path.join(log_dir, f"rag_api_{datetime.now().strftime('%Y-%m-%d')}.log")
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] [%(levelname)s] [RAG_API] %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S',
    handlers=[
        logging.FileHandler(log_file),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

# Initialize FastAPI app
app = FastAPI(
    title="K&K Ticketportaal RAG API",
    description="AI-powered ticket assistance using RAG (Retrieval-Augmented Generation)",
    version="1.0.0"
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, specify exact origins
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Global configuration
CONFIG = {
    # Vector Database Configuration
    'vector_db': 'qdrant',  # 'qdrant' or 'chromadb'
    'qdrant_host': 'localhost',
    'qdrant_port': 6333,
    'chromadb_path': os.path.join(os.path.dirname(os.path.dirname(__file__)), 'chromadb_data'),
    
    # MySQL Database Configuration
    'db_config': {
        'host': 'localhost',
        'user': 'root',
        'password': '',
        'database': 'ticketportaal'
    },
    
    # Ollama Configuration
    'ollama_url': 'http://localhost:11434',
    'ollama_model': 'llama3.1:8b',
    'ollama_timeout': 30,
    'max_context_length': 4000,
    'default_top_k': 10
}

# Global instances (initialized on startup)
hybrid_retrieval = None
reranker = None
service_stats = {
    'start_time': None,
    'total_queries': 0,
    'successful_queries': 0,
    'failed_queries': 0,
    'avg_response_time': 0.0,
    'ollama_available': False,
    'throttled_queries': 0,
    'cached_queries': 0
}

# Resource throttling
query_semaphore = asyncio.Semaphore(5)  # Max 5 concurrent queries
rate_limiter = defaultdict(list)  # IP -> list of timestamps

# Query cache (LRU cache for embeddings and results)
embedding_cache = {}
result_cache = {}
CACHE_TTL = 3600  # 1 hour in seconds
MAX_CACHE_SIZE = 100


# Pydantic models
class QueryRequest(BaseModel):
    """Request model for RAG query."""
    query: str = Field(..., description="User query text", min_length=1, max_length=500)
    top_k: int = Field(10, description="Number of results to retrieve", ge=1, le=50)
    include_tickets: bool = Field(True, description="Include ticket results")
    include_kb: bool = Field(True, description="Include KB article results")
    include_ci: bool = Field(False, description="Include CI item results")
    use_vector: bool = Field(True, description="Enable vector search")
    use_bm25: bool = Field(True, description="Enable BM25 search")
    use_graph: bool = Field(True, description="Enable graph search")


class SourceDocument(BaseModel):
    """Source document with metadata."""
    id: str
    title: str
    content: str
    category: Optional[str] = None
    ticket_number: Optional[str] = None
    score: float
    source_type: str  # 'ticket', 'kb_article', 'ci_item'


class RelationshipChain(BaseModel):
    """Relationship chain from knowledge graph."""
    source: str
    target: str
    relationship: str
    confidence: float


class QueryResponse(BaseModel):
    """Response model for RAG query."""
    success: bool
    ai_answer: str
    confidence_score: float
    sources: List[SourceDocument]
    relationships: List[RelationshipChain]
    uncertainties: List[str]
    response_time: float
    timestamp: str


class HealthResponse(BaseModel):
    """Health check response."""
    status: str
    ollama_available: bool
    chromadb_available: bool
    graph_available: bool
    uptime_seconds: float
    timestamp: str


class StatsResponse(BaseModel):
    """Statistics response."""
    total_queries: int
    successful_queries: int
    failed_queries: int
    throttled_queries: int
    cached_queries: int
    success_rate: float
    cache_hit_rate: float
    avg_response_time: float
    uptime_seconds: float
    ollama_available: bool
    current_cpu_percent: float
    current_memory_percent: float


# Startup event
@app.on_event("startup")
async def startup_event():
    """Initialize services on startup."""
    global hybrid_retrieval, reranker, service_stats
    
    logger.info("="*60)
    logger.info("Starting RAG API Service")
    logger.info("="*60)
    
    service_stats['start_time'] = datetime.now()
    
    try:
        # Initialize hybrid retrieval with Qdrant
        logger.info("Initializing hybrid retrieval system...")
        hybrid_retrieval = HybridRetrieval(
            qdrant_host=CONFIG['qdrant_host'],
            qdrant_port=CONFIG['qdrant_port'],
            db_config=CONFIG['db_config']
        )
        logger.info("✓ Hybrid retrieval initialized")
        
        # Initialize reranker
        logger.info("Initializing reranker...")
        reranker = AdvancedReranker()
        logger.info("✓ Reranker initialized")
        
        # Check Ollama availability
        logger.info("Checking Ollama availability...")
        service_stats['ollama_available'] = check_ollama_health()
        if service_stats['ollama_available']:
            logger.info("✓ Ollama is available")
        else:
            logger.warning("⚠ Ollama is not available")
        
        logger.info("="*60)
        logger.info("RAG API Service started successfully")
        logger.info(f"Listening on http://0.0.0.0:5005")
        logger.info("="*60)
        
    except Exception as e:
        logger.error(f"Error during startup: {e}")
        raise


# Health check endpoint
@app.get("/health", response_model=HealthResponse)
async def health_check():
    """
    Health check endpoint.
    
    Returns service health status and component availability.
    """
    try:
        # Check Ollama
        ollama_available = check_ollama_health()
        
        # Check ChromaDB
        chromadb_available = check_chromadb_health()
        
        # Check knowledge graph
        graph_available = check_graph_health()
        
        # Calculate uptime
        uptime = (datetime.now() - service_stats['start_time']).total_seconds()
        
        # Determine overall status
        if ollama_available and chromadb_available and graph_available:
            status = "healthy"
        elif chromadb_available:
            status = "degraded"
        else:
            status = "unhealthy"
        
        return HealthResponse(
            status=status,
            ollama_available=ollama_available,
            chromadb_available=chromadb_available,
            graph_available=graph_available,
            uptime_seconds=uptime,
            timestamp=datetime.now().isoformat()
        )
        
    except Exception as e:
        logger.error(f"Health check failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


# Statistics endpoint
@app.get("/stats", response_model=StatsResponse)
async def get_stats():
    """
    Get service statistics.
    
    Returns query statistics and performance metrics.
    """
    try:
        uptime = (datetime.now() - service_stats['start_time']).total_seconds()
        
        total = service_stats['total_queries']
        success_rate = (service_stats['successful_queries'] / total * 100) if total > 0 else 0.0
        cache_hit_rate = (service_stats['cached_queries'] / total * 100) if total > 0 else 0.0
        
        # Get current resource usage
        cpu_percent = psutil.cpu_percent(interval=0.1)
        memory_percent = psutil.virtual_memory().percent
        
        return StatsResponse(
            total_queries=service_stats['total_queries'],
            successful_queries=service_stats['successful_queries'],
            failed_queries=service_stats['failed_queries'],
            throttled_queries=service_stats['throttled_queries'],
            cached_queries=service_stats['cached_queries'],
            success_rate=success_rate,
            cache_hit_rate=cache_hit_rate,
            avg_response_time=service_stats['avg_response_time'],
            uptime_seconds=uptime,
            ollama_available=service_stats['ollama_available'],
            current_cpu_percent=cpu_percent,
            current_memory_percent=memory_percent
        )
        
    except Exception as e:
        logger.error(f"Stats retrieval failed: {e}")
        raise HTTPException(status_code=500, detail=str(e))


def check_ollama_health() -> bool:
    """Check if Ollama service is available."""
    try:
        response = requests.get(
            f"{CONFIG['ollama_url']}/api/tags",
            timeout=5
        )
        return response.status_code == 200
    except:
        return False


def check_chromadb_health() -> bool:
    """Check if Qdrant is accessible."""
    try:
        if hybrid_retrieval is None:
            return False
        # Try to get collections from Qdrant
        collections = hybrid_retrieval.vector_search.qdrant_client.get_collections()
        return True
    except:
        return False


def check_graph_health() -> bool:
    """Check if knowledge graph is accessible."""
    try:
        if hybrid_retrieval is None:
            return False
        stats = hybrid_retrieval.graph_search.knowledge_graph.get_stats()
        return stats['total_nodes'] > 0
    except:
        return False



# Main RAG query endpoint
@app.post("/rag_query", response_model=QueryResponse)
async def rag_query(request: QueryRequest, http_request: Request):
    """
    RAG query endpoint.
    
    Performs hybrid retrieval, builds context with provenance,
    generates answer using Ollama, and returns structured response.
    """
    start_time = time.time()
    service_stats['total_queries'] += 1
    
    # Get client IP
    client_ip = http_request.client.host
    
    logger.info(f"Received query from {client_ip}: '{request.query[:100]}...'")
    logger.info(f"Parameters: top_k={request.top_k}, tickets={request.include_tickets}, "
                f"kb={request.include_kb}, ci={request.include_ci}")
    
    # Check rate limiting
    if not check_rate_limit(client_ip):
        service_stats['throttled_queries'] += 1
        logger.warning(f"Rate limit exceeded for {client_ip}")
        raise HTTPException(
            status_code=429,
            detail="Rate limit exceeded. Maximum 10 requests per minute."
        )
    
    # Check system resources
    if not check_system_resources():
        service_stats['throttled_queries'] += 1
        logger.warning("System resources exceeded threshold")
        raise HTTPException(
            status_code=503,
            detail="System under heavy load. Please try again later."
        )
    
    # Check query cache
    cache_key = generate_cache_key(request)
    cached_response = get_cached_response(cache_key)
    if cached_response:
        service_stats['cached_queries'] += 1
        logger.info("Returning cached response")
        cached_response.response_time = time.time() - start_time
        return cached_response
    
    # Use semaphore to limit concurrent queries
    async with query_semaphore:
        try:
            # Step 1: Execute hybrid search
            logger.info("Step 1: Executing hybrid search...")
            search_results = execute_hybrid_search(request)
            logger.info(f"Retrieved {len(search_results)} results")
        
            # Step 2: Rerank results
            logger.info("Step 2: Reranking results...")
            reranked_results = reranker.rerank(search_results, top_n=request.top_k)
            logger.info(f"Reranked to top {len(reranked_results)} results")
            
            # Step 3: Build context with provenance
            logger.info("Step 3: Building context with provenance...")
            context, sources, relationships = build_context_with_provenance(
                reranked_results,
                request.query
            )
            logger.info(f"Built context with {len(sources)} sources and {len(relationships)} relationships")
            
            # Step 4: Generate RAG prompt
            logger.info("Step 4: Generating RAG prompt...")
            prompt = generate_rag_prompt(request.query, context, sources, relationships)
            logger.info(f"Generated prompt ({len(prompt)} chars)")
            
            # Step 5: Query Ollama
            logger.info("Step 5: Querying Ollama...")
            ollama_response = query_ollama(prompt)
            logger.info("Received Ollama response")
            
            # Step 6: Post-process response
            logger.info("Step 6: Post-processing response...")
            ai_answer, confidence, uncertainties = post_process_response(
                ollama_response,
                sources,
                relationships
            )
            logger.info(f"Post-processing complete (confidence: {confidence:.2f})")
            
            # Calculate response time
            response_time = time.time() - start_time
            
            # Update statistics
            service_stats['successful_queries'] += 1
            update_avg_response_time(response_time)
            
            logger.info(f"Query completed successfully in {response_time:.2f}s")
            
            response = QueryResponse(
                success=True,
                ai_answer=ai_answer,
                confidence_score=confidence,
                sources=sources,
                relationships=relationships,
                uncertainties=uncertainties,
                response_time=response_time,
                timestamp=datetime.now().isoformat()
            )
            
            # Cache the response
            cache_response(cache_key, response)
            
            return response
        
        except Exception as e:
            service_stats['failed_queries'] += 1
            logger.error(f"Query failed: {e}")
            
            response_time = time.time() - start_time
            
            return QueryResponse(
                success=False,
                ai_answer=f"Er is een fout opgetreden bij het verwerken van uw vraag: {str(e)}",
                confidence_score=0.0,
                sources=[],
                relationships=[],
                uncertainties=["Query processing failed"],
                response_time=response_time,
                timestamp=datetime.now().isoformat()
            )


def execute_hybrid_search(request: QueryRequest) -> List[Dict[str, Any]]:
    """
    Execute hybrid search based on request parameters.
    
    Args:
        request: Query request with search parameters
    
    Returns:
        List of search results
    """
    # Determine which collections to search
    collections_to_search = []
    if request.include_tickets:
        collections_to_search.append('tickets')
    if request.include_kb:
        collections_to_search.append('knowledge_base')
    if request.include_ci:
        collections_to_search.append('configuration_items')
    
    if not collections_to_search:
        collections_to_search = ['tickets']  # Default to tickets
    
    # Execute hybrid search
    all_results = []
    
    for collection in collections_to_search:
        try:
            results = hybrid_retrieval.search(
                request.query,
                top_k=request.top_k * 2,  # Get more results for reranking
                collection_name=collection,
                use_vector=request.use_vector,
                use_bm25=request.use_bm25,
                use_graph=request.use_graph
            )
            all_results.extend(results)
        except Exception as e:
            logger.warning(f"Search failed for collection {collection}: {e}")
    
    return all_results


def build_context_with_provenance(results: List[Dict[str, Any]], 
                                 query: str) -> Tuple[str, List[SourceDocument], List[RelationshipChain]]:
    """
    Build context from search results with provenance tracking.
    
    Args:
        results: Reranked search results
        query: Original query
    
    Returns:
        Tuple of (context_text, source_documents, relationship_chains)
    """
    context_parts = []
    sources = []
    relationships = []
    
    # Extract relevant passages from top results
    for i, result in enumerate(results[:10], 1):  # Limit to top 10
        metadata = result.get('metadata', {})
        document = result.get('document', '')
        
        # Extract source information
        source_type = result.get('collection', 'unknown')
        ticket_number = metadata.get('ticket_number', '')
        title = metadata.get('title', 'Untitled')
        category = metadata.get('category', '')
        
        # Build context entry
        context_entry = f"[Bron {i}] "
        if ticket_number:
            context_entry += f"Ticket {ticket_number}: "
        context_entry += f"{title}\n"
        
        # Add relevant content (limit length)
        content = document[:500] if len(document) > 500 else document
        context_entry += f"{content}\n"
        
        context_parts.append(context_entry)
        
        # Create source document
        source = SourceDocument(
            id=result.get('id', ''),
            title=title,
            content=document,
            category=category,
            ticket_number=ticket_number,
            score=result.get('final_score', result.get('combined_score', 0.0)),
            source_type=source_type
        )
        sources.append(source)
    
    # Extract relationship chains from graph
    try:
        # Get relationships for top tickets
        for result in results[:5]:
            if result.get('collection') == 'tickets':
                ticket_id = result.get('metadata', {}).get('ticket_id')
                if ticket_id:
                    ticket_node_id = f"ticket_{ticket_id}"
                    
                    # Get neighbors
                    neighbors = hybrid_retrieval.graph_search.knowledge_graph.get_neighbors(
                        ticket_node_id,
                        direction='both'
                    )
                    
                    # Build relationship chains
                    for neighbor in neighbors[:3]:  # Limit to 3 neighbors
                        # Get edge data
                        if hybrid_retrieval.graph_search.knowledge_graph.graph.has_edge(ticket_node_id, neighbor):
                            edge_data = hybrid_retrieval.graph_search.knowledge_graph.graph[ticket_node_id][neighbor]
                            relationship = RelationshipChain(
                                source=ticket_node_id,
                                target=neighbor,
                                relationship=edge_data.get('edge_type', 'RELATED_TO'),
                                confidence=edge_data.get('confidence', 0.5)
                            )
                            relationships.append(relationship)
    except Exception as e:
        logger.warning(f"Error extracting relationships: {e}")
    
    # Combine context parts
    context_text = "\n\n".join(context_parts)
    
    # Limit context length
    if len(context_text) > CONFIG['max_context_length']:
        context_text = context_text[:CONFIG['max_context_length']] + "..."
    
    return context_text, sources, relationships


def generate_rag_prompt(query: str, context: str, 
                       sources: List[SourceDocument],
                       relationships: List[RelationshipChain]) -> str:
    """
    Generate RAG prompt for Ollama.
    
    Args:
        query: User query
        context: Context from retrieved documents
        sources: Source documents
        relationships: Relationship chains
    
    Returns:
        Formatted prompt string
    """
    prompt = f"""Je bent een AI-assistent voor het K&K Ticketportaal. Je helpt medewerkers met het oplossen van IT-problemen door relevante informatie uit eerdere tickets en kennisbank artikelen te gebruiken.

VRAAG VAN GEBRUIKER:
{query}

RELEVANTE INFORMATIE UIT TICKETPORTAAL:
{context}

RELATIES IN KENNISBANK:
"""
    
    # Add relationship information
    if relationships:
        for rel in relationships[:5]:  # Limit to 5
            prompt += f"- {rel.source} {rel.relationship} {rel.target} (vertrouwen: {rel.confidence:.2f})\n"
    else:
        prompt += "Geen directe relaties gevonden.\n"
    
    prompt += """

INSTRUCTIES:
1. Beantwoord de vraag op basis van de gegeven informatie
2. Verwijs naar specifieke bronnen (bijv. "Volgens Ticket T-2024-001...")
3. Als je onzeker bent, geef dit duidelijk aan
4. Als de informatie niet voldoende is, zeg dit eerlijk
5. Geef praktische, uitvoerbare adviezen
6. Gebruik Nederlandse taal
7. Wees beknopt maar compleet

ANTWOORD:
"""
    
    return prompt


def query_ollama(prompt: str) -> str:
    """
    Query Ollama API with RAG prompt.
    
    Args:
        prompt: Formatted RAG prompt
    
    Returns:
        Generated answer from Ollama
    """
    try:
        url = f"{CONFIG['ollama_url']}/api/generate"
        
        payload = {
            'model': CONFIG['ollama_model'],
            'prompt': prompt,
            'stream': False,
            'options': {
                'temperature': 0.7,
                'top_p': 0.9,
                'top_k': 40
            }
        }
        
        response = requests.post(
            url,
            json=payload,
            timeout=CONFIG['ollama_timeout']
        )
        
        if response.status_code == 200:
            result = response.json()
            return result.get('response', '')
        else:
            logger.error(f"Ollama returned status {response.status_code}")
            raise Exception(f"Ollama API error: {response.status_code}")
            
    except requests.Timeout:
        logger.error("Ollama request timed out")
        raise Exception("Ollama request timed out")
    except Exception as e:
        logger.error(f"Error querying Ollama: {e}")
        raise


def post_process_response(ollama_response: str,
                         sources: List[SourceDocument],
                         relationships: List[RelationshipChain]) -> Tuple[str, float, List[str]]:
    """
    Post-process Ollama response.
    
    Args:
        ollama_response: Raw response from Ollama
        sources: Source documents
        relationships: Relationship chains
    
    Returns:
        Tuple of (processed_answer, confidence_score, uncertainties)
    """
    answer = ollama_response.strip()
    uncertainties = []
    
    # Detect uncertainty phrases
    uncertainty_phrases = [
        'ik weet niet',
        'niet zeker',
        'mogelijk',
        'misschien',
        'waarschijnlijk',
        'onvoldoende informatie',
        'niet genoeg informatie'
    ]
    
    answer_lower = answer.lower()
    for phrase in uncertainty_phrases:
        if phrase in answer_lower:
            uncertainties.append(f"Onzekerheid gedetecteerd: '{phrase}'")
    
    # Calculate confidence score
    confidence = calculate_confidence_score(answer, sources, uncertainties)
    
    # Add source citations if not already present
    if sources and not any(src.ticket_number in answer for src in sources if src.ticket_number):
        answer += "\n\n**Bronnen:**\n"
        for i, source in enumerate(sources[:5], 1):
            if source.ticket_number:
                answer += f"{i}. Ticket {source.ticket_number}: {source.title}\n"
            else:
                answer += f"{i}. {source.title}\n"
    
    return answer, confidence, uncertainties


def calculate_confidence_score(answer: str, 
                              sources: List[SourceDocument],
                              uncertainties: List[str]) -> float:
    """
    Calculate confidence score for the answer.
    
    Args:
        answer: Generated answer
        sources: Source documents
        uncertainties: Detected uncertainties
    
    Returns:
        Confidence score (0.0-1.0)
    """
    score = 0.8  # Base score
    
    # Reduce score for uncertainties
    score -= len(uncertainties) * 0.1
    
    # Reduce score for short answers
    if len(answer) < 100:
        score -= 0.1
    
    # Increase score if sources are cited
    if sources and any(src.ticket_number in answer for src in sources if src.ticket_number):
        score += 0.1
    
    # Increase score based on source quality
    if sources:
        avg_source_score = sum(src.score for src in sources[:3]) / min(3, len(sources))
        score += avg_source_score * 0.1
    
    # Clamp to 0.0-1.0
    return max(0.0, min(1.0, score))


def update_avg_response_time(response_time: float) -> None:
    """Update average response time statistic."""
    total = service_stats['successful_queries']
    if total == 1:
        service_stats['avg_response_time'] = response_time
    else:
        current_avg = service_stats['avg_response_time']
        service_stats['avg_response_time'] = (current_avg * (total - 1) + response_time) / total


# Resource throttling functions
def check_system_resources() -> bool:
    """
    Check if system has sufficient resources to accept query.
    
    Returns:
        True if resources are available, False otherwise
    """
    try:
        # Check CPU usage
        cpu_percent = psutil.cpu_percent(interval=0.1)
        if cpu_percent > 80:
            logger.warning(f"CPU usage too high: {cpu_percent}%")
            return False
        
        # Check RAM usage
        memory = psutil.virtual_memory()
        if memory.percent > 80:
            logger.warning(f"Memory usage too high: {memory.percent}%")
            return False
        
        return True
        
    except Exception as e:
        logger.error(f"Error checking system resources: {e}")
        return True  # Allow query if check fails


def check_rate_limit(client_ip: str, max_requests: int = 10, window_seconds: int = 60) -> bool:
    """
    Check if client has exceeded rate limit.
    
    Args:
        client_ip: Client IP address
        max_requests: Maximum requests allowed in window
        window_seconds: Time window in seconds
    
    Returns:
        True if within rate limit, False otherwise
    """
    current_time = time.time()
    
    # Clean old timestamps
    rate_limiter[client_ip] = [
        ts for ts in rate_limiter[client_ip]
        if current_time - ts < window_seconds
    ]
    
    # Check if limit exceeded
    if len(rate_limiter[client_ip]) >= max_requests:
        return False
    
    # Add current timestamp
    rate_limiter[client_ip].append(current_time)
    return True


# Caching functions
def generate_cache_key(request: QueryRequest) -> str:
    """
    Generate cache key from request.
    
    Args:
        request: Query request
    
    Returns:
        Cache key string
    """
    # Create hash from query and parameters
    cache_data = f"{request.query}_{request.top_k}_{request.include_tickets}_{request.include_kb}_{request.include_ci}"
    return hashlib.md5(cache_data.encode()).hexdigest()


def get_cached_response(cache_key: str) -> Optional[QueryResponse]:
    """
    Get cached response if available and not expired.
    
    Args:
        cache_key: Cache key
    
    Returns:
        Cached response or None
    """
    if cache_key in result_cache:
        cached_data = result_cache[cache_key]
        
        # Check if expired
        if time.time() - cached_data['timestamp'] < CACHE_TTL:
            logger.info(f"Cache hit for key: {cache_key}")
            return cached_data['response']
        else:
            # Remove expired entry
            del result_cache[cache_key]
            logger.info(f"Cache expired for key: {cache_key}")
    
    return None


def cache_response(cache_key: str, response: QueryResponse) -> None:
    """
    Cache response with timestamp.
    
    Args:
        cache_key: Cache key
        response: Response to cache
    """
    # Limit cache size (LRU-like behavior)
    if len(result_cache) >= MAX_CACHE_SIZE:
        # Remove oldest entry
        oldest_key = min(result_cache.keys(), key=lambda k: result_cache[k]['timestamp'])
        del result_cache[oldest_key]
        logger.info(f"Cache full, removed oldest entry: {oldest_key}")
    
    result_cache[cache_key] = {
        'response': response,
        'timestamp': time.time()
    }
    logger.info(f"Cached response for key: {cache_key}")


@lru_cache(maxsize=100)
def get_cached_embedding(text: str) -> Optional[list]:
    """
    Get cached embedding for text.
    
    Args:
        text: Text to get embedding for
    
    Returns:
        Cached embedding or None
    """
    if text in embedding_cache:
        cached_data = embedding_cache[text]
        
        # Check if expired
        if time.time() - cached_data['timestamp'] < CACHE_TTL:
            return cached_data['embedding']
        else:
            del embedding_cache[text]
    
    return None


def cache_embedding(text: str, embedding: list) -> None:
    """
    Cache embedding with timestamp.
    
    Args:
        text: Text
        embedding: Embedding vector
    """
    if len(embedding_cache) >= MAX_CACHE_SIZE:
        # Remove oldest entry
        oldest_key = min(embedding_cache.keys(), key=lambda k: embedding_cache[k]['timestamp'])
        del embedding_cache[oldest_key]
    
    embedding_cache[text] = {
        'embedding': embedding,
        'timestamp': time.time()
    }


# Run server
if __name__ == "__main__":
    import uvicorn
    
    logger.info("Starting RAG API server...")
    uvicorn.run(
        app,
        host="0.0.0.0",
        port=5005,
        log_level="info"
    )
