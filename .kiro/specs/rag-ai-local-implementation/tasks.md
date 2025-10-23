z# Implementation Plan

## Phase 1: Foundation & Infrastructure

- [x] 1. Setup Development Environment







  - Install Python 3.11+ on Windows Server
  - Create virtual environment at C:\TicketportaalAI\venv
  - Install core dependencies (ChromaDB, sentence-transformers, FastAPI, uvicorn)
  - Verify installations with version checks
  - _Requirements: 1.1, 1.2_

- [x] 2. Install and Configure Ollama





  - Download Ollama Windows installer from ollama.com
  - Install Ollama to default location
  - Configure environment variables (OLLAMA_HOST, OLLAMA_ORIGINS, OLLAMA_MODELS)
  - Pull Llama 3.1 8B model (4.7GB download)
  - Test model with simple query
  - _Requirements: 1.1, 1.3_

- [x] 3. Setup Ollama as Windows Service





  - Download NSSM (Non-Sucking Service Manager)
  - Install Ollama as Windows Service with NSSM
  - Configure service startup type as Automatic
  - Configure service recovery options (restart on failure)
  - Test service start/stop/restart
  - _Requirements: 5.1, 5.2_

- [x] 4. Create Directory Structure






  - Create C:\TicketportaalAI\ with subdirectories (scripts, logs, chromadb_data, models, backups)
  - Set appropriate permissions for IIS AppPool
  - Create log rotation configuration
  - _Requirements: 1.1_

## Phase 2: Data Quality & Category Fields

- [x] 5. Audit and Configure Category Dynamic Fields





  - Review all existing ticket categories in database
  - Design dynamic field schema for each category (Hardware, Software, Network, Account, etc.)
  - Create migration script to add missing category_fields entries
  - Define dropdown options for structured fields (brands, models, locations)
  - _Requirements: 2.1, 11.1_

- [x] 5.1 Populate Hardware Category Fields


  - Add fields: Merk, Model, Serienummer, Locatie, Afdeling
  - Create dropdown options for common brands (Dell, HP, Lenovo, etc.)
  - Create dropdown options for locations (Kantoor Hengelo, Kantoor Enschede, etc.)
  - _Requirements: 2.1_


- [x] 5.2 Populate Software Category Fields









  - Add fields: Applicatie naam, Versie, Licentie type, Installatie locatie
  - Create dropdown options for common applications
  - _Requirements: 2.1_


- [ ] 5.3 Populate Network Category Fields
  - Add fields: Switch/Router, Poort nummer, VLAN, IP adres

  - _Requirements: 2.1_

- [ ] 5.4 Populate Account Category Fields
  - Add fields: Username, Email, Afdeling, Toegangsniveau, Systeem
  - Create dropdown options for departments and access levels
  - _Requirements: 2.1_

- [ ]* 5.5 Create Seed Data for Testing
  - Generate 50-100 realistic test tickets with complete dynamic fields
  - Include variety of categories, priorities, and statuses
  - Add realistic comments and resolutions
  - _Requirements: 2.1_

- [x] 6. Create Database Indexes for Performance












  - Add index on tickets(updated_at) for sync queries
  - Add index on knowledge_base(is_published, updated_at)
  - Add index on configuration_items(status, updated_at)
  - Add index on ticket_field_values(ticket_id, field_id)
  - Test query performance improvements
  - _Requirements: 6.3_

## Phase 3: Knowledge Graph Foundation

- [x] 7. Design and Implement Knowledge Graph Schema





  - Create graph_nodes table (node_id, node_type, properties JSON, created_at)
  - Create graph_edges table (edge_id, source_id, target_id, edge_type, confidence, properties JSON)
  - Add indexes on source_id, target_id, edge_type for fast traversal
  - Create Python NetworkX wrapper for graph operations
  - _Requirements: 3.1, 3.2_

- [x] 8. Implement Entity Extraction (NER)





  - Install spaCy and download nl_core_news_lg model
  - Create extract_entities() function for tickets
  - Extract: products, errors, locations, persons, organizations and other variables which are gonna be needed
  - _Requirements: 3.1_







- [x] 9. Implement Relationship Extraction
  - Create extract_relationships() function
  - Build edges: CREATED_BY, AFFECTS, SIMILAR_TO, RESOLVED_BY, BELONGS_TO
  - Calculate confidence scores for each relationship type
  - Handle edge cases (missing data, invalid references)
  - _Requirements: 3.2_

