# Quick Start Guide - Task 2: Ollama Installation

This guide will help you quickly install and configure Ollama with Llama 3.1 8B model.

## Prerequisites Check

Before starting, ensure you have:
- [ ] Windows Server 2019+ (or Windows 10/11)
- [ ] Administrator access
- [ ] Internet connection
- [ ] At least 10GB free disk space
- [ ] PowerShell 5.1 or later

Check disk space:
```powershell
Get-PSDrive C | Select-Object Used,Free
```

## Installation Steps

### Step 1: Run Installation Script

**Option A: Double-click the batch file**
1. Navigate to `ai_module\scripts\`
2. Double-click `install_ollama.bat`
3. Follow the prompts

**Option B: Run PowerShell script**
```powershell
cd C:\Users\Damian\XAMPP\htdocs\ticketportaal\ai_module\scripts
.\install_ollama.ps1
```

### Step 2: Wait for Installation

The script will:
1. Download Ollama installer (~200MB) - 2-5 minutes
2. Install Ollama - 2-3 minutes
3. Configure environment variables - instant
4. Start Ollama service - 10 seconds
5. Download Llama 3.1 8B model (~4.7GB) - 10-30 minutes
6. Test the model - 30 seconds

**Total time: 15-45 minutes** (mostly downloading)

### Step 3: Verify Installation

Run the verification script:
```powershell
cd ai_module\scripts
.\verify_ollama.bat
```

Expected output:
```
✓ Ollama found at: C:\Users\...\Ollama\ollama.exe
✓ OLLAMA_HOST: 0.0.0.0:11434
✓ OLLAMA_ORIGINS: http://localhost:*,http://127.0.0.1:*
✓ OLLAMA_MODELS: C:\TicketportaalAI\models
✓ Models directory exists
✓ Ollama service is running
✓ Ollama API is accessible
✓ Llama 3.1 8B model is installed
✓ Model responded successfully

Passed: 8 / 8 checks
```

### Step 4: Test the API

Run the API test script:
```powershell
cd ai_module\scripts
.\test_ollama_api.bat
```

This will test all API endpoints and show response times.

## Quick Manual Test

Test Ollama from command line:

```powershell
# Check if Ollama is installed
ollama --version

# List installed models
ollama list

# Run a simple query
ollama run llama3.1:8b "What is a printer paper jam?"

# Test API endpoint
Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method GET
```

## Troubleshooting

### Problem: "ollama: command not found"

**Solution:**
```powershell
# Restart PowerShell to refresh PATH
# Or manually refresh:
$env:Path = [System.Environment]::GetEnvironmentVariable("Path","Machine") + ";" + [System.Environment]::GetEnvironmentVariable("Path","User")
```

### Problem: "Cannot connect to Ollama API"

**Solution:**
```powershell
# Check if Ollama is running
Get-Process ollama

# If not running, start it:
ollama serve

# Or start the service:
Start-Service Ollama
```

### Problem: "Model download is slow"

**Expected:** 4.7GB download can take 10-30 minutes depending on internet speed.

**Check progress:**
```powershell
# The download shows progress in the terminal
# You can also check the models directory size:
Get-ChildItem C:\TicketportaalAI\models -Recurse | Measure-Object -Property Length -Sum
```

### Problem: "Installation script fails"

**Manual installation:**
1. Download from: https://ollama.com/download
2. Run OllamaSetup.exe
3. Set environment variables manually (see OLLAMA_INSTALLATION_GUIDE.md)
4. Run: `ollama pull llama3.1:8b`

## What's Next?

After successful installation:

1. ✅ **Task 2 Complete**: Ollama is installed and working
2. → **Task 3**: Setup Ollama as Windows Service (for auto-start)
3. → **Task 4**: Create directory structure for AI module
4. → **Phase 2**: Begin data quality and category fields setup

## Configuration Summary

After installation, you'll have:

| Component | Location | Purpose |
|-----------|----------|---------|
| Ollama | `C:\Users\<User>\AppData\Local\Programs\Ollama` | LLM server |
| Models | `C:\TicketportaalAI\models` | Model storage |
| API | `http://localhost:11434` | REST API endpoint |
| Model | llama3.1:8b (4.7GB) | Language model |

## Environment Variables

| Variable | Value | Purpose |
|----------|-------|---------|
| OLLAMA_HOST | 0.0.0.0:11434 | Bind to all interfaces |
| OLLAMA_ORIGINS | http://localhost:*,http://127.0.0.1:* | CORS origins |
| OLLAMA_MODELS | C:\TicketportaalAI\models | Models directory |

## Performance Expectations

With Llama 3.1 8B on CPU:
- **Response time**: 3-10 seconds per query
- **RAM usage**: 6-8GB during inference
- **CPU usage**: 50-80% on 4-8 cores
- **Throughput**: ~10-20 tokens/second

## Useful Commands

```powershell
# Check Ollama status
Get-Service Ollama

# Start Ollama
Start-Service Ollama

# Stop Ollama
Stop-Service Ollama

# Restart Ollama
Restart-Service Ollama

# View Ollama logs (if service exists)
Get-EventLog -LogName Application -Source Ollama -Newest 10

# List models
ollama list

# Remove a model
ollama rm llama3.1:8b

# Pull a model again
ollama pull llama3.1:8b

# Interactive chat
ollama run llama3.1:8b
```

## Getting Help

1. **Detailed Guide**: See `OLLAMA_INSTALLATION_GUIDE.md`
2. **Scripts Documentation**: See `scripts/README.md`
3. **Task List**: See `.kiro/specs/rag-ai-local-implementation/tasks.md`
4. **Ollama Docs**: https://github.com/ollama/ollama

## Support Checklist

If you encounter issues, gather this information:

```powershell
# System info
systeminfo | findstr /C:"OS Name" /C:"Total Physical Memory"

# Disk space
Get-PSDrive C

# Ollama version
ollama --version

# Service status
Get-Service Ollama

# Process status
Get-Process ollama

# Environment variables
[System.Environment]::GetEnvironmentVariable("OLLAMA_HOST", "Machine")
[System.Environment]::GetEnvironmentVariable("OLLAMA_ORIGINS", "Machine")
[System.Environment]::GetEnvironmentVariable("OLLAMA_MODELS", "Machine")

# Test API
Invoke-RestMethod -Uri "http://localhost:11434/api/tags" -Method GET
```

Share this output when requesting support.
