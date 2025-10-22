# Production-Ready Application Implementation Plan - Local Development

## Overview

This implementation plan focuses on making the ICT Ticketportaal production-ready on the local XAMPP environment. The goal is to complete all features, fix bugs, optimize performance, and ensure the application is fully tested before production deployment.

## Phase 1: Complete Missing Features

- [x] 1. Implement ticket comments system



  - [x] 1.1 Add comment methods to Ticket.php class


    - Implement addComment() method with ticket_id, user_id, comment, is_internal parameters
    - Implement getComments() method with includeInternal flag for role-based filtering
    - Update ticket updated_at timestamp when comment is added
    - Add error handling and logging
    - _Requirements: 1.2, 2.1_

  - [x] 1.2 Create comment UI on ticket detail pages

    - Add comment form on user/ticket_detail.php (public comments only)
    - Add comment form on agent/ticket_detail.php (with internal checkbox)
    - Display comments with user info, timestamp, and internal badge
    - Add CSRF token to comment forms
    - Style comments with Bootstrap cards
    - _Requirements: 1.2, 3.4_

  - [x] 1.3 Integrate comments with email notifications

    - Send email notification when comment is added (skip internal comments for users)
    - Include comment text in notification email
    - Add link to ticket in email
    - _Requirements: 8.4_

- [x] 2. Complete and test CI Management module





  - [x] 2.1 Test CI CRUD operations


    - Test creating new CI with all fields
    - Test editing existing CI
    - Test CI number generation (CI-YYYY-XXXX format)
    - Test CI status changes
    - Test CI deletion/archiving
    - Fix any bugs found
    - _Requirements: 12.1, 12.2, 12.3_

  - [x] 2.2 Test CI history logging

    - Verify changes are logged to ci_history table
    - Test viewing CI history on detail page
    - Verify user info is captured in history
    - _Requirements: 12.3_

  - [x] 2.3 Test CI-Ticket relationships

    - Test linking CI to ticket
    - Test viewing linked tickets on CI detail page
    - Test viewing linked CI on ticket detail page
    - _Requirements: 12.4_

- [x] 3. Complete and test Change Management module




  - [ ] 3.1 Test Change CRUD operations
    - Test creating new Change with all tabs
    - Test editing existing Change
    - Test Change number generation (CHG-YYYY-XXXX format)
    - Test Change status workflow
    - Fix any bugs found
    - _Requirements: 12.5, 12.6, 12.7_

  - [ ] 3.2 Test Change history logging
    - Verify status changes are logged to change_history table
    - Test viewing Change history on detail page
    - Verify approval/rejection is logged
    - _Requirements: 12.7_

  - [ ] 3.3 Test Change-CI and Change-Ticket relationships
    - Test linking CI to Change
    - Test linking Ticket to Change
    - Test viewing relationships on all detail pages
    - _Requirements: 12.8, 12.9_

  - [ ] 3.4 Test Change report generation
    - Test generating Change report PDF
    - Verify all sections are included
    - Test report with different Change statuses
    - _Requirements: 12.10_

- [ ] 4. Complete email-to-ticket functionality
  - [ ] 4.1 Test email parsing
    - Send test emails to configured mailbox
    - Verify subject becomes ticket title
    - Verify body becomes ticket description
    - Verify sender is matched to user or new user created
    - Test with email attachments
    - _Requirements: 8.6, 8.7, 8.8_

  - [ ] 4.2 Test email processing script
    - Run email_to_ticket.php manually
    - Verify tickets are created from emails
    - Verify auto-reply is sent with ticket number
    - Verify emails are marked as read
    - Check error logging
    - _Requirements: 8.6, 8.7_


## Phase 2: Bug Fixes and Error Handling