- [x] 9.1 Human-in-the-Loop Validation






  - Create validation UI for reviewing extracted entities/relationships
  - Sample 100 tickets for manual review
  - Calculate precision/recall metrics
  - Adjust confidence thresholds based on results
  - Intergrate UI in admin screen
  - _Requirements: 3.1_

## Phase 4: Advanced Sync Pipeline

- [x] 10. Implement Enhanced Data Sync Script





  - Create sync_tickets_to_vector_db.py with rich data extraction
  - Query tickets WITH dynamic fields (JSON aggregation)
  - Query tickets WITH comments (JSON aggregation)
  - Query tickets WITH related CI items (JSON aggregation)
  - Implement semantic chunking (header, description, comments, resolution)
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 10.1 Implement Embedding Generation


  - Load sentence-transformers model (all-mpnet-base-v2)
  - Generate embeddings for each semantic chunk
  - Batch processing (100 chunks at a time)
  - Progress bar for long-running syncs
  - _Requirements: 2.3, 3.3_

- [x] 10.2 Implement ChromaDB Upsert

  - Create/get ChromaDB collections (tickets, kb, ci)
  - Upsert documents with embeddings and metadata
  - Handle duplicate detection (update if exists)
  - Error handling and retry logic
  - _Requirements: 2.3, 2.4_

- [x] 10.3 Implement Knowledge Graph Population

  - Extract entities from each ticket
  - Extract relationships between entities
  - Upsert nodes to graph_nodes table
  - Upsert edges to graph_edges table with confidence scores
  - _Requirements: 3.1, 3.2_

- [x] 10.4 Implement KB and CI Sync

  - Sync KB articles to kb_collection
  - Sync CI items to ci_collection
  - Extract entities from KB content
  - Build graph edges for KB → Ticket relationships
  - _Requirements: 2.1, 2.3_

- [x] 10.5 Add Logging and Error Handling

  - Structured logging with timestamps and log levels
  - Error handling for database connection failures
  - Error handling for embedding generation failures
  - Email alerts on critical failures
  - _Requirements: 2.5, 7.3_

- [ ]* 10.6 Create Sync Performance Tests
  - Test sync with 100, 500, 1000 tickets
  - Measure duration, CPU, RAM usage
  - Verify all data correctly stored in ChromaDB and graph
  - _Requirements: 6.2, 9.4_

## Phase 5: Hybrid Retrieval Implementation

- [x] 11. Implement Dense Vector Search


  - Create vector_search() function using ChromaDB
  - Query with embedding, return top-k results
  - Include metadata filtering (category, date range)
  - Return similarity scores
  - _Requirements: 3.2, 3.3_


- [x] 12. Implement Sparse Keyword Search (BM25)




  - Install rank-bm25 library
  - Create BM25 index from ticket documents
  - Implement bm25_search() function
  - Return relevance scores
  - _Requirements: 3.2_


- [x] 13. Implement Graph Traversal Search

  - Create graph_search() function using NetworkX
  - Find related entities within N hops (default 2)
  - Calculate graph centrality scores
  - Return subgraph relevant to query

  - _Requirements: 3.2, 3.4_

- [x] 14. Implement Hybrid Search Combiner

  - Create hybrid_search() function
  - Execute vector, BM25, and graph searches in parallel
  - Combine results with weighted scoring

  - Remove duplicates
  - _Requirements: 3.2, 3.5_

- [x] 15. Implement Advanced Reranking


  - Create rerank_results() function
  - Multi-factor scoring: similarity (40%), BM25 (20%), centrality (15%), recency (15%), feedback (10%)
  - Sort by final score
  - Return top-N results
  - _Requirements: 3.5, 6.2_

## Phase 6: RAG API Implementation

- [x] 16. Create FastAPI RAG Service


  - Create rag_api.py with FastAPI app
  - Define request/response models (Pydantic)
  - Implement /health endpoint
  - Implement /stats endpoint
  - _Requirements: 4.1, 4.2, 5.5_


- [x] 17. Implement /rag_query Endpoint

  - Accept query, top_k, include_tickets, include_kb, include_ci parameters
  - Generate query embedding
  - Execute hybrid search (vector + BM25 + graph)
  - Rerank results
  - Build context from top results
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_


