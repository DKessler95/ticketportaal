# Log Rotation Configuration for TicketportaalAI
# This script manages log file rotation to prevent disk space issues

param(
    [int]$MaxLogAgeDays = 30,
    [int]$MaxLogSizeMB = 100,
    [string]$LogPath = "C:\TicketportaalAI\logs"
)

Write-Host "Starting log rotation for $LogPath..." -ForegroundColor Cyan
Write-Host "Configuration:" -ForegroundColor Gray
Write-Host "  - Max log age: $MaxLogAgeDays days" -ForegroundColor Gray
Write-Host "  - Max log size: $MaxLogSizeMB MB" -ForegroundColor Gray

# Ensure log directory exists
if (-not (Test-Path $LogPath)) {
    Write-Host "✗ Log directory not found: $LogPath" -ForegroundColor Red
    exit 1
}

# Get all log files
$logFiles = Get-ChildItem -Path $LogPath -Filter "*.log" -File

$deletedCount = 0
$archivedCount = 0
$totalFreedMB = 0

foreach ($file in $logFiles) {
    $fileAgeDays = (Get-Date) - $file.LastWriteTime
    $fileSizeMB = [math]::Round($file.Length / 1MB, 2)
    
    # Delete logs older than MaxLogAgeDays
    if ($fileAgeDays.Days -gt $MaxLogAgeDays) {
        Write-Host "  Deleting old log: $($file.Name) (Age: $($fileAgeDays.Days) days)" -ForegroundColor Yellow
        Remove-Item $file.FullName -Force
        $deletedCount++
        $totalFreedMB += $fileSizeMB
        continue
    }
    
    # Archive logs larger than MaxLogSizeMB
    if ($fileSizeMB -gt $MaxLogSizeMB) {
        $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
        $archiveName = "$($file.BaseName)_$timestamp.zip"
        $archivePath = Join-Path $LogPath $archiveName
        
        Write-Host "  Archiving large log: $($file.Name) ($fileSizeMB MB)" -ForegroundColor Yellow
        
        try {
            Compress-Archive -Path $file.FullName -DestinationPath $archivePath -Force
            Remove-Item $file.FullName -Force
            $archivedCount++
            Write-Host "    ✓ Archived to: $archiveName" -ForegroundColor Green
        } catch {
            Write-Host "    ✗ Failed to archive: $_" -ForegroundColor Red
        }
    }
}

# Summary
Write-Host "`nLog Rotation Summary:" -ForegroundColor Cyan
Write-Host "  - Deleted: $deletedCount old log files" -ForegroundColor Gray
Write-Host "  - Archived: $archivedCount large log files" -ForegroundColor Gray
Write-Host "  - Space freed: $totalFreedMB MB" -ForegroundColor Gray

# Check remaining disk space
$drive = (Get-Item $LogPath).PSDrive
$freeSpaceGB = [math]::Round($drive.Free / 1GB, 2)
Write-Host "  - Free disk space: $freeSpaceGB GB" -ForegroundColor Gray

if ($freeSpaceGB -lt 20) {
    Write-Host "`n⚠ WARNING: Low disk space! Less than 20GB remaining." -ForegroundColor Red
    Write-Host "  Consider increasing log rotation frequency or reducing retention period." -ForegroundColor Yellow
}

Write-Host "`nLog rotation complete!" -ForegroundColor Green
