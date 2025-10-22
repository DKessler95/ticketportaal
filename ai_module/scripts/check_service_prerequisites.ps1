# Check Prerequisites for Ollama Service Installation
# Run this before installing the service to identify potential issues

$ErrorActionPreference = "Continue"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Ollama Service Prerequisites Check" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$allChecksPassed = $true

# Check 1: Administrator privileges
Write-Host "[CHECK 1] Administrator Privileges" -ForegroundColor Yellow
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if ($isAdmin) {
    Write-Host "  [OK] Running as Administrator" -ForegroundColor Green
} else {
    Write-Host "  [FAIL] Not running as Administrator" -ForegroundColor Red
    Write-Host "  Action: Right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    $allChecksPassed = $false
}
Write-Host ""

# Check 2: Ollama installation
Write-Host "[CHECK 2] Ollama Installation" -ForegroundColor Yellow
$ollamaExe = "$env:LOCALAPPDATA\Programs\Ollama\ollama.exe"
if (Test-Path $ollamaExe) {
    Write-Host "  [OK] Ollama found at: $ollamaExe" -ForegroundColor Green
    
    # Get version
    try {
        $version = & $ollamaExe --version 2>&1
        Write-Host "  Version: $version" -ForegroundColor Gray
    } catch {
        Write-Host "  [WARNING] Could not determine version" -ForegroundColor Yellow
    }
} else {
    Write-Host "  [FAIL] Ollama not found at: $ollamaExe" -ForegroundColor Red
    Write-Host "  Action: Run install_ollama.ps1 first" -ForegroundColor Yellow
    $allChecksPassed = $false
}
Write-Host ""

# Check 3: Running Ollama processes
Write-Host "[CHECK 3] Running Ollama Processes" -ForegroundColor Yellow
$ollamaProcesses = Get-Process -Name "ollama" -ErrorAction SilentlyContinue
if ($ollamaProcesses) {
    Write-Host "  [WARNING] Ollama is currently running" -ForegroundColor Yellow
    Write-Host "  Found $($ollamaProcesses.Count) process(es):" -ForegroundColor Gray
    foreach ($proc in $ollamaProcesses) {
        Write-Host "    PID: $($proc.Id), Memory: $([math]::Round($proc.WorkingSet64/1MB, 2)) MB" -ForegroundColor Gray
    }
    Write-Host "  Action: These will be stopped during service installation" -ForegroundColor Yellow
} else {
    Write-Host "  [OK] No Ollama processes running" -ForegroundColor Green
}
Write-Host ""

# Check 4: Port availability
Write-Host "[CHECK 4] Port 11434 Availability" -ForegroundColor Yellow
$portInUse = Get-NetTCPConnection -LocalPort 11434 -ErrorAction SilentlyContinue
if ($portInUse) {
    Write-Host "  [WARNING] Port 11434 is in use" -ForegroundColor Yellow
    foreach ($conn in $portInUse) {
        $process = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
        if ($process) {
            Write-Host "    Process: $($process.Name) (PID: $($process.Id))" -ForegroundColor Gray
        }
    }
    Write-Host "  Action: This port will be freed when Ollama processes are stopped" -ForegroundColor Yellow
} else {
    Write-Host "  [OK] Port 11434 is available" -ForegroundColor Green
}
Write-Host ""

# Check 5: NSSM availability
Write-Host "[CHECK 5] NSSM Availability" -ForegroundColor Yellow
$nssmPath = "$PSScriptRoot\nssm\win64\nssm.exe"
if (Test-Path $nssmPath) {
    Write-Host "  [OK] NSSM found at: $nssmPath" -ForegroundColor Green
} else {
    Write-Host "  [FAIL] NSSM not found at: $nssmPath" -ForegroundColor Red
    Write-Host "  Action: Run download_nssm.ps1 first" -ForegroundColor Yellow
    $allChecksPassed = $false
}
Write-Host ""

