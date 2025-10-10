# Implementation Plan

- [x] 1. Set up project structure and database foundation





  - Create directory structure following the design specification (/assets, /config, /includes, /classes, /admin, /agent, /user, /api, /uploads)
  - Create database schema SQL file with all tables (users, tickets, categories, ticket_comments, ticket_attachments, knowledge_base, password_resets)
  - Add appropriate indexes to all tables for performance optimization
  - Create sample data SQL file with default categories and initial admin user
  - _Requirements: 12.3_

- [x] 2. Implement core database and configuration classes





  - [x] 2.1 Create Database.php class with PDO singleton pattern


    - Implement getInstance(), getConnection(), query(), fetchAll(), fetchOne(), execute(), and lastInsertId() methods
    - Add error handling with try-catch blocks and logging
    - Use prepared statements for all queries
    - _Requirements: 11.3, 12.3_

  - [x] 2.2 Create configuration files

    - Write config/database.php with database connection settings
    - Write config/config.php with application settings (site URL, session timeout, file upload limits)
    - Write config/email.php with SMTP settings for Collax integration
    - _Requirements: 12.1_
  - [x] 2.3 Create includes/functions.php with helper functions


    - Implement logError() function for centralized error logging
    - Implement sanitizeInput() and validateEmail() functions
    - Implement generateCSRFToken() and validateCSRFToken() functions
    - Implement redirectTo() and checkLogin() functions
    - _Requirements: 11.2, 11.5_

- [x] 3. Build user authentication system




  - [x] 3.1 Create User.php class with authentication methods


    - Implement register() method with bcrypt password hashing
    - Implement login() method with session creation and role-based redirection
    - Implement logout() method with session destruction
    - Implement getUserById() and updateUser() methods
    - _Requirements: 1.1, 1.2, 1.3, 11.1_
  - [x] 3.2 Create password reset functionality


    - Implement requestPasswordReset() method to generate secure tokens
    - Implement resetPassword() method to validate tokens and update passwords
    - Add password_resets table handling with expiration (1 hour)
    - _Requirements: 1.4_
  - [x] 3.3 Implement session management and security


    - Configure secure session settings (httponly, secure, strict mode)
    - Implement 30-minute inactivity timeout
    - Add session regeneration on login to prevent fixation
    - Implement failed login tracking and account locking (5 attempts in 15 minutes)
    - _Requirements: 1.5, 11.4, 11.7_
  - [x] 3.4 Create login.php page


    - Build login form with email and password fields
    - Add CSRF token protection
    - Implement input validation and error display
    - Add "Forgot Password" link
    - _Requirements: 1.2, 1.3, 11.5_
  - [x] 3.5 Create register.php page


    - Build registration form with email, password, first name, last name, and department fields
    - Implement password strength validation (minimum 8 characters, letters and numbers)
    - Add CSRF token protection and input sanitization
    - Display validation errors inline
    - _Requirements: 1.1, 11.2_
  - [x] 3.6 Create password reset pages


    - Build forgot password form (request_reset.php)
    - Build reset password form (reset_password.php) with token validation
    - _Requirements: 1.4_

- [x] 4. Implement role-based access control and authorization




  - [x] 4.1 Add permission checking methods to User.php


    - Implement checkPermission() method for role verification
    - Implement getUsersByRole() method to fetch users by role
    - _Requirements: 1.6, 1.7, 1.8_
  - [x] 4.2 Create authorization middleware


    - Implement requireLogin() function to protect pages
    - Implement requireRole() function to check user role
    - Add to includes/functions.php
    - _Requirements: 1.6, 1.7, 1.8_
  - [x] 4.3 Create role-specific dashboard redirects


    - Implement dashboard routing logic based on user role
    - Redirect admin to /admin/index.php
    - Redirect agent to /agent/dashboard.php
    - Redirect user to /user/dashboard.php
    - _Requirements: 1.2, 1.6, 1.7, 1.8_

