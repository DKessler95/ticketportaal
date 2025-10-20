# CI & Change Management Migration Guide

## Overview

This migration adds Configuration Item (CI) Management and Change Management capabilities to the ICT Ticketportaal, following ITIL best practices.

## What's Included

### Database Tables (10)

1. **configuration_items** - Stores all IT assets (hardware, software, licenses)
2. **ci_history** - Audit trail for all CI changes
3. **ci_attachments** - File attachments for CIs (invoices, manuals, warranties)
4. **changes** - Change requests and their details
5. **change_history** - Audit trail for all change status transitions
6. **change_attachments** - File attachments for changes
7. **ticket_ci_relations** - Links tickets to configuration items
8. **ticket_change_relations** - Links tickets to changes
9. **change_ci_relations** - Links changes to configuration items
10. **sequences** - Manages auto-numbering for CIs and Changes

### Database Triggers (2)

1. **before_ci_insert** - Auto-generates CI numbers in format CI-YYYY-NNN
2. **before_change_insert** - Auto-generates change numbers in format CHG-YYYY-NNN

### Database Views (5)

1. **v_active_cis** - Active CIs with owner information
2. **v_changes_overview** - Changes with user information
3. **v_ci_financial_summary** - Financial summary by type and status
4. **v_expiring_warranties** - CIs with warranties expiring within 90 days
5. **v_change_statistics** - Change statistics by status, type, and priority

## Migration Instructions

### Prerequisites

- Existing ICT Ticketportaal installation
- MySQL 5.7+ or MariaDB 10.2+
- PHP 7.4+
- At least one admin user in the system

### Step 1: Backup Database

**IMPORTANT:** Always backup your database before running migrations!

```bash
mysqldump -u root -p ticketportaal > backup_before_ci_change_$(date +%Y%m%d).sql
```

### Step 2: Run Migration

1. Navigate to: `http://your-domain/run_ci_change_migration.php`
2. Enter the migration password (default: `migrate2025`)
3. Check "Insert sample test data" if you want test data
4. Click "Run Migration"
5. Review the results

### Step 3: Verify Installation

The migration script will verify that all tables were created successfully. Check for:

- ✓ All 10 tables created
- ✓ All triggers created
- ✓ All views created
- ✓ Sample data inserted (if selected)

### Step 4: Security

**Delete the migration file after successful installation:**

```bash
rm run_ci_change_migration.php
```

Or manually delete `run_ci_change_migration.php` from your web root.

## Sample Data

If you selected "Insert sample test data", the following will be created:

### Sample Configuration Items (5)

1. Dell Latitude 5520 Laptop
2. HP EliteDesk 800 Desktop
3. Microsoft Office 365 Business License
4. Dell P2422H Monitor
5. JetBrains IntelliJ IDEA License

### Sample Changes (3)

1. Upgrade Office 365 to E3 Plan (Status: Nieuw)
2. Replace aging network switch (Status: In beoordeling)
3. Implement backup solution (Status: Goedgekeurd)

## Auto-Numbering

### CI Numbers

Format: `CI-YYYY-NNN`

- CI-2025-001
- CI-2025-002
- CI-2026-001 (resets each year)

### Change Numbers

Format: `CHG-YYYY-NNN`

- CHG-2025-001
- CHG-2025-002
- CHG-2026-001 (resets each year)

## Database Schema Details

### Configuration Items

**Key Fields:**
- `ci_number` - Auto-generated unique identifier
- `type` - Hardware, Software, Licentie, Overig
- `status` - In gebruik, In voorraad, Defect, Afgeschreven, Onderhoud
- `owner_id` - Links to users table
- `purchase_price` - For financial tracking
- `warranty_expiry` - For warranty management

**Indexes:**
- Primary key on `ci_id`
- Unique keys on `ci_number` and `serial_number`
- Indexes on `status`, `type`, `owner_id`, `warranty_expiry`

### Changes

**Key Fields:**
- `change_number` - Auto-generated unique identifier
- `type` - Feature, Patch, Hardware, Software, Netwerk, Infrastructuur, Overig
- `priority` - Laag, Normaal, Hoog, Urgent
- `impact` - Laag, Middel, Hoog
- `status` - Nieuw, In beoordeling, Goedgekeurd, Ingepland, In uitvoering, Geïmplementeerd, Afgewezen, Geannuleerd

