# Health Monitor for AI Services
# Checks Ollama, RAG API, and system resources
# Sends alerts on critical failures

param(
    [string]$OllamaUrl = "http://localhost:11434",
    [string]$RagApiUrl = "http://localhost:5005",
    [string]$AlertEmail = "",  # Set email for alerts
    [int]$CpuThreshold = 90,
    [int]$MemoryThreshold = 90,
    [int]$DiskThreshold = 20  # GB free
)

# Configuration
$LogDir = Join-Path (Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)) "logs"
$LogFile = Join-Path $LogDir "health_monitor_$(Get-Date -Format 'yyyy-MM-dd').log"

# Ensure log directory exists
if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir | Out-Null
}

# Logging function
function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $LogMessage = "[$Timestamp] [$Level] $Message"
    Add-Content -Path $LogFile -Value $LogMessage
    
    # Also write to console with color
    $Color = switch ($Level) {
        "ERROR" { "Red" }
        "WARNING" { "Yellow" }
        "SUCCESS" { "Green" }
        default { "White" }
    }
    Write-Host $LogMessage -ForegroundColor $Color
}

# Alert function
function Send-Alert {
    param([string]$Subject, [string]$Body)
    
    Write-Log "ALERT: $Subject" "ERROR"
    
    # If email is configured, send email
    if ($AlertEmail) {
        try {
            # Note: Configure SMTP settings as needed
            # Send-MailMessage -To $AlertEmail -Subject $Subject -Body $Body -SmtpServer "smtp.example.com"
            Write-Log "Alert email would be sent to: $AlertEmail" "INFO"
        } catch {
            Write-Log "Failed to send alert email: $($_.Exception.Message)" "ERROR"
        }
    }
}

Write-Log "========================================" "INFO"
Write-Log "Starting Health Monitor Check" "INFO"
Write-Log "========================================" "INFO"

$Alerts = @()

# Check 1: Ollama Service
Write-Log "Checking Ollama service..." "INFO"
try {
    $Response = Invoke-RestMethod -Uri "$OllamaUrl/api/tags" -Method Get -TimeoutSec 5
    Write-Log "Ollama is running" "SUCCESS"
} catch {
    $Message = "Ollama service is not responding"
    Write-Log $Message "ERROR"
    $Alerts += @{
        Subject = "[CRITICAL] Ticketportaal AI - Ollama Down"
        Body = "$Message`n`nError: $($_.Exception.Message)`n`nAction: Check Ollama service status"
    }
}

# Check 2: RAG API Service
Write-Log "Checking RAG API service..." "INFO"
try {
    $Response = Invoke-RestMethod -Uri "$RagApiUrl/health" -Method Get -TimeoutSec 5
    Write-Log "RAG API is running (Status: $($Response.status))" "SUCCESS"
    
    # Check component health
    if (-not $Response.ollama_available) {
        Write-Log "RAG API reports Ollama unavailable" "WARNING"
    }
    if (-not $Response.chromadb_available) {
        $Message = "RAG API reports ChromaDB unavailable"
        Write-Log $Message "ERROR"
        $Alerts += @{
            Subject = "[CRITICAL] Ticketportaal AI - ChromaDB Unavailable"
            Body = "$Message`n`nAction: Check ChromaDB data directory and permissions"
        }
    }
    if (-not $Response.graph_available) {
        Write-Log "RAG API reports Knowledge Graph unavailable" "WARNING"
    }
} catch {
    $Message = "RAG API service is not responding"
    Write-Log $Message "ERROR"
    $Alerts += @{
        Subject = "[CRITICAL] Ticketportaal AI - RAG API Down"
        Body = "$Message`n`nError: $($_.Exception.Message)`n`nAction: Check TicketportaalRAG service status"
    }
}

# Check 3: System Resources
Write-Log "Checking system resources..." "INFO"