- [x] 17.1 Implement Context Building with Provenance

  - Extract relevant passages from top results
  - Include source metadata (ticket_number, title, category)
  - Build relationship chains from graph
  - Add confidence scores
  - _Requirements: 3.5, 8.4_



- [x] 17.2 Implement RAG Prompt Generation


  - Create prompt template with query, context, sources
  - Include relationship chains
  - Add instructions for citing sources
  - Add instructions for flagging uncertainties

  - _Requirements: 3.3, 8.4_





- [ ] 17.3 Implement Ollama Integration
  - Query Ollama API with RAG prompt
  - Handle timeouts and connection errors
  - Parse response

  - Extract answer and metadata


  - _Requirements: 1.3, 3.3, 4.3_

- [ ] 17.4 Implement Response Post-Processing
  - Add source citations to answer
  - Include relationship chains
  - Calculate confidence score
  - Flag uncertainties




  - Return structured response
  - _Requirements: 3.5, 8.4_

- [x] 18. Implement Resource Throttling


  - Check CPU/RAM before accepting query
  - Reject if system load >80%
  - Implement request semaphore (max 5 concurrent)
  - Add rate limiting (10 requests/minute per IP)
  - _Requirements: 6.4, 6.5_

- [x] 19. Implement Query Result Caching

  - LRU cache for frequent queries (maxsize=100)
  - Hash query text for cache key
  - Cache embeddings separately
  - Set TTL to 1 hour
  - _Requirements: 6.2_

- [ ]* 19.1 Create RAG API Unit Tests
  - Test /health endpoint
  - Test /stats endpoint
  - Test /rag_query with various inputs
  - Test error handling (Ollama down, ChromaDB error)
  - _Requirements: 9.1, 9.2_

## Phase 7: FastAPI Service Deployment

- [x] 20. Create FastAPI Startup Script


  - Create start_rag_api.bat
  - Activate venv
  - Run uvicorn with correct host/port
  - _Requirements: 5.1_

- [x] 21. Install FastAPI as Windows Service

  - Use NSSM to install TicketportaalRAG service
  - Configure working directory
  - Configure stdout/stderr logging
  - Set startup type to Automatic
  - Configure recovery options
  - _Requirements: 5.1, 5.2, 5.3_

- [x] 22. Test Service Operations

  - Start service and verify /health endpoint
  - Stop service and verify it stops cleanly
  - Restart service and verify it recovers
  - Test automatic restart on failure
  - _Requirements: 5.2, 5.3_

## Phase 8: Windows Task Scheduler Setup

- [x] 23. Create Daily Sync Scheduled Task


  - Create task: TicketportaalAISync
  - Trigger: Daily at 02:00
  - Action: Run sync_tickets_to_vector_db.py
  - Run as: SYSTEM
  - Configure to run whether user logged in or not
  - _Requirements: 2.2, 5.1_

- [x] 24. Create Health Monitor Script

  - Create health_monitor.ps1
  - Check Ollama and TicketportaalRAG service status
  - Check disk space
  - Send email alerts on failures
  - _Requirements: 7.1, 7.2, 7.3_

- [x] 25. Create Health Monitor Scheduled Task


  - Create task: TicketportaalAIHealthMonitor
  - Trigger: Every 30 minutes
  - Action: Run health_monitor.ps1
  - Run as: SYSTEM
  - _Requirements: 7.2, 7.3_

- [ ]* 25.1 Create Hourly Incremental Sync Task (Optional)
  - Create task: TicketportaalAISyncHourly
  - Trigger: Every hour
  - Action: Run sync with --incremental flag
  - _Requirements: 2.2_

## Phase 9: PHP Integration Layer

- [x] 26. Create AIHelper PHP Class


  - Create includes/ai_helper.php
  - Implement isEnabled() with health check
  - Implement getSuggestions() with cURL to RAG API
  - Implement getStats() for dashboard
  - Add error handling and graceful degradation
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 8.3_

- [x] 27. Create AI Suggestion Widget



  - Create includes/ai_suggestion_widget.php
  - Display AI answer with formatting
  - Display similar tickets with links
  - Display relevant KB articles with links
  - Display relationship chains
  - Display confidence scores and uncertainties
  - _Requirements: 3.5, 4.4, 8.4_

