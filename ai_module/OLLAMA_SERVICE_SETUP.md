# Ollama Windows Service Setup Guide

This guide explains how to set up Ollama as a Windows Service using NSSM (Non-Sucking Service Manager), ensuring Ollama runs automatically on server startup and restarts on failure.

## Overview

Running Ollama as a Windows Service provides:
- **Automatic Startup**: Ollama starts automatically when the server boots
- **Auto-Recovery**: Service restarts automatically if it crashes
- **Background Operation**: Runs without requiring a user to be logged in
- **Centralized Management**: Standard Windows service management tools

## Prerequisites

Before setting up the service, ensure:
1. ✅ Ollama is installed (Task 2 completed)
2. ✅ Ollama works when run manually
3. ✅ You have Administrator privileges
4. ✅ Llama 3.1 model is downloaded

## Installation Steps

### Step 1: Download NSSM

NSSM (Non-Sucking Service Manager) is a tool that wraps applications as Windows Services.

**Option A: Using the automated script (Recommended)**
```cmd
cd ai_module\scripts
download_nssm.bat
```

**Option B: Manual download**
1. Visit https://nssm.cc/download
2. Download NSSM 2.24 (or latest version)
3. Extract to `ai_module\scripts\nssm\`

**Verification:**
```cmd
dir ai_module\scripts\nssm\win64\nssm.exe
```

### Step 2: Install Ollama as Windows Service

Run the installation script **as Administrator**:

```cmd
cd ai_module\scripts
Right-click install_ollama_service.bat → Run as administrator
```

The script will:
1. ✅ Check if Ollama is installed
2. ✅ Install Ollama as a Windows Service named "Ollama"
3. ✅ Configure automatic startup
4. ✅ Set up logging to `ai_module\logs\`
5. ✅ Configure restart on failure (60 second delay)
6. ✅ Set environment variables (OLLAMA_HOST, OLLAMA_ORIGINS, OLLAMA_MODELS)
7. ✅ Start the service
8. ✅ Test the API

**Expected Output:**
```
========================================
Ollama Windows Service Installation
========================================

[INFO] Installing Ollama as Windows Service...
[OK] Service installed
[INFO] Configuring service parameters...
[OK] Service configured
[INFO] Starting Ollama service...
[OK] Service started successfully!

========================================
Installation Complete!
========================================

Service Name: Ollama
Status: Running
Startup Type: Automatic
Logs: C:\path\to\ai_module\logs

Service Management Commands:
  Start:   nssm start Ollama
  Stop:    nssm stop Ollama
  Restart: nssm restart Ollama
  Status:  Get-Service Ollama

[OK] Ollama API is responding!
Available models: 1
```

### Step 3: Verify Service Configuration

Check the service is configured correctly:

```powershell
# Check service status
Get-Service Ollama

# Check service details
Get-Service Ollama | Select-Object *

# Check if service is set to Automatic
(Get-Service Ollama).StartType
```

**Expected:**
- Status: Running
- StartType: Automatic

### Step 4: Test Service Operations

Run the comprehensive test script **as Administrator**:

```cmd
cd ai_module\scripts
Right-click test_ollama_service.bat → Run as administrator
```

The test script will:
1. ✅ Stop the service and verify it stops
2. ✅ Verify API is not responding when stopped
3. ✅ Start the service and verify it starts
4. ✅ Verify API responds after start
5. ✅ Restart the service and verify it restarts
6. ✅ Verify API responds after restart
7. ✅ Check service configuration (Automatic startup)

**Expected Output:**
```
========================================
Ollama Service Test Script
========================================

Initial Service Status: Running

[TEST 1] Stopping service...
[OK] Service stopped successfully
[OK] API not responding (as expected)

[TEST 2] Starting service...
[OK] Service started successfully
[OK] API is responding

[TEST 3] Restarting service...
[OK] Service restarted successfully
[OK] API responding after restart

[TEST 4] Verifying service configuration...
[OK] Startup type is Automatic

========================================
Test Results Summary
========================================

Service Name: Ollama
Current Status: Running
Startup Type: Automatic

[SUCCESS] All service tests completed!
```

## Service Management

### Using NSSM Commands

```cmd
# Start service
ai_module\scripts\nssm\win64\nssm.exe start Ollama

# Stop service
ai_module\scripts\nssm\win64\nssm.exe stop Ollama

# Restart service
ai_module\scripts\nssm\win64\nssm.exe restart Ollama

# Check service status
ai_module\scripts\nssm\win64\nssm.exe status Ollama

# Edit service configuration (GUI)
ai_module\scripts\nssm\win64\nssm.exe edit Ollama
```

### Using Windows PowerShell

```powershell
# Check status
Get-Service Ollama

# Start service
Start-Service Ollama

# Stop service
Stop-Service Ollama

# Restart service
Restart-Service Ollama