- [x] 5. Build ticket management core functionality






  - [x] 5.1 Create Ticket.php class with CRUD operations

    - Implement createTicket() method with ticket number generation (KK-YYYY-XXXX format)
    - Implement getTicketById() method with JOIN to fetch related data
    - Implement getTicketsByUser() method for user portal
    - Implement getAllTickets() method with filtering support
    - _Requirements: 2.2, 2.3, 4.1, 5.1_

  - [x] 5.2 Implement ticket assignment and status management

    - Implement assignTicket() method to assign tickets to agents
    - Implement updateStatus() method with timestamp tracking
    - Add resolution text requirement when marking as 'resolved'
    - _Requirements: 4.2, 4.3, 4.4_


  - [ ] 5.3 Implement ticket comments system
    - Implement addComment() method with internal/public flag
    - Implement getComments() method with filtering for internal comments
    - _Requirements: 4.5, 5.3_
  - [x] 5.4 Implement file attachment handling

    - Implement addAttachment() method with file validation (type, size max 10MB)
    - Implement getAttachments() method
    - Store files with random filenames in /uploads/tickets/
    - _Requirements: 2.5, 11.6_
  - [x] 5.5 Implement SLA tracking

    - Implement checkSLA() method to calculate if ticket is within SLA
    - Implement getOverdueTickets() method for agent dashboard
    - Use category SLA hours for calculation
    - _Requirements: 4.7, 7.5_

- [x] 6. Create ticket web interface for users




  - [x] 6.1 Build user dashboard (user/dashboard.php)


    - Display ticket statistics (open, in progress, resolved)
    - Show recent tickets created by user
    - Add quick action buttons (create ticket, view KB)
    - _Requirements: 5.1_
  - [x] 6.2 Create ticket creation form (user/create_ticket.php)


    - Build form with title, description, category dropdown, priority selection
    - Implement file upload field with client-side validation
    - Add CSRF protection and input sanitization
    - Apply category default priority when category is selected
    - _Requirements: 2.1, 2.4, 2.5, 2.7_
  - [x] 6.3 Create user ticket list page (user/my_tickets.php)


    - Display all tickets created by logged-in user
    - Show ticket number, title, status, priority, created date
    - Add status badges with color coding
    - Implement sorting by date and status
    - _Requirements: 5.1_
  - [x] 6.4 Create ticket detail page for users (user/ticket_detail.php)


    - Display full ticket information
    - Show all public comments in chronological order
    - Add comment form for users to add comments
    - Display attachments with download links
    - Show satisfaction rating form when ticket is resolved
    - _Requirements: 5.2, 5.3, 5.4, 5.5_

- [x] 7. Create agent portal for ticket management




  - [x] 7.1 Build agent dashboard (agent/dashboard.php)


    - Display all tickets with status, priority, category columns
    - Implement filters for status, priority, category, date range
    - Show overdue tickets with visual indicator
    - Add quick assign and status update actions
    - _Requirements: 4.1, 4.7_


  - [x] 7.2 Create agent ticket detail page (agent/ticket_detail.php)





    - Display full ticket information with all comments (including internal)
    - Add ticket assignment dropdown to assign to agents
    - Add status update form with resolution text field
    - Add comment form with internal/public checkbox
    - Show SLA status and time remaining
    - _Requirements: 4.2, 4.3, 4.4, 4.5_
  - [x] 7.3 Create assigned tickets page (agent/my_tickets.php)





    - Display tickets assigned to logged-in agent
    - Show same filtering and sorting as dashboard
    - _Requirements: 4.1_

- [x] 8. Implement category management






  - [x] 8.1 Create category CRUD operations

    - Add createCategory(), updateCategory(), deleteCategory() methods to new Category.php class
    - Implement getCategories() and getCategoryById() methods
    - Add active/inactive status handling
    - _Requirements: 7.1, 7.3_
  - [x] 8.2 Build admin category management page (admin/categories.php)


    - Display list of all categories with name, default priority, SLA hours, active status
    - Add create category form with validation
    - Add edit and deactivate buttons
    - _Requirements: 7.1, 7.2, 7.3_
  - [x] 8.3 Integrate categories with ticket creation


    - Populate category dropdown in ticket creation forms
    - Apply category default priority when selected
    - Only show active categories in dropdowns
    - _Requirements: 2.4, 7.2, 7.4_