- [ ] 5. Comprehensive functional testing
  - [ ] 5.1 Test user registration and authentication
    - Test registration with valid data
    - Test registration with invalid data (validation errors)
    - Test login with correct credentials
    - Test login with incorrect credentials
    - Test password reset flow
    - Test session timeout
    - Fix any bugs found
    - _Requirements: 2.1, 2.5, 6.1_

  - [ ] 5.2 Test ticket workflows
    - Test ticket creation with all field combinations
    - Test ticket viewing and filtering
    - Test ticket assignment to agents
    - Test ticket status updates
    - Test ticket resolution with resolution text
    - Test satisfaction rating
    - Fix any bugs found
    - _Requirements: 2.1, 6.1_

  - [ ] 5.3 Test file upload functionality
    - Test uploading valid file types
    - Test uploading invalid file types (should reject)
    - Test uploading oversized files (should reject)
    - Test downloading attachments
    - Test file storage and permissions
    - Fix any bugs found
    - _Requirements: 2.3, 6.1_

  - [ ] 5.4 Test email notifications
    - Test ticket creation confirmation email
    - Test ticket assignment notification
    - Test status change notification
    - Test comment notification
    - Test resolution notification
    - Verify email templates render correctly
    - Fix any bugs found
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_

  - [ ] 5.5 Test knowledge base
    - Test article search functionality
    - Test article viewing and view counter
    - Test article creation (admin/agent)
    - Test article publishing/unpublishing
    - Test category filtering
    - Fix any bugs found
    - _Requirements: 1.7, 6.1_

  - [ ] 5.6 Test admin functions
    - Test user management (create, edit, deactivate)
    - Test category management (create, edit, deactivate)
    - Test reports generation
    - Test all admin dashboard features
    - Fix any bugs found
    - _Requirements: 6.1_

- [ ] 6. Improve error handling
  - [ ] 6.1 Add validation error messages to all forms
    - Review all forms for proper validation
    - Add inline error messages next to fields
    - Use Bootstrap validation classes (is-invalid, invalid-feedback)
    - Preserve form data on validation failure
    - _Requirements: 2.1, 9.2_

  - [ ] 6.2 Implement centralized error logging
    - Create logError() function in includes/functions.php
    - Add error logging to all try-catch blocks
    - Include context (user_id, IP, URL) in logs
    - Ensure logs directory is writable
    - _Requirements: 2.2, 2.4, 5.7_

  - [ ] 6.3 Add user-friendly error messages
    - Replace technical error messages with user-friendly ones
    - Never expose database structure or file paths
    - Add generic "An error occurred" message with log reference
    - Test error messages in production mode
    - _Requirements: 2.6, 2.7_

  - [ ] 6.4 Handle edge cases
    - Test with empty database
    - Test with missing configuration files
    - Test with invalid database credentials
    - Test with missing file permissions
    - Add graceful error handling for all edge cases
    - _Requirements: 2.2, 6.4_

## Phase 3: Security Hardening

- [ ] 7. Audit and improve input validation
  - [ ] 7.1 Review all user inputs for sanitization
    - Audit all $_POST, $_GET, $_FILES usage
    - Ensure htmlspecialchars() is used on all output
    - Use filter_var() for email validation
    - Use trim() to remove whitespace
    - _Requirements: 3.2, 3.9_

  - [ ] 7.2 Verify CSRF protection on all forms
    - Audit all forms for CSRF token
    - Verify CSRF validation in all POST handlers
    - Add CSRF token generation to session
    - Test CSRF protection by removing token
    - _Requirements: 3.4_

  - [ ] 7.3 Audit file upload security
    - Verify file type validation (MIME type and extension)
    - Verify file size validation
    - Test uploading malicious files (PHP, executable)
    - Ensure uploaded files are stored with random names
    - Verify uploads directory is outside web root or protected
    - _Requirements: 3.5_

  - [ ] 7.4 Review database query security
    - Audit all SQL queries for prepared statements
    - Ensure no string concatenation in SQL
    - Test SQL injection attempts
    - Verify all queries use Database class methods
    - _Requirements: 3.3, 3.8_

  - [ ] 7.5 Implement security headers
    - Add X-Frame-Options: SAMEORIGIN
    - Add X-Content-Type-Options: nosniff
    - Add X-XSS-Protection: 1; mode=block
    - Add Content-Security-Policy header
    - Add Referrer-Policy header
    - Test headers with browser developer tools
    - _Requirements: 3.2_

  - [ ] 7.6 Review session security
    - Verify session.cookie_httponly is enabled
    - Verify session.cookie_secure is enabled (for HTTPS)
    - Verify session.use_strict_mode is enabled
    - Verify session regeneration on login
    - Test session timeout (30 minutes)
    - _Requirements: 3.6_

  - [ ] 7.7 Test authentication and authorization
    - Test accessing admin pages as user (should deny)
    - Test accessing agent pages as user (should deny)
    - Test accessing other users' tickets (should deny)
    - Verify role-based access control works correctly
    - _Requirements: 3.7_

