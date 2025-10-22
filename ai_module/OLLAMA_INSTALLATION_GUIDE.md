# Ollama Installation and Configuration Guide

This guide provides step-by-step instructions for installing and configuring Ollama with Llama 3.1 8B model on Windows Server.

## Prerequisites

- Windows Server 2019 or later (or Windows 10/11)
- Administrator access
- Internet connection for downloading Ollama and model
- At least 10GB free disk space (for Ollama + Llama 3.1 8B model)
- PowerShell 5.1 or later

## Installation Methods

### Method 1: Automated Installation (Recommended)

Run the PowerShell installation script:

```powershell
# Navigate to the scripts directory
cd C:\path\to\ticketportaal\ai_module\scripts

# Run the installation script
.\install_ollama.ps1
```

The script will:
1. Download Ollama Windows installer
2. Install Ollama to default location
3. Configure environment variables
4. Pull Llama 3.1 8B model (4.7GB download)
5. Test the model with a simple query

### Method 2: Manual Installation

#### Step 1: Download Ollama

1. Visit https://ollama.com/download
2. Download the Windows installer (OllamaSetup.exe)
3. Run the installer and follow the prompts
4. Ollama will be installed to: `C:\Users\<YourUser>\AppData\Local\Programs\Ollama`

#### Step 2: Configure Environment Variables

Open PowerShell as Administrator and run:

```powershell
# Create models directory
New-Item -ItemType Directory -Path "C:\TicketportaalAI\models" -Force

# Set environment variables
[System.Environment]::SetEnvironmentVariable("OLLAMA_HOST", "0.0.0.0:11434", "Machine")
[System.Environment]::SetEnvironmentVariable("OLLAMA_ORIGINS", "http://localhost:*,http://127.0.0.1:*", "Machine")
[System.Environment]::SetEnvironmentVariable("OLLAMA_MODELS", "C:\TicketportaalAI\models", "Machine")
```

**Environment Variables Explained:**

- **OLLAMA_HOST**: `0.0.0.0:11434` - Binds Ollama to all network interfaces on port 11434 (allows internal network access)
- **OLLAMA_ORIGINS**: `http://localhost:*,http://127.0.0.1:*` - Allows CORS requests from localhost
- **OLLAMA_MODELS**: `C:\TicketportaalAI\models` - Custom directory for storing models

#### Step 3: Start Ollama

```powershell
# Start Ollama service (if installed as service)
Start-Service Ollama

# OR start manually
ollama serve
```

#### Step 4: Pull Llama 3.1 8B Model

Open a new PowerShell window and run:

```powershell
ollama pull llama3.1:8b
```

This will download the Llama 3.1 8B model (~4.7GB). The download may take 10-30 minutes depending on your internet connection.

#### Step 5: Test the Model

```powershell
ollama run llama3.1:8b "Hello, can you help me troubleshoot a printer issue?"
```

You should see a response from the model.

## Verification

Run the verification script to check your installation:

```powershell
cd C:\path\to\ticketportaal\ai_module\scripts
.\verify_ollama.ps1
```

The script will check:
- ✓ Ollama command availability
- ✓ Environment variables configuration
- ✓ Models directory existence
- ✓ Ollama service status
- ✓ API endpoint accessibility
- ✓ Llama 3.1 8B model installation
- ✓ Model functionality with test query

## Testing the API

### Test 1: Check Available Models

```powershell
Invoke-WebRequest -Uri "http://localhost:11434/api/tags" -Method GET
```

Expected response:
```json
{
  "models": [
    {
      "name": "llama3.1:8b",
      "modified_at": "2024-10-22T10:30:00Z",
      "size": 4661224448
    }
  ]
}
```

### Test 2: Generate Response

```powershell
$body = @{
    model = "llama3.1:8b"
    prompt = "What is a printer paper jam?"
    stream = $false
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:11434/api/generate" -Method POST -Body $body -ContentType "application/json"
```

### Test 3: Using Ollama CLI

```powershell
# Interactive mode
ollama run llama3.1:8b

# Single query
ollama run llama3.1:8b "Explain how to fix a printer paper jam"

# List installed models
ollama list

# Show model info
ollama show llama3.1:8b
```

## Troubleshooting

### Issue: "ollama: command not found"

**Solution:**
1. Restart PowerShell to refresh PATH
2. Check if Ollama is installed: `Get-Command ollama`
3. Manually add to PATH if needed:
   ```powershell
   $env:Path += ";C:\Users\$env:USERNAME\AppData\Local\Programs\Ollama"
   ```

