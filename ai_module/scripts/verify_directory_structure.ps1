# Verify TicketportaalAI Directory Structure
# This script checks if all required directories exist and are accessible

Write-Host "Verifying TicketportaalAI Directory Structure..." -ForegroundColor Cyan
Write-Host ""

$basePath = "C:\TicketportaalAI"
$requiredDirs = @(
    "scripts",
    "logs",
    "chromadb_data",
    "models",
    "backups",
    "venv"
)

$allGood = $true

# Check base directory
if (Test-Path $basePath) {
    Write-Host "✓ Base directory exists: $basePath" -ForegroundColor Green
} else {
    Write-Host "✗ Base directory missing: $basePath" -ForegroundColor Red
    $allGood = $false
}

Write-Host ""
Write-Host "Checking subdirectories:" -ForegroundColor Cyan

# Check each required subdirectory
foreach ($dir in $requiredDirs) {
    $fullPath = Join-Path $basePath $dir
    
    if (Test-Path $fullPath) {
        # Check if writable
        $testFile = Join-Path $fullPath ".test_write"
        try {
            [System.IO.File]::WriteAllText($testFile, "test")
            Remove-Item $testFile -Force
            Write-Host "  ✓ $dir\ (writable)" -ForegroundColor Green
        }
        catch {
            Write-Host "  ⚠ $dir\ (exists but not writable)" -ForegroundColor Yellow
            $allGood = $false
        }
    }
    else {
        Write-Host "  ✗ $dir\ (missing)" -ForegroundColor Red
        $allGood = $false
    }
}

# Check disk space
Write-Host ""
Write-Host "Disk Space Check:" -ForegroundColor Cyan
$drive = Get-PSDrive C
$freeSpaceGB = [math]::Round($drive.Free / 1GB, 2)
$usedSpaceGB = [math]::Round($drive.Used / 1GB, 2)
$totalSpaceGB = [math]::Round(($drive.Used + $drive.Free) / 1GB, 2)

Write-Host "  Total: $totalSpaceGB GB" -ForegroundColor Gray
Write-Host "  Used: $usedSpaceGB GB" -ForegroundColor Gray
Write-Host "  Free: $freeSpaceGB GB" -ForegroundColor Gray

if ($freeSpaceGB -lt 20) {
    Write-Host "  ⚠ WARNING: Less than 20GB free space!" -ForegroundColor Yellow
    $allGood = $false
}
else {
    Write-Host "  ✓ Sufficient disk space" -ForegroundColor Green
}

# Check for key scripts
Write-Host ""
Write-Host "Checking for key scripts:" -ForegroundColor Cyan
$keyScripts = @(
    "set_permissions.ps1",
    "log_rotation.ps1"
)

foreach ($script in $keyScripts) {
    $scriptPath = Join-Path "$basePath\scripts" $script
    if (Test-Path $scriptPath) {
        Write-Host "  ✓ $script" -ForegroundColor Green
    }
    else {
        Write-Host "  ✗ $script (missing)" -ForegroundColor Red
        $allGood = $false
    }
}

# Summary
Write-Host ""
Write-Host "=" * 60 -ForegroundColor Gray
if ($allGood) {
    Write-Host "✓ Directory structure verification PASSED" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "  1. Run set_permissions.ps1 as Administrator to configure IIS AppPool access" -ForegroundColor Gray
    Write-Host "  2. Install Python dependencies in venv\" -ForegroundColor Gray
    Write-Host "  3. Configure Ollama service" -ForegroundColor Gray
}
else {
    Write-Host "✗ Directory structure verification FAILED" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please fix the issues above before proceeding." -ForegroundColor Yellow
    exit 1
}
