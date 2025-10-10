# Requirements Document

## Introduction

The ICT Ticketportaal is a web-based ticket management system designed for Kruit & Kramer to professionally manage ICT support requests. The system will replace the current email and phone-based approach with a structured ticketing system that includes dual access methods (web portal and email integration), a knowledge base, and comprehensive reporting capabilities. The platform will serve three user roles: end users submitting tickets, agents handling support requests, and administrators managing the system.

## Requirements

### Requirement 1: User Authentication and Authorization

**User Story:** As a system user, I want to securely register and log in to the portal, so that I can access role-appropriate features and my personal ticket information.

#### Acceptance Criteria

1. WHEN a new user registers THEN the system SHALL create an account with email, password (bcrypt hashed), first name, last name, and department
2. WHEN a user logs in with valid credentials THEN the system SHALL create a session and redirect to the appropriate dashboard based on role (user/agent/admin)
3. WHEN a user enters invalid credentials THEN the system SHALL display an error message and prevent access
4. WHEN a user requests password reset THEN the system SHALL send a secure reset link via email
5. WHEN a session is inactive for 30 minutes THEN the system SHALL automatically log out the user
6. IF a user has role 'admin' THEN the system SHALL grant access to admin panel features
7. IF a user has role 'agent' THEN the system SHALL grant access to agent dashboard and ticket assignment features
8. IF a user has role 'user' THEN the system SHALL restrict access to only their own tickets and public knowledge base

### Requirement 2: Ticket Creation via Web Portal

**User Story:** As a registered user, I want to create support tickets through the web interface, so that I can report ICT issues and track their resolution.

#### Acceptance Criteria

1. WHEN a logged-in user accesses the ticket creation form THEN the system SHALL display fields for title, description, category, and priority
2. WHEN a user submits a ticket THEN the system SHALL generate a unique ticket number in format KK-YYYY-XXXX
3. WHEN a ticket is created THEN the system SHALL set status to 'open' and record the creation timestamp
4. WHEN a user selects a category THEN the system SHALL apply the default priority for that category
5. WHEN a user uploads a file attachment THEN the system SHALL validate file type and size (max 10MB) and store securely
6. WHEN a ticket is successfully created THEN the system SHALL send a confirmation email to the user with the ticket number
7. IF no category is selected THEN the system SHALL prevent ticket submission and display a validation error

### Requirement 3: Ticket Creation via Email

**User Story:** As a user, I want to create tickets by sending emails to ict@kruit-en-kramer.nl, so that I can report issues without logging into the portal.

#### Acceptance Criteria

1. WHEN an email is received at ict@kruit-en-kramer.nl THEN the system SHALL parse the email and create a ticket automatically
2. WHEN creating a ticket from email THEN the system SHALL use the email subject as the ticket title and body as description
3. WHEN the sender email matches an existing user THEN the system SHALL associate the ticket with that user account
4. IF the sender email is not registered THEN the system SHALL create a basic user account with the email address
5. WHEN a ticket is created from email THEN the system SHALL send an auto-reply with the generated ticket number
6. WHEN a ticket is created from email THEN the system SHALL set the source field to 'email'
7. WHEN email attachments are present THEN the system SHALL extract and store them with the ticket

### Requirement 4: Ticket Management for Agents

**User Story:** As an ICT agent, I want to view, assign, and update tickets, so that I can efficiently manage and resolve support requests.

#### Acceptance Criteria

1. WHEN an agent accesses the dashboard THEN the system SHALL display all tickets with filters for status, priority, category, and date range
2. WHEN an agent assigns a ticket to themselves or another agent THEN the system SHALL update the assigned_agent_id and send notification email
3. WHEN an agent updates ticket status THEN the system SHALL record the timestamp and update the updated_at field
4. WHEN an agent marks a ticket as 'resolved' THEN the system SHALL record the resolved_at timestamp and require resolution text
5. WHEN an agent adds a comment THEN the system SHALL allow marking it as internal (visible only to agents) or public (visible to user)
6. WHEN a ticket status changes THEN the system SHALL send an email notification to the ticket creator
7. IF a ticket exceeds its SLA hours without resolution THEN the system SHALL flag it as overdue in the agent dashboard

### Requirement 5: User Ticket Portal

**User Story:** As a user, I want to view my submitted tickets and their status, so that I can track the progress of my support requests.

#### Acceptance Criteria

1. WHEN a user accesses their dashboard THEN the system SHALL display only tickets they created
2. WHEN a user views a ticket detail THEN the system SHALL show all public comments and status history
3. WHEN a user adds a comment to their ticket THEN the system SHALL notify the assigned agent via email
4. WHEN a ticket is marked as 'resolved' THEN the system SHALL prompt the user to provide a satisfaction rating (1-5 stars)
5. WHEN a user submits a satisfaction rating THEN the system SHALL store it and mark the ticket as 'closed'
6. IF no agent is assigned to a ticket THEN the system SHALL display "Awaiting assignment" status to the user

### Requirement 6: Knowledge Base System