## Phase 4: Performance Optimization

- [ ] 8. Database optimization
  - [ ] 8.1 Review and add missing indexes
    - Verify all foreign keys have indexes
    - Add indexes on frequently queried columns (status, created_at, email)
    - Add composite indexes where appropriate
    - Test query performance with EXPLAIN
    - _Requirements: 4.2, 7.2_

  - [ ] 8.2 Optimize slow queries
    - Identify slow queries using query log or profiling
    - Rewrite N+1 queries to use JOINs
    - Add indexes to improve query performance
    - Test query performance before and after optimization
    - _Requirements: 4.3, 4.7, 7.4_

  - [ ] 8.3 Implement pagination
    - Add pagination to ticket lists (25-50 items per page)
    - Add pagination to user lists
    - Add pagination to knowledge base articles
    - Add pagination to CI and Change lists
    - Test pagination with large datasets
    - _Requirements: 4.6_

- [ ] 9. Frontend optimization
  - [ ] 9.1 Optimize static assets
    - Minify CSS files
    - Minify JavaScript files
    - Optimize images (compress, resize)
    - Enable browser caching for static assets
    - _Requirements: 4.4, 4.5_

  - [ ] 9.2 Test page load times
    - Measure page load times for all major pages
    - Identify pages loading slower than 2 seconds
    - Optimize slow pages
    - Test with browser developer tools (Network tab)
    - _Requirements: 4.1_

  - [ ] 9.3 Test with concurrent users
    - Simulate 10-20 concurrent users
    - Monitor server resource usage (CPU, RAM)
    - Identify bottlenecks
    - Optimize as needed
    - _Requirements: 6.5_

## Phase 5: Code Quality and Maintainability

- [ ] 10. Code review and refactoring
  - [ ] 10.1 Review code for consistency
    - Ensure consistent coding style (indentation, naming)
    - Ensure consistent error handling patterns
    - Ensure consistent use of Database class methods
    - Refactor duplicate code into functions
    - _Requirements: 5.1, 5.5, 5.6_

  - [ ] 10.2 Add PHPDoc comments
    - Add PHPDoc comments to all class methods
    - Include @param and @return tags
    - Add class-level documentation
    - Document complex logic with inline comments
    - _Requirements: 5.2, 5.3_

  - [ ] 10.3 Review configuration management
    - Ensure all settings are in config files
    - Remove hardcoded values from code
    - Create .example.php files for all config files
    - Document all configuration options
    - _Requirements: 5.4, 10.1, 10.2, 10.3, 10.4, 10.7_

  - [ ] 10.4 Implement environment detection
    - Add ENVIRONMENT constant (development/production)
    - Set DEBUG_MODE based on environment
    - Configure error display based on environment
    - Test in both development and production modes
    - _Requirements: 10.5, 10.6_

## Phase 6: UI/UX Polish

