# CI & Change Management Migration - Quick Start Guide

## Files Created

### 1. Database Migration Script
**File:** `database/ci_change_migration.sql`
- Creates 10 tables for CI and Change Management
- Creates 2 triggers for auto-numbering (CI-YYYY-NNN, CHG-YYYY-NNN)
- Creates 5 views for reporting
- Initializes sequences table

### 2. Web-Based Migration Runner
**File:** `run_ci_change_migration.php`
- User-friendly web interface for running migration
- Password protected (default: `migrate2025`)
- Inserts sample test data (optional)
- Verifies table creation
- Shows detailed results

### 3. Verification Script
**File:** `verify_ci_migration.php`
- Checks database connectivity
- Verifies migration file exists
- Checks existing schema
- Validates MySQL version
- Tests upload directories
- Checks for admin users

### 4. Upload Directories
- `uploads/ci_attachments/` - For CI attachments (invoices, manuals, warranties)
- `uploads/change_attachments/` - For change attachments (plans, documents)
- Both protected with .htaccess files

### 5. Documentation
- `database/CI_CHANGE_MIGRATION_README.md` - Complete migration guide
- `database/MIGRATION_QUICK_START.md` - This file

## Quick Start (3 Steps)

### Step 1: Verify System
Navigate to: `http://localhost/verify_ci_migration.php`

This will check:
- ✓ Database connection
- ✓ Migration file exists
- ✓ Required tables present
- ✓ Upload directories
- ✓ MySQL version
- ✓ Admin users

### Step 2: Run Migration
Navigate to: `http://localhost/run_ci_change_migration.php`

1. Enter password: `migrate2025`
2. Check "Insert sample test data" (recommended for development)
3. Click "Run Migration"
4. Review results

### Step 3: Clean Up
After successful migration:
1. Delete `run_ci_change_migration.php` (security)
2. Delete `verify_ci_migration.php` (optional)
3. Delete `verify_migration.php` (optional)

## What Gets Created

### Tables (10)
1. **configuration_items** - IT assets (hardware, software, licenses)
2. **ci_history** - Audit trail for CI changes
3. **ci_attachments** - File attachments for CIs
4. **changes** - Change requests
5. **change_history** - Audit trail for change status transitions
6. **change_attachments** - File attachments for changes
7. **ticket_ci_relations** - Links tickets to CIs
8. **ticket_change_relations** - Links tickets to changes
9. **change_ci_relations** - Links changes to CIs
10. **sequences** - Manages auto-numbering

### Sample Data (Optional)
- **5 Configuration Items:**
  - Dell Latitude 5520 Laptop
  - HP EliteDesk 800 Desktop
  - Microsoft Office 365 License
  - Dell P2422H Monitor
  - JetBrains IntelliJ IDEA License

- **3 Changes:**
  - Upgrade Office 365 to E3 Plan (Nieuw)
  - Replace aging network switch (In beoordeling)
  - Implement backup solution (Goedgekeurd)

## Auto-Numbering Examples

### CI Numbers
- CI-2025-001
- CI-2025-002
- CI-2025-003
- CI-2026-001 (resets each year)

### Change Numbers
- CHG-2025-001
- CHG-2025-002
- CHG-2025-003
- CHG-2026-001 (resets each year)

## Troubleshooting

### "Table already exists" warnings
- Normal if running migration multiple times
- Migration uses `CREATE TABLE IF NOT EXISTS`
- Safe to ignore

### "No admin user found"
- Sample data requires an admin user
- Create admin user first or skip sample data

### "Permission denied" errors
- Check MySQL user has CREATE, TRIGGER, and CREATE VIEW privileges
- Grant privileges: `GRANT ALL ON ticketportaal.* TO 'user'@'localhost';`

### Upload directory not writable
- Set permissions: `chmod 755 uploads/ci_attachments uploads/change_attachments`
- Or use Windows file properties to allow write access

## Next Steps

After successful migration:

1. **Implement Classes** (Task 2 & 3)
   - `classes/ConfigItem.php`
   - `classes/Change.php`
   - `classes/ChangeReport.php`

2. **Create UI Pages** (Task 4 & 5)
   - CI overview and detail pages
   - Change overview and detail pages
   - Forms for creating/editing

3. **Integrate with Tickets** (Task 7)
   - Add CI selection to ticket forms
   - Add "Create Change" button to tickets
   - Display linked CIs and changes

4. **Update Navigation** (Task 8)
   - Add menu items to sidebar
   - Add dashboard widgets

## Support

For detailed information, see:
- `database/CI_CHANGE_MIGRATION_README.md` - Full documentation
- `.kiro/specs/ci-change-management/design.md` - Technical design
- `.kiro/specs/ci-change-management/requirements.md` - Requirements

## Security Notes

- Change the migration password in `run_ci_change_migration.php`
- Delete migration files after successful installation
- Upload directories are protected with .htaccess
- All SQL uses prepared statements (PDO)
- Audit trails are immutable (no delete/update)

---

**Migration Version:** 1.0  
**Created:** 2025-10-20  
**Database:** MySQL 5.7+ / MariaDB 10.2+
