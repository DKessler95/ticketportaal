# Ollama Installation and Testing Scripts

This directory contains PowerShell scripts for installing, configuring, verifying, and testing Ollama with Llama 3.1 8B model.

## Scripts Overview

### Installation Scripts

#### 1. install_ollama.ps1
**Purpose**: Automated installation and configuration of Ollama

**What it does:**
- Downloads Ollama Windows installer from ollama.com
- Installs Ollama to default location
- Creates models directory at C:\TicketportaalAI\models
- Configures environment variables (OLLAMA_HOST, OLLAMA_ORIGINS, OLLAMA_MODELS)
- Starts Ollama service
- Pulls Llama 3.1 8B model (4.7GB download)
- Tests model with a simple query

**Usage:**
```powershell
cd C:\path\to\ticketportaal\ai_module\scripts
.\install_ollama.ps1
```

**Requirements:**
- Administrator privileges (recommended)
- Internet connection
- At least 10GB free disk space

**Duration:** 15-45 minutes (depending on internet speed)

---

### Verification Scripts

#### 2. verify_ollama.ps1
**Purpose**: Verify Ollama installation and configuration

**What it checks:**
- ✓ Ollama command availability in PATH
- ✓ Environment variables (OLLAMA_HOST, OLLAMA_ORIGINS, OLLAMA_MODELS)
- ✓ Models directory existence
- ✓ Ollama service status
- ✓ API endpoint accessibility (http://localhost:11434)
- ✓ Llama 3.1 8B model installation
- ✓ Model functionality with test query

**Usage:**
```powershell
cd C:\path\to\ticketportaal\ai_module\scripts
.\verify_ollama.ps1
```

**Output:** Pass/fail status for each check with troubleshooting suggestions

**Duration:** 30-60 seconds

---

### Testing Scripts

#### 3. test_ollama_api.ps1
**Purpose**: Comprehensive API testing for Ollama endpoints

**What it tests:**
1. **GET /api/tags** - List available models
2. **POST /api/show** - Get model information
3. **POST /api/generate** - Generate response (non-streaming)
4. **POST /api/generate** - Generate with context (RAG simulation)
5. **POST /api/chat** - Chat API (conversational)
6. **Performance test** - Multiple rapid queries

**Usage:**
```powershell
cd C:\path\to\ticketportaal\ai_module\scripts
.\test_ollama_api.ps1
```

**Output:** 
- Test results for each endpoint
- Response times and token counts
- Performance metrics

**Duration:** 2-5 minutes

---

### Windows Service Management Scripts

#### 4. download_nssm.ps1
**Purpose**: Download NSSM (Non-Sucking Service Manager) for Windows Service management

**What it does:**
- Downloads NSSM 2.24 from nssm.cc
- Extracts to scripts/nssm/ directory
- Verifies installation

**Usage:**
```powershell
.\download_nssm.ps1
# Or use batch file:
download_nssm.bat
```

**Duration:** 30 seconds

---

#### 5. install_ollama_service.ps1
**Purpose**: Install Ollama as a Windows Service with automatic startup and recovery

**What it does:**
- Installs Ollama as Windows Service using NSSM
- Configures automatic startup (SERVICE_AUTO_START)
- Sets up service recovery (restart on failure, 60 second delay)
- Configures environment variables
- Sets up logging to ai_module/logs/
- Starts the service and tests API

**Usage:**
```powershell
# Must run as Administrator
.\install_ollama_service.ps1
# Or use batch file:
Right-click install_ollama_service.bat → Run as administrator
```

**Requirements:**
- Administrator privileges (required)
- NSSM downloaded (run download_nssm.ps1 first)
- Ollama installed

**Duration:** 1-2 minutes

---

#### 6. test_ollama_service.ps1
**Purpose**: Comprehensive testing of Ollama Windows Service operations

**What it tests:**
1. Service stop operation
2. API unavailability when stopped
3. Service start operation
4. API availability when running
5. Service restart operation
6. API recovery after restart
7. Service configuration (Automatic startup)

**Usage:**
```powershell
# Must run as Administrator
.\test_ollama_service.ps1
# Or use batch file:
Right-click test_ollama_service.bat → Run as administrator
```

**Duration:** 1-2 minutes

---

#### 7. uninstall_ollama_service.ps1
**Purpose**: Remove Ollama Windows Service

**What it does:**
- Stops the Ollama service
- Removes service registration
- Keeps Ollama application installed

**Usage:**
```powershell
# Must run as Administrator
.\uninstall_ollama_service.ps1
# Or use batch file:
Right-click uninstall_ollama_service.bat → Run as administrator
```

**Duration:** 30 seconds

---

## Quick Start Guide

### First-Time Setup

1. **Install Ollama:**
   ```powershell
   .\install_ollama.ps1
   ```
   Wait for installation to complete (15-45 minutes)

2. **Verify Installation:**
   ```powershell
   .\verify_ollama.ps1
   ```
   Ensure all checks pass

3. **Test API:**
   ```powershell
   .\test_ollama_api.ps1
   ```
   Verify all endpoints work correctly

4. **Setup Windows Service (Recommended):**
   ```powershell
   # Download NSSM
   .\download_nssm.ps1
   
   # Install as service (as Administrator)
   Right-click install_ollama_service.bat → Run as administrator
   
   # Test service operations (as Administrator)
   Right-click test_ollama_service.bat → Run as administrator
   ```
   This ensures Ollama starts automatically on server boot

### Troubleshooting

If verification or tests fail:

1. **Restart PowerShell** to refresh environment variables
2. **Check Ollama service:**
   ```powershell
   Get-Service Ollama
   Start-Service Ollama
   ```
3. **Start Ollama manually** if service doesn't exist:
   ```powershell
   ollama serve
   ```
4. **Re-run verification:**
   ```powershell
   .\verify_ollama.ps1
   ```

## Common Issues and Solutions

### Issue: "Ollama command not found"
**Solution:**
```powershell
# Refresh PATH
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")

# Or restart PowerShell
```

### Issue: "Cannot connect to API"
**Solution:**
```powershell
# Check if Ollama is running
Get-Process ollama

# Start Ollama
ollama serve

# Or start service
Start-Service Ollama
```

### Issue: "Model not found"
**Solution:**
```powershell
# Pull the model
ollama pull llama3.1:8b

# Verify
ollama list
```

### Issue: "Environment variables not set"
**Solution:**
```powershell
# Set manually
[System.Environment]::SetEnvironmentVariable("OLLAMA_HOST", "0.0.0.0:11434", "Machine")
[System.Environment]::SetEnvironmentVariable("OLLAMA_ORIGINS", "http://localhost:*,http://127.0.0.1:*", "Machine")
[System.Environment]::SetEnvironmentVariable("OLLAMA_MODELS", "C:\TicketportaalAI\models", "Machine")

# Restart PowerShell
```

## Manual Commands

### Ollama CLI Commands

```powershell
# List installed models
ollama list

# Pull a model
ollama pull llama3.1:8b

# Run interactive chat
ollama run llama3.1:8b

# Single query
ollama run llama3.1:8b "Your question here"

# Show model info
ollama show llama3.1:8b

# Remove a model
ollama rm llama3.1:8b

# Start Ollama server
ollama serve
```

### API Testing with PowerShell

```powershell
# List models
Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method GET

# Generate response
$body = @{
    model = "llama3.1:8b"
    prompt = "What is AI?"
    stream = $false
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://localhost:11434/api/generate" -Method POST -Body $body -ContentType "application/json"
```

## Environment Variables Reference

| Variable | Value | Purpose |
|----------|-------|---------|
| OLLAMA_HOST | 0.0.0.0:11434 | Bind to all interfaces on port 11434 |
| OLLAMA_ORIGINS | http://localhost:*,http://127.0.0.1:* | CORS allowed origins |
| OLLAMA_MODELS | C:\TicketportaalAI\models | Custom models directory |

## Performance Expectations

### Llama 3.1 8B Model (CPU-only)

- **Response Time**: 3-10 seconds per query
- **RAM Usage**: 6-8GB during inference
- **CPU Usage**: 50-80% on 4-8 cores
- **Throughput**: ~10-20 tokens/second

### System Requirements

**Minimum:**
- 8GB RAM
- 4 CPU cores
- 10GB disk space

**Recommended:**
- 16GB RAM
- 8 CPU cores
- 60GB disk space

## Windows Service Management

### Service Commands

```powershell
# Using NSSM
.\nssm\win64\nssm.exe start Ollama
.\nssm\win64\nssm.exe stop Ollama
.\nssm\win64\nssm.exe restart Ollama
.\nssm\win64\nssm.exe status Ollama

# Using PowerShell
Get-Service Ollama
Start-Service Ollama
Stop-Service Ollama
Restart-Service Ollama

# View logs
Get-Content ..\logs\ollama_stdout.log -Tail 50
Get-Content ..\logs\ollama_stderr.log -Tail 50
```

### Service Benefits

- ✓ Automatic startup on server boot
- ✓ Runs without user login
- ✓ Auto-restart on failure (60 second delay)
- ✓ Centralized logging
- ✓ Standard Windows service management

## Next Steps

After successful installation and testing:

1. ✓ **Task 2 Complete**: Ollama installed and configured
2. ✓ **Task 3 Complete**: Ollama running as Windows Service
3. → **Task 4**: Create Directory Structure
4. → **Phase 2**: Begin data quality and category fields setup

## Additional Resources

- **Main Installation Guide**: `../OLLAMA_INSTALLATION_GUIDE.md`
- **Service Setup Guide**: `../OLLAMA_SERVICE_SETUP.md`
- **Ollama Documentation**: https://github.com/ollama/ollama
- **Ollama API Reference**: https://github.com/ollama/ollama/blob/main/docs/api.md
- **NSSM Documentation**: https://nssm.cc/usage
- **Task List**: `../../.kiro/specs/rag-ai-local-implementation/tasks.md`

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review `../OLLAMA_INSTALLATION_GUIDE.md`
3. Run `.\verify_ollama.ps1` for diagnostic information
4. Check Ollama logs in Windows Event Viewer
5. Contact system administrator