- [ ] 11. Improve user interface
  - [ ] 11.1 Standardize form styling
    - Ensure all forms use Bootstrap classes consistently
    - Add clear labels and placeholders
    - Style validation errors consistently
    - Add success messages after form submission
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [ ] 11.2 Add loading indicators
    - Add loading spinners for AJAX requests
    - Add loading state to buttons during submission
    - Disable forms during submission to prevent double-submit
    - _Requirements: 9.5_

  - [ ] 11.3 Improve table displays
    - Add sortable columns to tables
    - Add filters to tables
    - Style status and priority badges consistently
    - Add hover effects to table rows
    - _Requirements: 9.6_

  - [ ] 11.4 Test responsive design
    - Test on desktop (1920x1080, 1366x768)
    - Test on tablet (768x1024)
    - Test on mobile (375x667, 414x896)
    - Fix any layout issues
    - _Requirements: 9.8_

  - [ ] 11.5 Test browser compatibility
    - Test on Chrome (latest)
    - Test on Firefox (latest)
    - Test on Edge (latest)
    - Test on Safari (latest)
    - Fix any browser-specific issues
    - _Requirements: 6.4_

  - [ ] 11.6 Improve accessibility
    - Add ARIA labels to interactive elements
    - Ensure keyboard navigation works
    - Test with screen reader
    - Ensure sufficient color contrast
    - _Requirements: 9.9_

## Phase 7: Documentation

- [ ] 12. Complete technical documentation
  - [ ] 12.1 Update README.md
    - Add project overview and features
    - Add installation instructions for XAMPP
    - Add configuration guide
    - Add usage guide
    - Add troubleshooting section
    - _Requirements: 11.1_

  - [ ] 12.2 Create/update DEPLOYMENT.md
    - Document production deployment steps
    - Document server requirements
    - Document security hardening steps
    - Document backup procedures
    - _Requirements: 11.1_

  - [ ] 12.3 Document database schema
    - Create DATABASE_SCHEMA.md
    - Document all tables and relationships
    - Document indexes
    - Add ER diagram (optional)
    - _Requirements: 11.3_

  - [ ] 12.4 Document configuration options
    - Document all config files
    - Document all configuration options
    - Add examples for common scenarios
    - _Requirements: 11.4_

  - [ ] 12.5 Create troubleshooting guide
    - Document common issues and solutions
    - Add diagnostic commands
    - Add log file locations
    - _Requirements: 11.5_

  - [ ] 12.6 Document security features
    - Create SECURITY.md
    - Document security measures implemented
    - Document security best practices
    - Add vulnerability reporting process
    - _Requirements: 11.6_

  - [ ] 12.7 Create user documentation
    - Write user guide for end users
    - Document ticket creation process
    - Document knowledge base usage
    - Create FAQ section
    - _Requirements: 11.2_

## Phase 8: Final Testing and Validation

- [ ] 13. Comprehensive testing
  - [ ] 13.1 Functional testing checklist
    - Complete full functional test of all features
    - Test all user workflows end-to-end
    - Test all admin workflows
    - Test all agent workflows
    - Document any issues found
    - _Requirements: 6.1, 14.1_

  - [ ] 13.2 Security testing checklist
    - Test SQL injection protection
    - Test XSS protection
    - Test CSRF protection
    - Test file upload validation
    - Test authorization checks
    - Run security scanner (optional)
    - _Requirements: 6.2, 14.2_

  - [ ] 13.3 Performance testing checklist
    - Measure all page load times
    - Test with concurrent users
    - Monitor database query performance
    - Monitor server resource usage
    - _Requirements: 6.3, 14.3_

  - [ ] 13.4 Browser and device testing
    - Test on all major browsers
    - Test on mobile devices
    - Test responsive design
    - Fix any compatibility issues
    - _Requirements: 6.4_

- [ ] 14. User acceptance testing
  - [ ] 14.1 Prepare UAT environment
    - Set up clean database with test data
    - Create test user accounts (admin, agent, user)
    - Prepare test scenarios
    - _Requirements: 14.4_

  - [ ] 14.2 Conduct UAT with stakeholders
    - Have admin test all admin functions
    - Have agent test all agent functions
    - Have user test all user functions
    - Collect feedback
    - _Requirements: 14.4_

  - [ ] 14.3 Address UAT feedback
    - Prioritize feedback (critical, high, medium, low)
    - Fix critical issues immediately
    - Plan fixes for non-critical issues
    - Re-test after fixes
    - _Requirements: 14.4_

