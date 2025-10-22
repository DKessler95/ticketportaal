# Quick Ollama Service Setup

Fast guide to set up Ollama as a Windows Service in 3 steps.

## Prerequisites

✅ Ollama installed and working  
✅ Administrator access  
✅ PowerShell available

## Setup Steps

### Step 1: Download NSSM (30 seconds)

```cmd
cd ai_module\scripts
download_nssm.bat
```

### Step 2: Install Service (1 minute)

```cmd
Right-click install_ollama_service.bat → Run as administrator
```

Wait for "Installation Complete!" message.

### Step 3: Test Service (1 minute)

```cmd
Right-click test_ollama_service.bat → Run as administrator
```

All tests should pass with "[SUCCESS]" message.

## Verification

Check service is running:

```powershell
Get-Service Ollama
```

Expected output:
```
Status   Name               DisplayName
------   ----               -----------
Running  Ollama             Ollama AI Service
```

Test API:

```powershell
Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get
```

## Service Management

### Quick Commands

```powershell
# Check status
Get-Service Ollama

# Start
Start-Service Ollama

# Stop
Stop-Service Ollama

# Restart
Restart-Service Ollama

# View logs
Get-Content ai_module\logs\ollama_stdout.log -Tail 50
```

### Using Services GUI

1. Press `Win + R`
2. Type `services.msc`
3. Find "Ollama AI Service"
4. Right-click for options

## What This Does

✅ Ollama starts automatically on server boot  
✅ Runs in background (no user login needed)  
✅ Auto-restarts if it crashes (60 second delay)  
✅ Logs to `ai_module\logs\`  
✅ Accessible at `http://localhost:11434`

## Troubleshooting

### Service won't start

```powershell
# Check logs
Get-Content ai_module\logs\ollama_stderr.log -Tail 50

# Verify Ollama installed
Test-Path "$env:LOCALAPPDATA\Programs\Ollama\ollama.exe"

# Check port availability
netstat -ano | findstr :11434
```

### API not responding

Wait 30-60 seconds after starting service, then test again:

```powershell
Start-Sleep -Seconds 60
Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method Get
```

## Uninstall Service

```cmd
Right-click uninstall_ollama_service.bat → Run as administrator
```

This removes the service but keeps Ollama installed.

## Full Documentation

See `OLLAMA_SERVICE_SETUP.md` for complete details.

## Next Steps

✅ Task 3 Complete - Ollama running as Windows Service  
→ Task 4 - Create Directory Structure  
→ Phase 2 - Data Quality & Category Fields