# Check 6: Existing service
Write-Host "[CHECK 6] Existing Ollama Service" -ForegroundColor Yellow
$existingService = Get-Service -Name "Ollama" -ErrorAction SilentlyContinue
if ($existingService) {
    Write-Host "  [INFO] Service 'Ollama' already exists" -ForegroundColor Yellow
    Write-Host "    Status: $($existingService.Status)" -ForegroundColor Gray
    Write-Host "    StartType: $($existingService.StartType)" -ForegroundColor Gray
    Write-Host "  Action: Installation script will prompt to reinstall" -ForegroundColor Yellow
} else {
    Write-Host "  [OK] No existing service found" -ForegroundColor Green
}
Write-Host ""

# Check 7: Logs directory
Write-Host "[CHECK 7] Logs Directory" -ForegroundColor Yellow
$logPath = "$PSScriptRoot\..\logs"
if (Test-Path $logPath) {
    Write-Host "  [OK] Logs directory exists: $logPath" -ForegroundColor Green
} else {
    Write-Host "  [INFO] Logs directory will be created: $logPath" -ForegroundColor Yellow
}
Write-Host ""

# Check 8: Models directory
Write-Host "[CHECK 8] Models Directory" -ForegroundColor Yellow
$modelsPath = "$PSScriptRoot\..\models"
if (Test-Path $modelsPath) {
    Write-Host "  [OK] Models directory exists: $modelsPath" -ForegroundColor Green
    
    # Check for models
    $modelFiles = Get-ChildItem -Path $modelsPath -Recurse -File -ErrorAction SilentlyContinue
    if ($modelFiles) {
        $totalSize = ($modelFiles | Measure-Object -Property Length -Sum).Sum
        Write-Host "    Files: $($modelFiles.Count), Total size: $([math]::Round($totalSize/1GB, 2)) GB" -ForegroundColor Gray
    }
} else {
    Write-Host "  [INFO] Models directory will be created: $modelsPath" -ForegroundColor Yellow
}
Write-Host ""

# Check 9: Disk space
Write-Host "[CHECK 9] Disk Space" -ForegroundColor Yellow
$drive = (Get-Item $PSScriptRoot).PSDrive
$freeSpace = (Get-PSDrive $drive.Name).Free
$freeSpaceGB = [math]::Round($freeSpace/1GB, 2)
if ($freeSpaceGB -gt 10) {
    Write-Host "  [OK] Free space on $($drive.Name): $freeSpaceGB GB" -ForegroundColor Green
} else {
    Write-Host "  [WARNING] Low disk space on $($drive.Name): $freeSpaceGB GB" -ForegroundColor Yellow
    Write-Host "  Recommended: At least 10GB free" -ForegroundColor Gray
}
Write-Host ""

# Check 10: System resources
Write-Host "[CHECK 10] System Resources" -ForegroundColor Yellow
$os = Get-CimInstance Win32_OperatingSystem
$totalRAM = [math]::Round($os.TotalVisibleMemorySize/1MB, 2)
$freeRAM = [math]::Round($os.FreePhysicalMemory/1MB, 2)
Write-Host "  Total RAM: $totalRAM GB" -ForegroundColor Gray
Write-Host "  Free RAM: $freeRAM GB" -ForegroundColor Gray
if ($totalRAM -ge 8) {
    Write-Host "  [OK] Sufficient RAM for Ollama" -ForegroundColor Green
} else {
    Write-Host "  [WARNING] Low RAM. Recommended: 8GB or more" -ForegroundColor Yellow
}
Write-Host ""

# Summary
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Summary" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if ($allChecksPassed) {
    Write-Host "[SUCCESS] All critical checks passed!" -ForegroundColor Green
    Write-Host ""
    Write-Host "You can proceed with service installation:" -ForegroundColor White
    Write-Host "  .\install_ollama_service.ps1" -ForegroundColor Gray
    Write-Host "  or" -ForegroundColor Gray
    Write-Host "  Right-click install_ollama_service.bat → Run as administrator" -ForegroundColor Gray
} else {
    Write-Host "[FAIL] Some critical checks failed" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please resolve the issues above before installing the service" -ForegroundColor Yellow
}

Write-Host ""