- [ ] 15. Final preparation for production
  - [ ] 15.1 Create production configuration templates
    - Create config/database.example.php with production structure
    - Create config/email.example.php with production structure
    - Update config/config.php with environment detection
    - Document configuration process
    - _Requirements: 10.7_

  - [ ] 15.2 Test backup and restore procedures
    - Create database backup script
    - Test database backup
    - Test database restore
    - Test file backup
    - Test file restore
    - Document procedures
    - _Requirements: 13.1, 13.2, 13.3, 13.4, 13.5_

  - [ ] 15.3 Create deployment checklist
    - List all pre-deployment tasks
    - List all deployment steps
    - List all post-deployment verification steps
    - List rollback procedures
    - _Requirements: 14.7_

  - [ ] 15.4 Final code review
    - Review all code for quality
    - Ensure all TODOs are resolved
    - Ensure all debug code is removed
    - Ensure all comments are accurate
    - _Requirements: 14.5_

  - [ ] 15.5 Final documentation review
    - Review all documentation for accuracy
    - Ensure all documentation is complete
    - Fix any errors or omissions
    - _Requirements: 14.6_

## Phase 9: Dynamic Category Fields System

- [ ] 16. Admin interface voor veld beheer
  - [x] 16.1 Create category fields overview page


    - Create admin/category_fields.php with overview of all categories
    - Display category name with count of fields per category
    - Add "Manage Fields" button per category
    - Style with Bootstrap cards/table
    - _Requirements: 1.1_

  - [x] 16.2 Create field management modal


    - Create modal for create/edit field functionality
    - Add form fields: field_name, field_type, field_label, is_required, field_order, options (JSON)
    - Support field types: text, textarea, select, radio, checkbox, date, number, email, tel
    - Add JSON editor for select/radio/checkbox options
    - Add validation for required fields
    - Add CSRF protection
    - _Requirements: 1.1_

  - [x] 16.3 Implement drag & drop field ordering


    - Add JavaScript drag & drop functionality using SortableJS or native HTML5
    - Update field_order in database via AJAX
    - Provide visual feedback during drag
    - _Requirements: 1.1_

  - [x] 16.4 Add field preview functionality


    - Create preview section in modal showing how field will render
    - Update preview in real-time as user configures field
    - Show different preview for each field type
    - _Requirements: 1.1_

  - [x] 16.5 Implement field CRUD operations


    - Add createField() method to CategoryField.php
    - Add updateField() method to CategoryField.php
    - Add deleteField() method to CategoryField.php
    - Add getFieldsByCategory() method to CategoryField.php
    - Add updateFieldOrder() method for drag & drop
    - Add proper error handling and logging
    - _Requirements: 1.1_

- [ ] 17. Dynamische veld rendering op ticket creation
  - [x] 17.1 Create JavaScript field loader


    - Create assets/js/dynamic-fields.js
    - Implement loadCategoryFields(categoryId) function
    - Fetch fields via AJAX from api/get_category_fields.php
    - Clear existing dynamic fields before loading new ones
    - _Requirements: 2.1_

  - [x] 17.2 Implement field renderers for all types


    - Create renderTextField() for text/email/tel/number
    - Create renderTextareaField() for textarea
    - Create renderSelectField() for select dropdowns
    - Create renderRadioField() for radio buttons
    - Create renderCheckboxField() for checkboxes
    - Create renderDateField() for date picker
    - Add proper labels, placeholders, and required indicators
    - _Requirements: 2.1_

  - [ ] 17.3 Add conditional field logic
    - Implement conditional field display based on other field values
    - Add data-condition attribute support (e.g., show field X when field Y = "yes")
    - Create showConditionalFields() JavaScript function
    - Test with example: "Licentie nodig?" → shows license details field
    - _Requirements: 2.1_

  - [ ] 17.4 Implement client-side validation
    - Add validation for required fields
    - Add validation for field types (email format, number range, etc.)
    - Show validation errors inline
    - Prevent form submission if validation fails
    - Style validation with Bootstrap classes
    - _Requirements: 2.1_

  - [ ] 17.5 Style dynamic fields with UX improvements
    - Apply consistent Bootstrap styling to all field types
    - Add icons for different field types
    - Add tooltips for field descriptions
    - Ensure responsive design for mobile
    - Add smooth transitions when fields appear/disappear
    - _Requirements: 2.1_