- [x] 9. Build email integration system




  - [x] 9.1 Create EmailHandler.php class for sending emails


    - Implement sendTicketConfirmation() method
    - Implement sendStatusUpdate() method
    - Implement sendAssignmentNotification() method
    - Implement sendCommentNotification() method
    - Implement sendResolutionNotification() method
    - Use PHP mail() function with SMTP configuration from config/email.php
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_
  - [x] 9.2 Integrate email notifications with ticket operations


    - Call sendTicketConfirmation() after ticket creation
    - Call sendAssignmentNotification() when ticket is assigned
    - Call sendStatusUpdate() when status changes
    - Call sendCommentNotification() when comment is added (skip if internal)
    - Call sendResolutionNotification() when ticket is resolved
    - _Requirements: 2.6, 8.1, 8.2, 8.3, 8.4, 8.5, 8.6_
  - [x] 9.3 Implement email parsing for ticket creation

    - Add parseEmail() method to EmailHandler.php to extract subject, body, sender, attachments
    - Implement createTicketFromEmail() method
    - Implement findOrCreateUser() method to match or create user accounts
    - Set ticket source to 'email'
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.6_
  - [x] 9.4 Create email processing cron script (email_to_ticket.php)


    - Connect to ict@kruit-en-kramer.nl mailbox using PHP IMAP functions
    - Process unread emails and create tickets
    - Send auto-reply with ticket number
    - Mark processed emails as read
    - Log processing errors
    - _Requirements: 3.1, 3.5, 3.7_

- [x] 10. Implement knowledge base system






  - [x] 10.1 Create KnowledgeBase.php class

    - Implement createArticle(), updateArticle(), deleteArticle() methods
    - Implement getArticleById(), getAllArticles(), getPublishedArticles() methods
    - Implement searchArticles() method with FULLTEXT search
    - Implement incrementViews() method
    - Implement publishArticle() and unpublishArticle() methods
    - _Requirements: 6.3, 6.4, 6.5_
  - [x] 10.2 Create public knowledge base page (knowledge_base.php)


    - Display published articles organized by category
    - Implement search functionality with search form
    - Show article list with title, category, view count
    - _Requirements: 6.1, 6.2_
  - [x] 10.3 Create knowledge base article view page (kb_article.php)


    - Display full article content
    - Increment view counter when article is viewed
    - Show related articles from same category
    - _Requirements: 6.3_
  - [x] 10.4 Create agent/admin KB management interface


    - Build admin/knowledge_base.php with article list including unpublished articles
    - Add create article form with title, content, category, tags, published status
    - Add edit and delete functionality
    - Show internal articles to agents and admins only
    - _Requirements: 6.4, 6.5, 6.6_

- [x] 11. Build admin user management




  - [x] 11.1 Add user management methods to User.php


    - Implement getAllUsers() method
    - Implement createUser() method for admin user creation
    - Implement updateUserRole() method
    - Implement deactivateUser() and reactivateUser() methods
    - Add validation to prevent admin from deleting their own account
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_
  - [x] 11.2 Create admin user management page (admin/users.php)


    - Display list of all users with email, name, role, department, active status
    - Add create user form
    - Add edit user modal with role change dropdown
    - Add activate/deactivate buttons
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5_