### Issue: "Cannot connect to Ollama API"

**Solution:**
1. Check if Ollama is running:
   ```powershell
   Get-Process ollama
   ```
2. Start Ollama manually:
   ```powershell
   ollama serve
   ```
3. Check firewall settings (port 11434 should be open)

### Issue: "Model download fails"

**Solution:**
1. Check internet connection
2. Check disk space (need at least 5GB free)
3. Try downloading again:
   ```powershell
   ollama pull llama3.1:8b
   ```
4. If persistent, try a smaller model first:
   ```powershell
   ollama pull llama3.1:latest
   ```

### Issue: "Environment variables not taking effect"

**Solution:**
1. Restart PowerShell/Command Prompt
2. Restart the Ollama service:
   ```powershell
   Restart-Service Ollama
   ```
3. Verify variables are set:
   ```powershell
   [System.Environment]::GetEnvironmentVariable("OLLAMA_HOST", "Machine")
   [System.Environment]::GetEnvironmentVariable("OLLAMA_ORIGINS", "Machine")
   [System.Environment]::GetEnvironmentVariable("OLLAMA_MODELS", "Machine")
   ```

### Issue: "Model runs slowly"

**Possible causes:**
- Insufficient RAM (need at least 8GB available)
- CPU-only inference (no GPU acceleration)
- Other processes consuming resources

**Solutions:**
1. Close unnecessary applications
2. Check system resources:
   ```powershell
   Get-Process | Sort-Object -Property WS -Descending | Select-Object -First 10
   ```
3. Consider using a smaller model if performance is critical

## Performance Expectations

### Llama 3.1 8B Model

- **Model Size**: 4.7GB
- **RAM Usage**: 6-8GB during inference
- **CPU Usage**: 50-80% on 4-8 cores
- **Response Time**: 3-10 seconds per query (CPU-only)
- **Context Window**: 8,192 tokens

### System Requirements

**Minimum:**
- 8GB RAM
- 4 CPU cores
- 10GB disk space

**Recommended:**
- 16GB RAM
- 8 CPU cores
- 60GB disk space (for multiple models and data)

## Next Steps

After successful installation and verification:

1. ✓ Task 2 Complete: Ollama installed and configured
2. → Proceed to **Task 3**: Setup Ollama as Windows Service
3. → Continue with **Task 4**: Create Directory Structure

## Additional Resources

- **Ollama Documentation**: https://github.com/ollama/ollama/blob/main/README.md
- **Ollama API Reference**: https://github.com/ollama/ollama/blob/main/docs/api.md
- **Llama 3.1 Model Card**: https://ollama.com/library/llama3.1
- **Windows Service Setup**: See Task 3 documentation

## Configuration Reference

### Default Ollama Locations

- **Installation**: `C:\Users\<User>\AppData\Local\Programs\Ollama`
- **Models (default)**: `C:\Users\<User>\.ollama\models`
- **Models (custom)**: `C:\TicketportaalAI\models` (as configured)
- **Logs**: Check Windows Event Viewer or service logs

### API Endpoints

- **Base URL**: `http://localhost:11434`
- **Generate**: `POST /api/generate`
- **Chat**: `POST /api/chat`
- **List Models**: `GET /api/tags`
- **Show Model**: `POST /api/show`
- **Pull Model**: `POST /api/pull`
- **Delete Model**: `DELETE /api/delete`

### Useful Commands

```powershell
# List all models
ollama list

# Remove a model
ollama rm llama3.1:8b

# Update a model
ollama pull llama3.1:8b

# Show model details
ollama show llama3.1:8b

# Copy a model
ollama cp llama3.1:8b my-custom-model

# Create custom model from Modelfile
ollama create my-model -f Modelfile
```

## Security Considerations

1. **Network Access**: Ollama is configured to bind to `0.0.0.0:11434` for internal network access. Ensure firewall rules restrict access to trusted networks only.

2. **CORS Origins**: Only localhost origins are allowed. Adjust `OLLAMA_ORIGINS` if you need to access from other domains.

3. **Model Storage**: Models are stored in `C:\TicketportaalAI\models`. Ensure appropriate file permissions are set.

4. **API Authentication**: Ollama does not have built-in authentication. Use network-level security (firewall, VPN) to restrict access.

## Support

For issues or questions:
1. Check this guide's Troubleshooting section
2. Review Ollama logs in Windows Event Viewer
3. Consult the main project documentation
4. Contact the system administrator