- [ ] 18. Opslaan van veld waarden
  - [x] 18.1 Create ticket_field_values table integration


    - Verify ticket_field_values table exists in database
    - Add saveFieldValues() method to Ticket.php
    - Save all dynamic field values when ticket is created
    - Link values to ticket_id and field_id
    - _Requirements: 3.1_

  - [x] 18.2 Integrate field values in ticket creation process


    - Update user/create_ticket.php to process dynamic field values
    - Extract field values from $_POST
    - Validate required fields server-side
    - Call saveFieldValues() after ticket creation
    - Handle validation errors gracefully
    - _Requirements: 3.1, 3.2_

  - [x] 18.3 Add server-side validation for field values


    - Validate required fields are not empty
    - Validate field types (email format, number range, date format)
    - Validate select/radio/checkbox values are in allowed options
    - Return validation errors to user
    - _Requirements: 3.2_

- [ ] 19. Tonen van veld waarden in ticket detail
  - [x] 19.1 Add getFieldValues() method to Ticket.php

    - Implement getFieldValues($ticketId) method
    - Join ticket_field_values with category_fields
    - Return array of field labels and values
    - Handle different field types appropriately
    - _Requirements: 4.1_

  - [x] 19.2 Display field values on user ticket detail page


    - Update user/ticket_detail.php to show dynamic field values
    - Create section "Additional Information" or "Details"
    - Display each field with label and formatted value
    - Style with Bootstrap cards or definition list
    - _Requirements: 4.1, 4.2_

  - [x] 19.3 Display field values on agent/admin ticket detail page


    - Update agent/ticket_detail.php to show dynamic field values
    - Display in same format as user view
    - Ensure all field types render correctly
    - _Requirements: 4.1, 4.2_

  - [x] 19.4 Format field values by type


    - Format date fields with Dutch format (dd-mm-yyyy)
    - Format checkbox fields as "Ja/Nee" or list of selected options
    - Format select/radio fields with readable labels
    - Format URLs as clickable links
    - Format email as mailto links
    - _Requirements: 4.2_

- [ ] 20. Template integratie met veld waarden
  - [ ] 20.1 Extend Template.php with field placeholder support
    - Add getFieldPlaceholders($ticketId) method
    - Generate placeholders like {field_hardware_type}, {field_license_needed}
    - Replace placeholders in template content
    - Handle missing field values gracefully
    - _Requirements: 5.1, 5.2_

  - [ ] 20.2 Update template editor with field placeholder hints
    - Update admin/templates.php to show available field placeholders
    - Display list of placeholders per category
    - Add "Insert Placeholder" button/dropdown
    - Show example: "Use {field_hardware_type} to insert Hardware Type value"
    - _Requirements: 5.1_

  - [ ] 20.3 Test template rendering with field values
    - Create test templates using field placeholders
    - Test with tickets that have field values
    - Test with tickets that don't have field values
    - Verify placeholders are replaced correctly
    - Verify missing values show as empty or default text
    - _Requirements: 5.1, 5.2_

