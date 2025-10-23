# Test RAG API Service Operations
# Tests service start, stop, restart, and health checks

param(
    [string]$ServiceName = "TicketportaalRAG",
    [string]$ApiUrl = "http://localhost:5005"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Testing RAG API Service" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Function to test API endpoint
function Test-ApiEndpoint {
    param(
        [string]$Url,
        [string]$EndpointName
    )
    
    try {
        Write-Host "Testing $EndpointName..." -NoNewline
        $Response = Invoke-RestMethod -Uri $Url -Method Get -TimeoutSec 10
        Write-Host " OK" -ForegroundColor Green
        return $true
    } catch {
        Write-Host " FAILED" -ForegroundColor Red
        Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Yellow
        return $false
    }
}

# Function to wait for service
function Wait-ForService {
    param(
        [string]$Name,
        [string]$Status,
        [int]$TimeoutSeconds = 30
    )
    
    $Elapsed = 0
    while ($Elapsed -lt $TimeoutSeconds) {
        $Service = Get-Service -Name $Name -ErrorAction SilentlyContinue
        if ($Service -and $Service.Status -eq $Status) {
            return $true
        }
        Start-Sleep -Seconds 1
        $Elapsed++
    }
    return $false
}

# Check if service exists
Write-Host "`nChecking if service exists..." -NoNewline
$Service = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if (-not $Service) {
    Write-Host " NOT FOUND" -ForegroundColor Red
    Write-Host "Please install the service first using install_rag_service.ps1" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}
Write-Host " OK" -ForegroundColor Green

# Test 1: Stop service
Write-Host "`n[Test 1] Stopping service..." -ForegroundColor Yellow
try {
    Stop-Service -Name $ServiceName -Force -ErrorAction Stop
    if (Wait-ForService -Name $ServiceName -Status "Stopped" -TimeoutSeconds 10) {
        Write-Host "  Service stopped successfully" -ForegroundColor Green
    } else {
        Write-Host "  WARNING: Service did not stop within timeout" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ERROR: Failed to stop service" -ForegroundColor Red
    Write-Host "  $($_.Exception.Message)" -ForegroundColor Yellow
}

Start-Sleep -Seconds 2

# Test 2: Start service
Write-Host "`n[Test 2] Starting service..." -ForegroundColor Yellow
try {
    Start-Service -Name $ServiceName -ErrorAction Stop
    if (Wait-ForService -Name $ServiceName -Status "Running" -TimeoutSeconds 30) {
        Write-Host "  Service started successfully" -ForegroundColor Green
    } else {
        Write-Host "  ERROR: Service did not start within timeout" -ForegroundColor Red
        Read-Host "Press Enter to exit"
        exit 1
    }
} catch {
    Write-Host "  ERROR: Failed to start service" -ForegroundColor Red
    Write-Host "  $($_.Exception.Message)" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

# Wait for API to be ready
Write-Host "`nWaiting for API to be ready..." -NoNewline
Start-Sleep -Seconds 5
Write-Host " Done" -ForegroundColor Green

# Test 3: Health endpoint
Write-Host "`n[Test 3] Testing /health endpoint..." -ForegroundColor Yellow
$HealthUrl = "$ApiUrl/health"
if (Test-ApiEndpoint -Url $HealthUrl -EndpointName "Health Check") {
    try {
        $Health = Invoke-RestMethod -Uri $HealthUrl -Method Get
        Write-Host "  Status: $($Health.status)" -ForegroundColor Cyan
        Write-Host "  Ollama: $($Health.ollama_available)" -ForegroundColor Cyan
        Write-Host "  ChromaDB: $($Health.chromadb_available)" -ForegroundColor Cyan
        Write-Host "  Graph: $($Health.graph_available)" -ForegroundColor Cyan
    } catch {
        Write-Host "  Could not parse health response" -ForegroundColor Yellow
    }
}

# Test 4: Stats endpoint
Write-Host "`n[Test 4] Testing /stats endpoint..." -ForegroundColor Yellow
$StatsUrl = "$ApiUrl/stats"
if (Test-ApiEndpoint -Url $StatsUrl -EndpointName "Stats") {
    try {
        $Stats = Invoke-RestMethod -Uri $StatsUrl -Method Get
        Write-Host "  Total Queries: $($Stats.total_queries)" -ForegroundColor Cyan
        Write-Host "  Success Rate: $([math]::Round($Stats.success_rate, 2))%" -ForegroundColor Cyan
        Write-Host "  Uptime: $([math]::Round($Stats.uptime_seconds, 0))s" -ForegroundColor Cyan
    } catch {
        Write-Host "  Could not parse stats response" -ForegroundColor Yellow
    }
}

# Test 5: Restart service
Write-Host "`n[Test 5] Restarting service..." -ForegroundColor Yellow
try {
    Restart-Service -Name $ServiceName -Force -ErrorAction Stop
    if (Wait-ForService -Name $ServiceName -Status "Running" -TimeoutSeconds 30) {
        Write-Host "  Service restarted successfully" -ForegroundColor Green
    } else {
        Write-Host "  WARNING: Service did not restart within timeout" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ERROR: Failed to restart service" -ForegroundColor Red
    Write-Host "  $($_.Exception.Message)" -ForegroundColor Yellow
}

# Wait for API to be ready after restart
Write-Host "`nWaiting for API to be ready after restart..." -NoNewline
Start-Sleep -Seconds 5
Write-Host " Done" -ForegroundColor Green

# Test 6: Verify service recovered
Write-Host "`n[Test 6] Verifying service recovered..." -ForegroundColor Yellow
if (Test-ApiEndpoint -Url $HealthUrl -EndpointName "Health Check After Restart") {
    Write-Host "  Service recovered successfully" -ForegroundColor Green
} else {
    Write-Host "  WARNING: Service may not have recovered properly" -ForegroundColor Yellow
}

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Test Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

$FinalService = Get-Service -Name $ServiceName
Write-Host "`nService Status: $($FinalService.Status)" -ForegroundColor $(if ($FinalService.Status -eq 'Running') { 'Green' } else { 'Red' })
Write-Host "Service Start Type: $($FinalService.StartType)" -ForegroundColor Cyan

Write-Host "`nAll tests completed!" -ForegroundColor Green
Write-Host "`nAPI Endpoints:" -ForegroundColor Yellow
Write-Host "  Health: $HealthUrl" -ForegroundColor White
Write-Host "  Stats:  $StatsUrl" -ForegroundColor White
Write-Host "  Query:  $ApiUrl/rag_query (POST)" -ForegroundColor White

Read-Host "`nPress Enter to exit"