# CPU
$CpuUsage = (Get-Counter '\Processor(_Total)\% Processor Time').CounterSamples.CookedValue
if ($CpuUsage -gt $CpuThreshold) {
    $Message = "CPU usage is high: $([math]::Round($CpuUsage, 1))%"
    Write-Log $Message "WARNING"
    $Alerts += @{
        Subject = "[WARNING] Ticketportaal AI - High CPU Usage"
        Body = "$Message (Threshold: $CpuThreshold%)`n`nAction: Monitor system performance"
    }
} else {
    Write-Log "CPU usage: $([math]::Round($CpuUsage, 1))%" "SUCCESS"
}

# Memory
$Memory = Get-CimInstance Win32_OperatingSystem
$MemoryUsagePercent = [math]::Round((($Memory.TotalVisibleMemorySize - $Memory.FreePhysicalMemory) / $Memory.TotalVisibleMemorySize) * 100, 1)
if ($MemoryUsagePercent -gt $MemoryThreshold) {
    $Message = "Memory usage is high: $MemoryUsagePercent%"
    Write-Log $Message "WARNING"
    $Alerts += @{
        Subject = "[WARNING] Ticketportaal AI - High Memory Usage"
        Body = "$Message (Threshold: $MemoryThreshold%)`n`nAction: Monitor memory usage and consider increasing RAM"
    }
} else {
    Write-Log "Memory usage: $MemoryUsagePercent%" "SUCCESS"
}

# Disk Space
$Disk = Get-PSDrive C
$FreeSpaceGB = [math]::Round($Disk.Free / 1GB, 2)
if ($FreeSpaceGB -lt $DiskThreshold) {
    $Message = "Low disk space: ${FreeSpaceGB}GB free"
    Write-Log $Message "ERROR"
    $Alerts += @{
        Subject = "[CRITICAL] Ticketportaal AI - Low Disk Space"
        Body = "$Message (Threshold: ${DiskThreshold}GB)`n`nAction: Clean up old logs or expand disk"
    }
} else {
    Write-Log "Disk space: ${FreeSpaceGB}GB free" "SUCCESS"
}

# Check 4: Recent Sync Status
Write-Log "Checking recent sync status..." "INFO"
$SyncLogPattern = Join-Path $LogDir "sync_*.log"
$RecentSyncLogs = Get-ChildItem -Path $SyncLogPattern -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1

if ($RecentSyncLogs) {
    $LastSyncTime = $RecentSyncLogs.LastWriteTime
    $HoursSinceSync = ((Get-Date) - $LastSyncTime).TotalHours
    
    if ($HoursSinceSync -gt 25) {  # More than 25 hours (daily sync should run at 2 AM)
        $Message = "No sync in last 25 hours (last sync: $LastSyncTime)"
        Write-Log $Message "WARNING"
        $Alerts += @{
            Subject = "[WARNING] Ticketportaal AI - Sync Overdue"
            Body = "$Message`n`nAction: Check scheduled task 'TicketportaalAISync'"
        }
    } else {
        Write-Log "Last sync: $LastSyncTime ($([math]::Round($HoursSinceSync, 1)) hours ago)" "SUCCESS"
    }
    
    # Check for errors in recent sync
    $SyncErrors = Select-String -Path $RecentSyncLogs.FullName -Pattern "\[ERROR\]" -ErrorAction SilentlyContinue
    if ($SyncErrors) {
        $ErrorCount = ($SyncErrors | Measure-Object).Count
        Write-Log "Found $ErrorCount errors in recent sync log" "WARNING"
    }
} else {
    Write-Log "No sync logs found" "WARNING"
}

# Send alerts
if ($Alerts.Count -gt 0) {
    Write-Log "========================================" "INFO"
    Write-Log "ALERTS DETECTED: $($Alerts.Count)" "ERROR"
    Write-Log "========================================" "INFO"
    
    foreach ($Alert in $Alerts) {
        Send-Alert -Subject $Alert.Subject -Body $Alert.Body
    }
} else {
    Write-Log "========================================" "INFO"
    Write-Log "All checks passed - System healthy" "SUCCESS"
    Write-Log "========================================" "INFO"
}

Write-Log "Health monitor check completed" "INFO"