- [ ] 21. API endpoints voor dynamic fields
  - [x] 21.1 Create api/get_category_fields.php


    - Accept category_id parameter
    - Return JSON array of fields for category
    - Include field configuration (type, options, required, order)
    - Add error handling for invalid category_id
    - _Requirements: 2.1_

  - [x] 21.2 Create api/update_field_order.php


    - Accept array of field_id with new order positions
    - Update field_order in database
    - Return success/error JSON response
    - Add CSRF protection
    - Restrict to admin role only
    - _Requirements: 1.1_

## Phase 10: Additional UX Improvements

- [ ] 22. Template system improvements
  - [ ] 22.1 Fix save template button functionality
    - Debug and fix template save functionality in admin/templates.php
    - Ensure templates are saved correctly to database
    - _Requirements: Template system_

  - [ ] 22.2 Integrate templates in ticket comment/resolution editor
    - Add template dropdown in comment form
    - Add template dropdown in resolution form
    - Insert template content into TinyMCE editor
    - _Requirements: Template system_

- [ ] 23. Ticket reopen functionality
  - [ ] 23.1 Add reopen button for agents/admins
    - Add "Reopen Ticket" button on resolved/closed tickets
    - Update ticket status back to "open"
    - Log status change in ticket history
    - _Requirements: Ticket management_

  - [ ] 23.2 Add reopen option for users
    - Add "Problem Not Solved" button for users on resolved tickets
    - Reopen ticket and notify assigned agent
    - Add comment explaining why ticket was reopened
    - _Requirements: Ticket management_

  - [ ] 23.3 Add notification system with bell icon
    - Create notifications table in database
    - Add bell icon in header with notification count
    - Show notifications dropdown
    - Mark notifications as read
    - Send notification when ticket is reopened
    - _Requirements: Notification system_

- [ ] 24. Authentication and homepage improvements
  - [x] 24.1 Remove public registration from homepage


    - Remove "Register" link from login page
    - Add message: "Voor toegang neem contact op met ICT afdeling (tel: 777)"
    - Only admins can create user accounts
    - _Requirements: Security_

- [ ] 25. UI/Navigation improvements
  - [x] 25.1 Remove duplicate header navigation




    - Remove top navigation links (Dashboard, Gebruikers, etc.)
    - Keep only sidebar navigation
    - Ensure all pages use sidebar consistently
    - _Requirements: UI consistency_

  - [ ] 25.2 Improve sidebar styling
    - Move sidebar completely to left edge
    - Increase main content area width
    - Improve sidebar visual design
    - _Requirements: UI improvements_

- [ ] 26. User profile and department management
  - [x] 26.1 Add user departments




    - Create departments table with fixed list: Financien, Service, Directie, Magazijn, Transport, Planning, ICT, Externe partij
    - Add department_id to users table
    - Update user creation/edit forms to include department selection
    - _Requirements: User management_

  - [x] 26.2 Create user profile page


    - Create user/profile.php page
    - Display user information (name, email, department, role)
    - Show user statistics (total tickets, open tickets, etc.)
    - _Requirements: User management_

  - [x] 26.3 Add password change functionality


    - Add "Change Password" section on profile page
    - Validate current password
    - Require password confirmation
    - Update password with bcrypt hashing
    - _Requirements: User management, Security_

  - [x] 26.4 Add profile link to sidebar



    - Add "Mijn Profiel" link to user sidebar
    - Add profile icon
    - _Requirements: Navigation_

## Success Criteria

The application is production-ready when:
- ✅ All planned features are complete and working
- ✅ No critical or high-priority bugs exist
- ✅ All security best practices are implemented
- ✅ Performance meets requirements (< 2s page load)
- ✅ Code is clean, documented, and maintainable
- ✅ Comprehensive testing is complete
- ✅ Documentation is complete and accurate
- ✅ Application works on all major browsers
- ✅ Database is optimized with proper indexes
- ✅ Configuration is flexible and documented
- ✅ Backup and restore procedures are tested
- ✅ User acceptance testing is passed
- ✅ Deployment checklist is ready
- ✅ Dynamic category fields system is fully functional
- ✅ All field types render correctly and save properly
- ✅ Template integration with field placeholders works correctly
