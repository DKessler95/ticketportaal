# Quick Start: Run CI Management Tests

## 🚀 Quick Test Instructions

### Step 1: Prerequisites Check

Before running tests, verify:

```bash
✅ XAMPP is running (Apache + MySQL)
✅ Database 'ticketportaal' exists
✅ CI migration has been run
✅ You have an admin account
```

### Step 2: Run CI Migration (if not done)

Navigate to:
```
http://localhost/ticketportaal/run_ci_change_migration.php
```

- Enter migration password (default: `migrate2025`)
- Check "Insert sample test data" (optional)
- Click "Run Migration"
- Wait for success message

### Step 3: Login as Admin

Navigate to:
```
http://localhost/ticketportaal/login.php
```

Login with admin credentials.

### Step 4: Run Test Suite 1 - CI CRUD Operations

Navigate to:
```
http://localhost/ticketportaal/test_ci_management.php
```

**Expected Results:**
- ✅ Test 1: Create Configuration Item - PASS
- ✅ Test 2: Read Configuration Item - PASS
- ✅ Test 3: CI Number Format - PASS
- ✅ Test 4: Update Configuration Item - PASS
- ✅ Test 5: CI History Logging - PASS
- ✅ Test 6: Get All Configuration Items - PASS
- ✅ Test 7: Delete Configuration Item - PASS

**Success Message:** "All Tests Passed! All 7 tests completed successfully."

### Step 5: Run Test Suite 2 - CI-Ticket Relationships

Navigate to:
```
http://localhost/ticketportaal/test_ci_ticket_relations.php
```

**Expected Results:**
- ✅ Test 1: Link CI to Ticket - PASS
- ✅ Test 2: View Linked Tickets on CI Detail - PASS
- ✅ Test 3: View Linked CIs on Ticket Detail - PASS
- ✅ Test 4: Unlink CI from Ticket - PASS

**Success Message:** "All Tests Passed! All 4 tests completed successfully."

## ✅ Success!

If all tests pass, Task 2 is complete! You can now:

1. Mark Task 2 as complete in your project tracker
2. Proceed to Task 3: Complete and test Change Management module
3. (Optional) Delete test scripts for security:
   - `test_ci_management.php`
   - `test_ci_ticket_relations.php`

## ❌ Troubleshooting

### "Access Denied"
**Problem:** Not logged in or not admin
**Solution:** Login with admin account first

### "Table 'configuration_items' doesn't exist"
**Problem:** Migration not run
**Solution:** Run `run_ci_change_migration.php` first

### "Database connection failed"
**Problem:** Database config missing or incorrect
**Solution:** 
1. Copy `config/database.example.php` to `config/database.php`
2. Update with your database credentials
3. Test with `test_db_connection.php`

### "No categories found in database"
**Problem:** No categories for ticket creation
**Solution:** Create at least one category in admin panel

### Tests fail with errors
**Problem:** Various issues
**Solution:** 
1. Check browser console for JavaScript errors
2. Check `logs/app.log` for PHP errors
3. Verify database tables exist
4. Check database user permissions

## 📚 More Information

- **Detailed Testing Guide:** `CI_TESTING_README.md`
- **Implementation Details:** `CI_IMPLEMENTATION_SUMMARY.md`
- **Task Completion Summary:** `TASK_2_COMPLETION_SUMMARY.md`

## 🎯 What Gets Tested

### Test Suite 1 (test_ci_management.php)
- CI creation with all fields
- CI retrieval by ID
- CI number format (CI-YYYY-XXXX)
- CI updates (status, location, notes)
- History logging (creation, updates, status changes)
- Listing all CIs
- CI deletion

### Test Suite 2 (test_ci_ticket_relations.php)
- Creating CI-Ticket links
- Viewing linked tickets from CI
- Viewing linked CIs from ticket
- Removing CI-Ticket links

## 💡 Tips

1. **Run tests in order** - Test Suite 1 before Test Suite 2
2. **Check all green** - All tests should show green "PASS" indicators
3. **Review details** - Click on test items to see detailed results
4. **Automatic cleanup** - Tests clean up their own data
5. **Safe to re-run** - You can run tests multiple times

## 🔒 Security Note

After testing in production:
- Delete test scripts (`test_ci_management.php`, `test_ci_ticket_relations.php`)
- Delete migration script (`run_ci_change_migration.php`)
- Keep only the ConfigurationItem class (`classes/ConfigurationItem.php`)

---

**Ready? Let's test!** 🚀

Start with Step 1 above and work through each step in order.
