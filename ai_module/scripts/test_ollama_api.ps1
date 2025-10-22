# Ollama API Test Script
# Tests various Ollama API endpoints to ensure proper functionality

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Ollama API Test Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "http://localhost:11434"
$testsPassed = 0
$testsFailed = 0

# Test 1: Health Check (List Models)
Write-Host "[Test 1] GET /api/tags - List available models" -ForegroundColor Yellow
try {
    $response = Invoke-RestMethod -Uri "$baseUrl/api/tags" -Method GET -TimeoutSec 5
    Write-Host "✓ Success!" -ForegroundColor Green
    Write-Host "  Available models:" -ForegroundColor Cyan
    foreach ($model in $response.models) {
        $sizeGB = [math]::Round($model.size / 1GB, 2)
        Write-Host "    - $($model.name) ($sizeGB GB)" -ForegroundColor White
    }
    $testsPassed++
} catch {
    Write-Host "✗ Failed: $_" -ForegroundColor Red
    $testsFailed++
}

Write-Host ""

# Test 2: Show Model Info
Write-Host "[Test 2] POST /api/show - Get model information" -ForegroundColor Yellow
try {
    $body = @{
        name = "llama3.1:8b"
    } | ConvertTo-Json

    $response = Invoke-RestMethod -Uri "$baseUrl/api/show" -Method POST -Body $body -ContentType "application/json" -TimeoutSec 5
    Write-Host "✓ Success!" -ForegroundColor Green
    Write-Host "  Model: $($response.modelfile.Split("`n")[0])" -ForegroundColor Cyan
    $testsPassed++
} catch {
    Write-Host "✗ Failed: $_" -ForegroundColor Red
    $testsFailed++
}

Write-Host ""

# Test 3: Generate Response (Non-streaming)
Write-Host "[Test 3] POST /api/generate - Generate response (non-streaming)" -ForegroundColor Yellow
Write-Host "  Query: 'What is 2+2?'" -ForegroundColor Cyan
try {
    $body = @{
        model = "llama3.1:8b"
        prompt = "What is 2+2? Answer in one short sentence."
        stream = $false
        options = @{
            temperature = 0.7
            num_predict = 50
        }
    } | ConvertTo-Json

    $startTime = Get-Date
    $response = Invoke-RestMethod -Uri "$baseUrl/api/generate" -Method POST -Body $body -ContentType "application/json" -TimeoutSec 30
    $endTime = Get-Date
    $duration = ($endTime - $startTime).TotalSeconds

    Write-Host "✓ Success!" -ForegroundColor Green
    Write-Host "  Response: $($response.response)" -ForegroundColor White
    Write-Host "  Duration: $([math]::Round($duration, 2)) seconds" -ForegroundColor Cyan
    Write-Host "  Tokens: $($response.eval_count) tokens" -ForegroundColor Cyan
    $testsPassed++
} catch {
    Write-Host "✗ Failed: $_" -ForegroundColor Red
    $testsFailed++
}

Write-Host ""

# Test 4: Generate Response with Context (RAG-like)
Write-Host "[Test 4] POST /api/generate - Generate with context (RAG simulation)" -ForegroundColor Yellow
try {
    $context = @"
Context: The HP LaserJet printer shows a paper jam error. 
Previous solution: Open the rear door, remove jammed paper, close door, restart printer.

Question: How do I fix a paper jam on an HP printer?
"@

    $body = @{
        model = "llama3.1:8b"
        prompt = $context
        stream = $false
        options = @{
            temperature = 0.5
            num_predict = 100
        }
    } | ConvertTo-Json

    $startTime = Get-Date
    $response = Invoke-RestMethod -Uri "$baseUrl/api/generate" -Method POST -Body $body -ContentType "application/json" -TimeoutSec 30
    $endTime = Get-Date
    $duration = ($endTime - $startTime).TotalSeconds

    Write-Host "✓ Success!" -ForegroundColor Green
    Write-Host "  Response preview:" -ForegroundColor Cyan
    $preview = $response.response.Substring(0, [Math]::Min(200, $response.response.Length))
    Write-Host "  $preview..." -ForegroundColor White
    Write-Host "  Duration: $([math]::Round($duration, 2)) seconds" -ForegroundColor Cyan
    $testsPassed++
} catch {
    Write-Host "✗ Failed: $_" -ForegroundColor Red
    $testsFailed++
}

Write-Host ""

# Test 5: Chat API (Conversational)
Write-Host "[Test 5] POST /api/chat - Chat API test" -ForegroundColor Yellow
try {
    $body = @{
        model = "llama3.1:8b"
        messages = @(
            @{
                role = "system"
                content = "You are a helpful IT support assistant."
            },
            @{
                role = "user"
                content = "My laptop won't turn on. What should I check first?"
            }
        )
        stream = $false
        options = @{
            temperature = 0.7
            num_predict = 100
        }
    } | ConvertTo-Json -Depth 10

    $startTime = Get-Date
    $response = Invoke-RestMethod -Uri "$baseUrl/api/chat" -Method POST -Body $body -ContentType "application/json" -TimeoutSec 30
    $endTime = Get-Date
    $duration = ($endTime - $startTime).TotalSeconds

    Write-Host "✓ Success!" -ForegroundColor Green
    Write-Host "  Response:" -ForegroundColor Cyan
    Write-Host "  $($response.message.content)" -ForegroundColor White
    Write-Host "  Duration: $([math]::Round($duration, 2)) seconds" -ForegroundColor Cyan
    $testsPassed++
} catch {
    Write-Host "✗ Failed: $_" -ForegroundColor Red
    $testsFailed++
}

Write-Host ""

# Test 6: Performance Test (Multiple Queries)
Write-Host "[Test 6] Performance test - 3 rapid queries" -ForegroundColor Yellow
$queries = @(
    "What is a VPN?",
    "How do I reset a password?",
    "What causes slow internet?"
)

$totalDuration = 0
$successCount = 0

foreach ($query in $queries) {
    try {
        $body = @{
            model = "llama3.1:8b"
            prompt = "$query Answer in one sentence."
            stream = $false
            options = @{
                num_predict = 30
            }
        } | ConvertTo-Json

        $startTime = Get-Date
        $response = Invoke-RestMethod -Uri "$baseUrl/api/generate" -Method POST -Body $body -ContentType "application/json" -TimeoutSec 30
        $endTime = Get-Date
        $duration = ($endTime - $startTime).TotalSeconds
        $totalDuration += $duration
        $successCount++
        
        Write-Host "  ✓ Query $successCount completed in $([math]::Round($duration, 2))s" -ForegroundColor Green
    } catch {
        Write-Host "  ✗ Query failed: $_" -ForegroundColor Red
    }
}

if ($successCount -eq $queries.Count) {
    $avgDuration = $totalDuration / $successCount
    Write-Host "✓ All queries successful!" -ForegroundColor Green
    Write-Host "  Average response time: $([math]::Round($avgDuration, 2)) seconds" -ForegroundColor Cyan
    $testsPassed++
} else {
    Write-Host "✗ Some queries failed" -ForegroundColor Red
    $testsFailed++
}

# Summary
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Test Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Passed: $testsPassed" -ForegroundColor Green
Write-Host "Failed: $testsFailed" -ForegroundColor Red
Write-Host ""

if ($testsFailed -eq 0) {
    Write-Host "✓ All tests passed! Ollama API is working correctly." -ForegroundColor Green
    Write-Host ""
    Write-Host "The API is ready for integration with the RAG system." -ForegroundColor Cyan
} else {
    Write-Host "⚠ Some tests failed. Please review the errors above." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Common issues:" -ForegroundColor Cyan
    Write-Host "- Ollama service not running" -ForegroundColor White
    Write-Host "- Model not downloaded (run: ollama pull llama3.1:8b)" -ForegroundColor White
    Write-Host "- Insufficient system resources" -ForegroundColor White
}

Write-Host ""
