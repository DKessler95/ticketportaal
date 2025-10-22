# CI Management Testing Guide

## Overview

This guide explains how to test the CI (Configuration Item) Management module for the ICT Ticketportaal. The tests verify CRUD operations, history logging, and ticket relationships.

## Prerequisites

Before running the tests, ensure:

1. **Database is set up**
   - The CI & Change Management migration has been run (`run_ci_change_migration.php`)
   - All required tables exist: `configuration_items`, `ci_history`, `ticket_ci_relations`

2. **Configuration files exist**
   - `config/database.php` is configured with correct database credentials
   - `config/config.php` exists with required constants

3. **User account**
   - You have an admin account to run the tests
   - You are logged in to the system

## Test Files

### 1. `test_ci_management.php` - CI CRUD Operations Test (Task 2.1)

Tests the following functionality:
- ✅ Create Configuration Item
- ✅ Read Configuration Item by ID
- ✅ CI Number Generation (CI-YYYY-XXXX format)
- ✅ Update Configuration Item
- ✅ CI History Logging
- ✅ Get All Configuration Items
- ✅ Delete Configuration Item

**How to run:**
1. Navigate to: `http://localhost/ticketportaal/test_ci_management.php`
2. Ensure you're logged in as admin
3. The test will automatically:
   - Create a test CI
   - Verify CI number format
   - Update the CI
   - Check history logging
   - Delete the test CI

**Expected Results:**
- All 7 tests should pass
- Test CI is created and deleted automatically
- History entries are logged for creation, updates, and deletion

### 2. `test_ci_ticket_relations.php` - CI-Ticket Relationships Test (Task 2.3)

Tests the following functionality:
- ✅ Link CI to Ticket
- ✅ View Linked Tickets on CI Detail
- ✅ View Linked CIs on Ticket Detail
- ✅ Unlink CI from Ticket

**How to run:**
1. Navigate to: `http://localhost/ticketportaal/test_ci_ticket_relations.php`
2. Ensure you're logged in as admin
3. Ensure at least one category exists in the database
4. The test will automatically:
   - Create a test CI
   - Create a test ticket
   - Link them together
   - Verify the relationship from both sides
   - Unlink them
   - Clean up test data

**Expected Results:**
- All 4 tests should pass
- Test CI is created and deleted automatically
- Test ticket is created but kept for reference
- Relationships are properly created and removed

## Task 2.2: CI History Logging

History logging is tested as part of `test_ci_management.php` (Test 5). The test verifies:

- ✅ History entries are created for CI creation
- ✅ History entries are created for CI updates
- ✅ History entries include user information
- ✅ History entries include field changes (old value → new value)
- ✅ Status changes are logged with action type 'status_changed'

**Verification:**
The test checks that:
1. At least 2 history entries exist (creation + status change)
2. Each entry includes user details (first_name, last_name)
3. Entries are ordered by timestamp (most recent first)

## Running All Tests

To run all CI Management tests in sequence:

1. **First, run the migration** (if not already done):
   ```
   http://localhost/ticketportaal/run_ci_change_migration.php
   ```

2. **Run CI CRUD tests** (Task 2.1 + 2.2):
   ```
   http://localhost/ticketportaal/test_ci_management.php
   ```

3. **Run CI-Ticket relationship tests** (Task 2.3):
   ```
   http://localhost/ticketportaal/test_ci_ticket_relations.php
   ```

## Troubleshooting

### "Access Denied" Error
- **Cause:** Not logged in or not an admin user
- **Solution:** Log in with an admin account first

### "Database connection failed"
- **Cause:** `config/database.php` doesn't exist or has wrong credentials
- **Solution:** Copy `config/database.example.php` to `config/database.php` and update credentials

### "Table 'configuration_items' doesn't exist"
- **Cause:** CI migration hasn't been run
- **Solution:** Run `run_ci_change_migration.php` first

### "No categories found in database"
- **Cause:** No categories exist for ticket creation
- **Solution:** Create at least one category in the admin panel first

### Tests fail with "Failed to generate CI number"
- **Cause:** Database permissions issue or trigger not created
- **Solution:** 
  1. Check database user has INSERT, SELECT permissions
  2. Verify the migration created the CI number generation logic
  3. Check `ci_number` column exists and is unique

### History logging tests fail
- **Cause:** `ci_history` table doesn't exist or foreign keys are broken
- **Solution:** 
  1. Verify `ci_history` table exists
  2. Check foreign key constraints are properly set up
  3. Re-run migration if necessary

## Test Data Cleanup

The test scripts automatically clean up most test data:

- **test_ci_management.php**: Deletes the test CI after all tests complete
- **test_ci_ticket_relations.php**: Deletes the test CI but keeps the test ticket

If tests fail mid-execution, you may need to manually clean up:

```sql
-- Find test CIs (they have "Test" in the name)
SELECT * FROM configuration_items WHERE name LIKE '%Test%';

-- Delete test CIs
DELETE FROM configuration_items WHERE name LIKE '%Test%';

-- Find test tickets
SELECT * FROM tickets WHERE title LIKE '%Test Ticket%';

-- Optionally delete test tickets
DELETE FROM tickets WHERE title LIKE '%Test Ticket%';
```

## Success Criteria

Task 2 is complete when:

- ✅ **Task 2.1**: All 7 CI CRUD tests pass
  - Create, Read, Update, Delete operations work
  - CI number generation follows CI-YYYY-XXXX format
  - Get all CIs returns results

- ✅ **Task 2.2**: CI history logging tests pass
  - History entries are created for all actions
  - User information is captured
  - Field changes are tracked

- ✅ **Task 2.3**: All 4 CI-Ticket relationship tests pass
  - CIs can be linked to tickets
  - Linked tickets are visible on CI detail
  - Linked CIs are visible on ticket detail
  - Relationships can be removed

## Next Steps

After all tests pass:

1. Mark Task 2.1, 2.2, and 2.3 as complete in `tasks.md`
2. Delete the test scripts (optional, for security):
   - `test_ci_management.php`
   - `test_ci_ticket_relations.php`
3. Proceed to Task 3: Complete and test Change Management module

## Notes

- These tests use the actual database, not mocks
- Tests create and delete real data
- Always run tests in a development environment first
- The ConfigurationItem class is located in `classes/ConfigurationItem.php`
- All test results are displayed in a user-friendly web interface

## Support

If you encounter issues:

1. Check the browser console for JavaScript errors
2. Check PHP error logs for server-side errors
3. Verify database connection with `test_db_connection.php`
4. Review the requirements in `.kiro/specs/production-ready-local/requirements.md`
5. Review the design in `.kiro/specs/production-ready-local/design.md`
