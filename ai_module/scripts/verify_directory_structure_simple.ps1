# Verify TicketportaalAI Directory Structure
Write-Host "Verifying TicketportaalAI Directory Structure..." -ForegroundColor Cyan
Write-Host ""

$basePath = "C:\TicketportaalAI"
$requiredDirs = @("scripts", "logs", "chromadb_data", "models", "backups", "venv")
$allGood = $true

# Check base directory
if (Test-Path $basePath) {
    Write-Host "[OK] Base directory exists: $basePath" -ForegroundColor Green
} else {
    Write-Host "[FAIL] Base directory missing: $basePath" -ForegroundColor Red
    $allGood = $false
}

Write-Host ""
Write-Host "Checking subdirectories:" -ForegroundColor Cyan

# Check each required subdirectory
foreach ($dir in $requiredDirs) {
    $fullPath = Join-Path $basePath $dir
    
    if (Test-Path $fullPath) {
        Write-Host "  [OK] $dir (exists)" -ForegroundColor Green
    } else {
        Write-Host "  [FAIL] $dir (missing)" -ForegroundColor Red
        $allGood = $false
    }
}

# Check disk space
Write-Host ""
Write-Host "Disk Space Check:" -ForegroundColor Cyan
$drive = Get-PSDrive C
$freeSpaceGB = [math]::Round($drive.Free / 1GB, 2)

Write-Host "  Free: $freeSpaceGB GB" -ForegroundColor Gray

if ($freeSpaceGB -lt 20) {
    Write-Host "  [WARNING] Less than 20GB free space!" -ForegroundColor Yellow
} else {
    Write-Host "  [OK] Sufficient disk space" -ForegroundColor Green
}

# Summary
Write-Host ""
Write-Host "============================================================" -ForegroundColor Gray
if ($allGood) {
    Write-Host "[SUCCESS] Directory structure verification PASSED" -ForegroundColor Green
} else {
    Write-Host "[FAILED] Directory structure verification FAILED" -ForegroundColor Red
}
