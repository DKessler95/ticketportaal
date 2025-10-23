# Setup Windows Scheduled Tasks for AI Module
# Creates tasks for daily sync, hourly incremental sync, and health monitoring
# Run this script as Administrator

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Setting up Scheduled Tasks" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Check if running as Administrator
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "ERROR: This script must be run as Administrator" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Get paths
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$AIModuleDir = Split-Path -Parent $ScriptDir
$VenvPython = Join-Path $AIModuleDir "venv\Scripts\python.exe"
$SyncScript = Join-Path $ScriptDir "sync_tickets_to_vector_db.py"
$HealthMonitorScript = Join-Path $ScriptDir "health_monitor.ps1"

Write-Host "`nConfiguration:" -ForegroundColor Yellow
Write-Host "  Python: $VenvPython"
Write-Host "  Sync Script: $SyncScript"
Write-Host "  Working Dir: $ScriptDir"

# Verify paths
if (-not (Test-Path $VenvPython)) {
    Write-Host "`nERROR: Python not found at: $VenvPython" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

if (-not (Test-Path $SyncScript)) {
    Write-Host "`nERROR: Sync script not found at: $SyncScript" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

# Task 1: Daily Full Sync
Write-Host "`n[Task 1] Creating Daily Full Sync task..." -ForegroundColor Yellow

$TaskName1 = "TicketportaalAISync"
$Description1 = "Daily full sync of tickets, KB articles, and CI items to vector database"

# Remove existing task if it exists
$ExistingTask1 = Get-ScheduledTask -TaskName $TaskName1 -ErrorAction SilentlyContinue
if ($ExistingTask1) {
    Write-Host "  Removing existing task..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $TaskName1 -Confirm:$false
}

# Create action
$Action1 = New-ScheduledTaskAction `
    -Execute $VenvPython `
    -Argument "`"$SyncScript`" --since-hours 24" `
    -WorkingDirectory $ScriptDir

# Create trigger (daily at 2:00 AM)
$Trigger1 = New-ScheduledTaskTrigger -Daily -At 2:00AM

# Create settings
$Settings1 = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Hours 2)

# Register task
Register-ScheduledTask `
    -TaskName $TaskName1 `
    -Description $Description1 `
    -Action $Action1 `
    -Trigger $Trigger1 `
    -Settings $Settings1 `
    -User "SYSTEM" `
    -RunLevel Highest | Out-Null

Write-Host "  Task created: $TaskName1" -ForegroundColor Green
Write-Host "    Schedule: Daily at 2:00 AM" -ForegroundColor Cyan
Write-Host "    Command: python sync_tickets_to_vector_db.py --since-hours 24" -ForegroundColor Cyan

# Task 2: Hourly Incremental Sync
Write-Host "`n[Task 2] Creating Hourly Incremental Sync task..." -ForegroundColor Yellow

$TaskName2 = "TicketportaalAISyncHourly"
$Description2 = "Hourly incremental sync of recent tickets to vector database"

# Remove existing task if it exists
$ExistingTask2 = Get-ScheduledTask -TaskName $TaskName2 -ErrorAction SilentlyContinue
if ($ExistingTask2) {
    Write-Host "  Removing existing task..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $TaskName2 -Confirm:$false
}

# Create action
$Action2 = New-ScheduledTaskAction `
    -Execute $VenvPython `
    -Argument "`"$SyncScript`" --incremental" `
    -WorkingDirectory $ScriptDir

# Create trigger (every hour)
$Trigger2 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours 1) -RepetitionDuration ([TimeSpan]::MaxValue)

# Create settings
$Settings2 = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 30)

# Register task
Register-ScheduledTask `
    -TaskName $TaskName2 `
    -Description $Description2 `
    -Action $Action2 `
    -Trigger $Trigger2 `
    -Settings $Settings2 `
    -User "SYSTEM" `
    -RunLevel Highest | Out-Null

Write-Host "  Task created: $TaskName2" -ForegroundColor Green
Write-Host "    Schedule: Every hour" -ForegroundColor Cyan
Write-Host "    Command: python sync_tickets_to_vector_db.py --incremental" -ForegroundColor Cyan

# Task 3: Health Monitor (every 30 minutes)
Write-Host "`n[Task 3] Creating Health Monitor task..." -ForegroundColor Yellow

$TaskName3 = "TicketportaalAIHealthMonitor"
$Description3 = "Monitor AI services health and send alerts on failures"

# Check if health monitor script exists
if (Test-Path $HealthMonitorScript) {
    # Remove existing task if it exists
    $ExistingTask3 = Get-ScheduledTask -TaskName $TaskName3 -ErrorAction SilentlyContinue
    if ($ExistingTask3) {
        Write-Host "  Removing existing task..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $TaskName3 -Confirm:$false
    }

    # Create action
    $Action3 = New-ScheduledTaskAction `
        -Execute "powershell.exe" `
        -Argument "-ExecutionPolicy Bypass -File `"$HealthMonitorScript`"" `
        -WorkingDirectory $ScriptDir

    # Create trigger (every 30 minutes)
    $Trigger3 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 30) -RepetitionDuration ([TimeSpan]::MaxValue)

    # Create settings
    $Settings3 = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -ExecutionTimeLimit (New-TimeSpan -Minutes 5)

    # Register task
    Register-ScheduledTask `
        -TaskName $TaskName3 `
        -Description $Description3 `
        -Action $Action3 `
        -Trigger $Trigger3 `
        -Settings $Settings3 `
        -User "SYSTEM" `
        -RunLevel Highest | Out-Null

    Write-Host "  Task created: $TaskName3" -ForegroundColor Green
    Write-Host "    Schedule: Every 30 minutes" -ForegroundColor Cyan
    Write-Host "    Command: powershell health_monitor.ps1" -ForegroundColor Cyan
} else {
    Write-Host "  Skipped: health_monitor.ps1 not found" -ForegroundColor Yellow
}

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Setup Complete" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

Write-Host "`nCreated Scheduled Tasks:" -ForegroundColor Green
Write-Host "  1. $TaskName1 - Daily at 2:00 AM" -ForegroundColor White
Write-Host "  2. $TaskName2 - Every hour" -ForegroundColor White
if (Test-Path $HealthMonitorScript) {
    Write-Host "  3. $TaskName3 - Every 30 minutes" -ForegroundColor White
}

Write-Host "`nTask Management Commands:" -ForegroundColor Yellow
Write-Host "  View tasks:    Get-ScheduledTask | Where-Object {`$_.TaskName -like 'Ticketportaal*'}" -ForegroundColor White
Write-Host "  Run task now:  Start-ScheduledTask -TaskName '$TaskName1'" -ForegroundColor White
Write-Host "  Disable task:  Disable-ScheduledTask -TaskName '$TaskName1'" -ForegroundColor White
Write-Host "  Enable task:   Enable-ScheduledTask -TaskName '$TaskName1'" -ForegroundColor White
Write-Host "  Remove task:   Unregister-ScheduledTask -TaskName '$TaskName1' -Confirm:`$false" -ForegroundColor White

Write-Host "`nNext Steps:" -ForegroundColor Yellow
Write-Host "  1. Verify tasks in Task Scheduler (taskschd.msc)" -ForegroundColor White
Write-Host "  2. Test tasks manually: Start-ScheduledTask -TaskName '$TaskName1'" -ForegroundColor White
Write-Host "  3. Check task history in Task Scheduler" -ForegroundColor White

Read-Host "`nPress Enter to exit"