# View service details
Get-Service Ollama | Format-List *
```

### Using Windows Services GUI

1. Press `Win + R`
2. Type `services.msc` and press Enter
3. Find "Ollama AI Service" in the list
4. Right-click for options (Start, Stop, Restart, Properties)

## Service Configuration Details

### Service Properties

| Property | Value |
|----------|-------|
| Service Name | Ollama |
| Display Name | Ollama AI Service |
| Description | Local LLM service for K&K Ticketportaal RAG AI |
| Startup Type | Automatic |
| Executable | `%LOCALAPPDATA%\Programs\Ollama\ollama.exe` |
| Arguments | `serve` |
| Recovery | Restart on failure (60 second delay) |

### Environment Variables

The service is configured with these environment variables:

```
OLLAMA_HOST=0.0.0.0:11434
OLLAMA_ORIGINS=*
OLLAMA_MODELS=C:\path\to\ai_module\models
```

### Logging

Service logs are written to:
- **Standard Output**: `ai_module\logs\ollama_stdout.log`
- **Standard Error**: `ai_module\logs\ollama_stderr.log`

View recent logs:
```powershell
# View last 50 lines of stdout
Get-Content ai_module\logs\ollama_stdout.log -Tail 50

# View last 50 lines of stderr
Get-Content ai_module\logs\ollama_stderr.log -Tail 50

# Monitor logs in real-time
Get-Content ai_module\logs\ollama_stdout.log -Wait -Tail 20
```

## Testing the Service

### Test 1: Automatic Startup

1. Restart the server
2. Wait for Windows to boot
3. Check if Ollama service is running:
   ```powershell
   Get-Service Ollama
   ```
4. Test the API:
   ```powershell
   Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get
   ```

### Test 2: Failure Recovery

1. Manually kill the Ollama process:
   ```powershell
   Get-Process ollama | Stop-Process -Force
   ```
2. Wait 60 seconds
3. Check if service restarted:
   ```powershell
   Get-Service Ollama
   ```
4. Verify API is responding again

### Test 3: API Availability

```powershell
# Test API endpoint
Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get

# Test with a simple query
$body = @{
    model = "llama3.1:8b"
    prompt = "Hello, are you working?"
    stream = $false
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:11434/api/generate" -Method Post -Body $body -ContentType "application/json"
```

## Troubleshooting

### Service Won't Start

**Check logs:**
```powershell
Get-Content ai_module\logs\ollama_stderr.log -Tail 50
```

**Common issues:**
- Ollama not installed correctly
- Port 11434 already in use
- Insufficient permissions

**Solutions:**
```powershell
# Check if port is in use
netstat -ano | findstr :11434

# Verify Ollama installation
Test-Path "$env:LOCALAPPDATA\Programs\Ollama\ollama.exe"

# Reinstall service
ai_module\scripts\uninstall_ollama_service.bat
ai_module\scripts\install_ollama_service.bat
```

### Service Starts but API Not Responding

**Wait longer** - Ollama can take 30-60 seconds to fully start, especially on first run.

**Check if process is running:**
```powershell
Get-Process ollama
```

**Test API with timeout:**
```powershell
Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get -TimeoutSec 60
```

### Service Keeps Restarting

**Check error logs:**
```powershell
Get-Content ai_module\logs\ollama_stderr.log -Tail 100
```

**Common causes:**
- Model files corrupted
- Insufficient RAM
- Disk space issues

**Check system resources:**
```powershell
# Check RAM
Get-CimInstance Win32_OperatingSystem | Select-Object FreePhysicalMemory, TotalVisibleMemorySize

# Check disk space
Get-PSDrive C
```

### Cannot Access Service Management

**Ensure you're running as Administrator:**
```powershell
# Check if running as admin
([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
```

## Uninstallation

To remove the Ollama service:

```cmd
cd ai_module\scripts
Right-click uninstall_ollama_service.bat → Run as administrator
```

This will:
1. Stop the service
2. Remove the service registration
3. Keep Ollama application installed (can still run manually)

## Next Steps

After completing this task:

✅ **Task 3 Complete**: Ollama is now running as a Windows Service

**Next Task**: Task 4 - Create Directory Structure
- Set up the complete directory structure for the AI module
- Configure permissions for IIS AppPool
- Set up log rotation

## Requirements Satisfied

This implementation satisfies the following requirements:

- **Requirement 5.1**: Ollama Service automatically starts as Windows Service with SERVICE_AUTO_START
- **Requirement 5.2**: Service configured to restart automatically on failure (60 second delay)
- **Requirement 5.3**: Service recovery options configured
- **Requirement 5.4**: Logs written to `ai_module\logs\` directory
- **Requirement 5.5**: Health check available via API endpoint

## Additional Resources

- **NSSM Documentation**: https://nssm.cc/usage
- **Ollama Documentation**: https://github.com/ollama/ollama/blob/main/docs/windows.md
- **Windows Services**: https://docs.microsoft.com/en-us/windows/win32/services/services

## Support

If you encounter issues:
1. Check the logs in `ai_module\logs\`
2. Review the troubleshooting section above
3. Verify all prerequisites are met
4. Contact the system administrator