- [x] 12. Implement reporting and analytics




  - [x] 12.1 Create Report.php class with analytics methods


    - Implement getTicketVolumeByPeriod() method for ticket counts by day/week/month
    - Implement getAverageResolutionTimeByCategory() method
    - Implement getAgentPerformance() method with tickets resolved and avg resolution time
    - Implement getSatisfactionMetrics() method with average rating and distribution
    - Implement getCategoryAnalysis() method
    - Add date range filtering support to all methods
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

  - [x] 12.2 Create admin reports dashboard (admin/reports.php)

    - Display ticket volume chart by period
    - Show average resolution time per category
    - Display agent performance table
    - Show satisfaction rating metrics
    - Add date range filter form
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.6_

- [x] 13. Build shared UI components and styling





  - [x] 13.1 Create common header and navigation (includes/header.php)
    - Build responsive navbar with logo, user menu, notifications
    - Add role-based navigation links
    - Implement logout functionality
    - _Requirements: 12.1_

  - [x] 13.2 Create sidebar navigation (includes/sidebar.php)
    - Build collapsible sidebar with role-specific menu items
    - Add active state highlighting
    - _Requirements: 12.1_

  - [x] 13.3 Create footer (includes/footer.php)
    - Add copyright and version information
    - _Requirements: 12.1_
  - [x] 13.4 Implement Bootstrap 5 styling (assets/css/style.css)


    - Define color scheme (primary blue, success green, warning yellow, danger red)
    - Style forms with validation states
    - Create status badges and priority indicators
    - Implement responsive breakpoints (mobile < 768px, tablet 768-1024px, desktop > 1024px)
    - Add custom styles for ticket lists and dashboards
    - _Requirements: 12.1_

  - [x] 13.5 Add JavaScript functionality (assets/js/main.js)


    - Implement form validation
    - Add AJAX for ticket status updates
    - Implement file upload preview
    - Add confirmation dialogs for destructive actions
    - _Requirements: 12.1_

- [x] 14. Create landing page and public pages






  - [x] 14.1 Build index.php landing page

    - Display welcome message and system overview
    - Add login and register buttons
    - Show link to public knowledge base
    - _Requirements: 12.1_


  - [x] 14.2 Create logout.php handler





    - Destroy session and redirect to login page
    - _Requirements: 1.2_

- [x] 15. Implement security hardening




  - [x] 15.1 Add input validation and sanitization to all forms


    - Validate email format, required fields, field lengths
    - Sanitize all text inputs with htmlspecialchars()
    - Validate file uploads (type whitelist, size limit)
    - _Requirements: 11.2, 11.6_
  - [x] 15.2 Add CSRF protection to all forms


    - Generate CSRF tokens in session
    - Add hidden token fields to all forms
    - Validate tokens on form submission
    - _Requirements: 11.5_
  - [x] 15.3 Configure secure session settings


    - Set httponly, secure, and strict mode flags
    - Implement session timeout
    - _Requirements: 11.4_
  - [x] 15.4 Create .htaccess for Apache security


    - Disable directory listing
    - Enable HTTPS redirect
    - Set security headers (X-Frame-Options, X-XSS-Protection)
    - Protect sensitive directories
    - _Requirements: 11.2_

- [x] 16. Create database initialization and seed scripts





  - [x] 16.1 Create database schema script (database/schema.sql)


    - Include all CREATE TABLE statements
    - Add all indexes and foreign keys
    - _Requirements: 12.3_

  - [x] 16.2 Create seed data script (database/seed.sql)

    - Insert default categories (Hardware, Software, Network, Account, Other)
    - Create initial admin user (admin@kruit-en-kramer.nl)
    - Add sample knowledge base articles
    - _Requirements: 7.1_

- [x] 17. Set up deployment configuration






  - [x] 17.1 Create deployment documentation (DEPLOYMENT.md)

    - Document server requirements (PHP 7.4+, MySQL 5.7+, Apache/Nginx)
    - Provide installation steps
    - Document cron job setup for email processing
    - Include SSL certificate configuration
    - _Requirements: 12.2_

  - [x] 17.2 Create environment-specific config template

    - Create config/database.example.php
    - Create config/email.example.php
    - Add instructions for configuration
    - _Requirements: 12.1_