**User Story:** As a user, I want to search and browse a knowledge base of common solutions, so that I can resolve issues independently without creating tickets.

#### Acceptance Criteria

1. WHEN a user accesses the knowledge base THEN the system SHALL display published articles organized by category
2. WHEN a user searches the knowledge base THEN the system SHALL return articles matching the search term in title, content, or tags
3. WHEN a user views a knowledge base article THEN the system SHALL increment the view counter
4. WHEN an agent or admin creates a knowledge base article THEN the system SHALL allow setting title, content, category, tags, and published status
5. IF an article is not published THEN the system SHALL only display it to agents and admins
6. WHEN an agent views the knowledge base THEN the system SHALL also display internal articles not visible to regular users

### Requirement 7: Category Management

**User Story:** As an administrator, I want to manage ticket categories with default priorities and SLA times, so that tickets are properly classified and prioritized.

#### Acceptance Criteria

1. WHEN an admin creates a category THEN the system SHALL require name, description, default priority, and SLA hours
2. WHEN a category is created THEN the system SHALL make it available in ticket creation forms
3. WHEN an admin deactivates a category THEN the system SHALL hide it from new ticket forms but preserve existing ticket associations
4. WHEN a ticket is assigned a category THEN the system SHALL apply the category's default priority unless manually overridden
5. WHEN calculating SLA compliance THEN the system SHALL use the category's SLA hours from ticket creation time

### Requirement 8: Email Notifications

**User Story:** As a system user, I want to receive email notifications for ticket events, so that I stay informed about ticket progress without constantly checking the portal.

#### Acceptance Criteria

1. WHEN a user creates a ticket THEN the system SHALL send a confirmation email with the ticket number
2. WHEN a ticket is assigned to an agent THEN the system SHALL send notification emails to both the agent and the user
3. WHEN a ticket status changes THEN the system SHALL send an email to the ticket creator with the new status
4. WHEN a comment is added to a ticket THEN the system SHALL send an email to the ticket creator and assigned agent
5. WHEN a ticket is resolved THEN the system SHALL send an email to the user with the resolution details
6. IF a comment is marked as internal THEN the system SHALL NOT send notification to the user

### Requirement 9: Reporting and Analytics

**User Story:** As an administrator, I want to view reports and statistics about ticket volume and performance, so that I can monitor service quality and identify improvement areas.

#### Acceptance Criteria

1. WHEN an admin accesses the reports dashboard THEN the system SHALL display ticket volume by period (day/week/month)
2. WHEN viewing reports THEN the system SHALL calculate and display average resolution time per category
3. WHEN viewing agent performance THEN the system SHALL show tickets resolved per agent and average resolution time
4. WHEN viewing satisfaction metrics THEN the system SHALL display average satisfaction rating and distribution
5. WHEN viewing category analysis THEN the system SHALL show ticket count and average resolution time per category
6. WHEN an admin selects a date range THEN the system SHALL filter all reports to that period

### Requirement 10: User Management

**User Story:** As an administrator, I want to manage user accounts and roles, so that I can control system access and assign appropriate permissions.

#### Acceptance Criteria

1. WHEN an admin views the user list THEN the system SHALL display all users with their role, department, and active status
2. WHEN an admin creates a user THEN the system SHALL require email, password, first name, last name, and role
3. WHEN an admin changes a user's role THEN the system SHALL immediately apply the new permissions
4. WHEN an admin deactivates a user THEN the system SHALL prevent login but preserve their ticket history
5. WHEN an admin reactivates a user THEN the system SHALL restore login access
6. IF an admin attempts to delete their own admin account THEN the system SHALL prevent the action

### Requirement 11: Security and Data Protection

**User Story:** As a system administrator, I want the system to implement security best practices, so that user data and system integrity are protected.

#### Acceptance Criteria

1. WHEN a user creates a password THEN the system SHALL hash it using bcrypt before storage
2. WHEN processing form submissions THEN the system SHALL validate and sanitize all inputs to prevent SQL injection and XSS
3. WHEN executing database queries THEN the system SHALL use prepared statements with parameter binding
4. WHEN a user session is created THEN the system SHALL generate a secure session token
5. WHEN sensitive operations are performed THEN the system SHALL implement CSRF token validation
6. WHEN file uploads are processed THEN the system SHALL validate file types and scan for malicious content
7. IF multiple failed login attempts occur (5 within 15 minutes) THEN the system SHALL temporarily lock the account for 30 minutes

### Requirement 12: System Performance and Reliability

**User Story:** As a system user, I want the portal to load quickly and be available when needed, so that I can efficiently submit and manage tickets.

#### Acceptance Criteria

1. WHEN a user loads any page THEN the system SHALL render it within 2 seconds under normal load
2. WHEN the system is operational THEN it SHALL maintain 99.9% uptime
3. WHEN database queries are executed THEN the system SHALL use appropriate indexes for optimal performance
4. WHEN the knowledge base is accessed THEN the system SHALL cache frequently accessed articles
5. WHEN the system experiences high load THEN it SHALL maintain responsive performance for up to 100 concurrent users
