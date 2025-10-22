# Set Permissions for IIS AppPool
# This script configures appropriate permissions for the TicketportaalAI directory

Write-Host "Setting permissions for C:\TicketportaalAI..." -ForegroundColor Cyan

# Define the IIS AppPool identity (adjust if your AppPool has a different name)
$appPoolIdentity = "IIS AppPool\DefaultAppPool"

# Alternative common AppPool names (uncomment the one you use):
# $appPoolIdentity = "IIS AppPool\ticketportaal"
# $appPoolIdentity = "BUILTIN\IIS_IUSRS"

# Get the ACL for the directory
$path = "C:\TicketportaalAI"
$acl = Get-Acl $path

# Create access rule for IIS AppPool
# Permissions: Read, Write, Modify for logs, chromadb_data, backups
$accessRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
    $appPoolIdentity,
    "ReadAndExecute, Write, Modify",
    "ContainerInherit, ObjectInherit",
    "None",
    "Allow"
)

# Add the rule to the ACL
$acl.SetAccessRule($accessRule)

# Apply the ACL
try {
    Set-Acl -Path $path -AclObject $acl
    Write-Host "✓ Permissions set successfully for $appPoolIdentity" -ForegroundColor Green
    Write-Host "  - Read, Write, Modify access granted" -ForegroundColor Gray
} catch {
    Write-Host "✗ Failed to set permissions: $_" -ForegroundColor Red
    Write-Host "  Note: You may need to run this script as Administrator" -ForegroundColor Yellow
    exit 1
}

# Verify permissions
Write-Host "`nVerifying permissions..." -ForegroundColor Cyan
$currentAcl = Get-Acl $path
$currentAcl.Access | Where-Object { $_.IdentityReference -like "*IIS*" -or $_.IdentityReference -like "*AppPool*" } | Format-Table IdentityReference, FileSystemRights, AccessControlType -AutoSize

Write-Host "`nPermissions configuration complete!" -ForegroundColor Green