**Workflow:**
```
Nieuw → In beoordeling → Goedgekeurd → Ingepland → In uitvoering → Geïmplementeerd
  ↓                          ↓
Geannuleerd              Afgewezen
```

**Indexes:**
- Primary key on `change_id`
- Unique key on `change_number`
- Indexes on `status`, `type`, `priority`, `planned_start_date`

## Relationships

### Ticket ↔ CI
- Many-to-many relationship
- Relation types: affects, caused_by, resolved_by, related_to

### Ticket ↔ Change
- Many-to-many relationship
- Relation types: caused_by, resolved_by, related_to

### Change ↔ CI
- Many-to-many relationship
- Relation types: affects, modifies, replaces, uses, related_to

## File Storage

### CI Attachments
- Location: `/uploads/ci_attachments/`
- Protected by .htaccess
- Metadata stored in `ci_attachments` table

### Change Attachments
- Location: `/uploads/change_attachments/`
- Protected by .htaccess
- Metadata stored in `change_attachments` table

## Audit Trail

All changes are logged in history tables:

### CI History
- Tracks all field changes
- Records: user, timestamp, field, old value, new value
- Immutable (no delete/update)

### Change History
- Tracks all status transitions
- Records: user, timestamp, old status, new status, comments
- Immutable (no delete/update)

## Rollback

If you need to rollback the migration:

```sql
-- Drop all CI & Change Management tables
DROP TABLE IF EXISTS ticket_ci_relations;
DROP TABLE IF EXISTS ticket_change_relations;
DROP TABLE IF EXISTS change_ci_relations;
DROP TABLE IF EXISTS ci_attachments;
DROP TABLE IF EXISTS ci_history;
DROP TABLE IF EXISTS change_attachments;
DROP TABLE IF EXISTS change_history;
DROP TABLE IF EXISTS configuration_items;
DROP TABLE IF EXISTS changes;
DROP TABLE IF EXISTS sequences;

-- Drop views
DROP VIEW IF EXISTS v_active_cis;
DROP VIEW IF EXISTS v_changes_overview;
DROP VIEW IF EXISTS v_ci_financial_summary;
DROP VIEW IF EXISTS v_expiring_warranties;
DROP VIEW IF EXISTS v_change_statistics;

-- Restore from backup
mysql -u root -p ticketportaal < backup_before_ci_change_YYYYMMDD.sql
```

## Troubleshooting

### Migration Fails with "Table already exists"

This is normal if you're running the migration multiple times. The script uses `CREATE TABLE IF NOT EXISTS` to prevent errors.

### No Admin User Found

The sample data requires at least one admin user. Create an admin user first:

```sql
INSERT INTO users (email, password, first_name, last_name, role) 
VALUES ('admin@example.com', '$2y$10$...', 'Admin', 'User', 'admin');
```

### Trigger Creation Fails

Ensure your MySQL user has `TRIGGER` privilege:

```sql
GRANT TRIGGER ON ticketportaal.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

### View Creation Fails

Ensure your MySQL user has `CREATE VIEW` privilege:

```sql
GRANT CREATE VIEW ON ticketportaal.* TO 'your_user'@'localhost';
FLUSH PRIVILEGES;
```

## Next Steps

After successful migration:

1. **Delete migration file** - `run_ci_change_migration.php`
2. **Implement CI Management classes** - Task 2 in implementation plan
3. **Implement Change Management classes** - Task 3 in implementation plan
4. **Create UI pages** - Tasks 4 and 5 in implementation plan
5. **Integrate with ticket system** - Task 7 in implementation plan

## Support

For issues or questions:
- Review the design document: `.kiro/specs/ci-change-management/design.md`
- Review the requirements: `.kiro/specs/ci-change-management/requirements.md`
- Check the implementation tasks: `.kiro/specs/ci-change-management/tasks.md`

## Version History

- **v1.0** (2025-10-20) - Initial migration script
  - 10 tables
  - 2 triggers
  - 5 views
  - Sample data support