- [x] 28. Integrate AI into Ticket Detail Pages


  - Update agent/ticket_detail.php to include AIHelper
  - Call getSuggestions() with ticket text
  - Include ai_suggestion_widget.php if successful
  - Handle errors gracefully (don't break page)
  - _Requirements: 3.1, 4.4_

- [x] 28.1 Integrate AI into User Ticket Detail

  - Update user/ticket_detail.php similarly
  - Show simplified AI suggestions for end users
  - _Requirements: 3.1, 4.4_

- [x] 29. Create AI Dashboard for Admins


  - Create admin/ai_dashboard.php
  - Display service status (Ollama, RAG API)
  - Display statistics (tickets embedded, KB count, CI count, queries today)
  - Display performance metrics (avg response time, success rate)
  - Display disk/RAM usage
  - Add "Sync Now" button
  - Add "View Logs" button
  - Add "Restart Services" button
  - _Requirements: 7.1, 7.4_

- [x] 30. Implement Feature Flag System


  - Add AI_ENABLED constant to config/config.php
  - Add AI_BETA_USERS array for staged rollout
  - Update AIHelper::isEnabled() to check feature flags
  - _Requirements: 8.1, 8.2, 8.5_

- [ ]* 30.1 Create PHP Integration Tests
  - Test AIHelper::isEnabled()
  - Test AIHelper::getSuggestions() with mock data
  - Test widget rendering
  - _Requirements: 9.3_

## Phase 10: Monitoring and Alerting

- [ ] 31. Implement Structured Logging
  - Configure Python logging with formatters
  - Daily log files with rotation
  - Log levels: ERROR, WARNING, INFO, DEBUG
  - Structured format with timestamps
  - _Requirements: 7.3, 12.4_

- [ ] 32. Implement Email Alerting
  - Configure SMTP settings in config.py
  - Create send_alert() function
  - Alert on service down >5 minutes
  - Alert on sync failed 3 consecutive times
  - Alert on disk space <20GB
  - Alert on error rate >10% in last hour
  - _Requirements: 2.5, 7.2, 7.3_

- [ ] 33. Implement RAG Quality Metrics Collection
  - Track answer faithfulness scores
  - Track retrieval precision
  - Track graph coverage percentage
  - Track extraction quality
  - Track latency (P50, P95, P99)
  - Store metrics in database or log files
  - _Requirements: 7.4, 12.1_

- [ ] 34. Create Metrics Dashboard
  - Add metrics section to admin/ai_dashboard.php
  - Display faithfulness, precision, coverage, latency
  - Display graphs/charts for trends
  - _Requirements: 7.1, 7.4_

## Phase 11: Testing and Validation

- [ ]* 35. VM Testing Environment Setup
  - Create VM with 16GB RAM, 8 CPU cores, 60GB disk
  - Install Windows Server 2022
  - Install XAMPP and deploy ticketportaal
  - Document baseline metrics (CPU, RAM, response times)
  - _Requirements: 9.1, 9.4_

- [ ]* 36. Install AI Components on VM
  - Install Ollama + Llama 3.1
  - Install Python dependencies
  - Measure resource increase
  - _Requirements: 9.1, 9.4_

- [ ]* 37. Run Load Tests on VM
  - Sync 1000 tickets
  - Run 100 concurrent RAG queries
  - Simulate 10 active users
  - Measure performance impact
  - _Requirements: 9.4, 9.5_

- [ ]* 38. Generate Performance Report
  - Compare baseline vs AI-enabled metrics
  - Create charts (CPU, RAM, response times)
  - Document findings and recommendations
  - _Requirements: 9.4, 9.5_

- [ ]* 39. End-to-End Integration Tests
  - Test full RAG pipeline (sync → query → response)
  - Test PHP integration (widget rendering)
  - Test error scenarios (Ollama down, ChromaDB error)
  - Test rollback procedures
  - _Requirements: 9.2, 9.3_

## Phase 12: Production Deployment

- [ ] 40. Pre-Deployment Checklist
  - Verify server resources (16GB+ RAM, 8+ CPU cores, 60GB+ disk)
  - Create full system backup (database + application files)
  - Schedule maintenance window
  - Communicate to users 48 hours in advance
  - _Requirements: 8.1, 8.3_

- [ ] 41. Deploy to Production Server
  - Install Python 3.11+
  - Install Ollama + Llama 3.1
  - Install Python dependencies
  - Create directory structure
  - Deploy Python scripts
  - Deploy PHP integration files
  - _Requirements: 1.1, 1.2, 1.3_

- [ ] 42. Configure Windows Services
  - Install Ollama as service
  - Install TicketportaalRAG as service
  - Start services and verify health
  - _Requirements: 5.1, 5.2_

- [ ] 43. Configure Scheduled Tasks
  - Create daily sync task
  - Create health monitor task
  - Test manual task execution
  - _Requirements: 2.2, 7.2_

- [ ] 44. Run Initial Data Sync
  - Execute sync_tickets_to_vector_db.py manually
  - Monitor progress and logs
  - Verify data in ChromaDB and knowledge graph
  - _Requirements: 2.1, 2.3_

- [ ] 45. Smoke Tests in Production
  - Test /health endpoint
  - Test /stats endpoint
  - Test /rag_query with sample query
  - Test PHP widget rendering
  - _Requirements: 9.1_

- [ ] 46. Staged Rollout - Week 1
  - Enable AI for 2-3 test agents only (AI_BETA_USERS)
  - Monitor usage patterns and errors
  - Collect feedback
  - Fix critical bugs
  - _Requirements: 8.5_

- [ ] 47. Staged Rollout - Week 2-3
  - Enable AI for 50% of agents
  - Monitor performance metrics
  - Compare AI vs non-AI resolution times
  - Adjust based on feedback
  - _Requirements: 8.5_

- [ ] 48. Full Rollout - Week 4
  - Enable AI for all agents (set AI_ENABLED=true)
  - Add to user portal
  - Announce to organization
  - Monitor closely for first week
  - _Requirements: 8.5_

## Phase 13: Documentation and Training

- [ ] 49. Create Admin Documentation
  - Installation guide
  - Operations manual
  - Troubleshooting guide
  - Maintenance schedule
  - _Requirements: 12.1, 12.2, 12.3_

- [ ] 50. Create User Documentation
  - Agent guide: How to use AI suggestions
  - Agent guide: Interpreting AI responses
  - Agent guide: When to trust AI vs manual research
  - End user guide (for future self-service)
  - _Requirements: 12.4_

- [ ] 51. Create Developer Documentation
  - API documentation (endpoints, request/response formats)
  - Integration guide (using AIHelper class)
  - Code documentation (inline comments, docstrings)
  - Architecture diagrams
  - _Requirements: 12.5_

- [ ]* 52. Conduct Agent Training Sessions
  - Train agents on using AI suggestions
  - Train agents on filling dynamic fields correctly
  - Demonstrate data quality impact on AI
  - _Requirements: 2.1, 12.4_

## Phase 14: Continuous Improvement

- [ ] 53. Setup Weekly Review Process
  - Review low-confidence answers
  - Identify missing entities/relationships
  - Update ontology if needed
  - Retrain NER on new examples
  - _Requirements: 11.1_

- [ ] 54. Setup Monthly Analysis
  - Analyze user feedback patterns
  - Identify common failure modes
  - Expand knowledge graph coverage
  - Optimize chunking strategy
  - _Requirements: 11.1_

- [ ] 55. Setup Quarterly Audit
  - Full RAG pipeline audit
  - A/B test reranking weights
  - Evaluate new embedding models
  - Consider graph database upgrade (Neo4j)
  - _Requirements: 11.1, 11.2_

- [ ]* 56. Implement User Feedback Collection
  - Add thumbs up/down buttons to AI suggestions
  - Store feedback in database
  - Use feedback for reranking weights
  - _Requirements: 7.4, 11.1_

## Phase 15: Future Enhancements

- [ ]* 57. Implement Image OCR Processing
  - Install Tesseract OCR or EasyOCR
  - Extract text from ticket screenshots
  - Include extracted text in embeddings
  - _Requirements: 11.3_

- [ ]* 58. Implement Vision Model for Images
  - Install LLaVA or CLIP model locally
  - Generate descriptions for ticket images
  - Include descriptions in embeddings
  - _Requirements: 11.3_

- [ ]* 59. Add Product Catalog Integration
  - Create products_collection in ChromaDB
  - Sync product data from inventory system
  - Enable product recommendations in tickets
  - _Requirements: 11.2_

- [ ]* 60. Add Ecoro Integration
  - Create ecoro_orders_collection
  - Create ecoro_prices_collection
  - Enable pricing/availability queries
  - _Requirements: 11.2_

- [ ]* 61. Add SHD Integration
  - Unified search across Ticketportaal + SHD
  - Cross-system ticket linking
  - Shared knowledge base
  - _Requirements: 11.2_
