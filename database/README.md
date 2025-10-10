# Database Setup Instructions

## Overview
This directory contains the database schema and seed data for the ICT Ticketportaal system.

## Files
- `schema.sql` - Complete database schema with all tables and indexes
- `seed.sql` - Initial data including categories and admin user

## Installation Steps

### 1. Create Database
```sql
CREATE DATABASE ticketportaal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Create Database User
```sql
CREATE USER 'ticketuser'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON ticketportaal.* TO 'ticketuser'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Import Schema
```bash
mysql -u ticketuser -p ticketportaal < schema.sql
```

### 4. Import Seed Data
```bash
mysql -u ticketuser -p ticketportaal < seed.sql
```

## Default Admin Account
After running the seed script, you can log in with:
- **Email**: admin@kruit-en-kramer.nl
- **Password**: Admin123!

**IMPORTANT**: Change this password immediately after first login!

## Database Tables

### users
Stores all system users (users, agents, admins) with authentication credentials.

### categories
Ticket and knowledge base categories with SLA settings.

### tickets
Core ticket information including status, priority, and assignments.

### ticket_comments
All comments on tickets (public and internal).

### ticket_attachments
File attachment metadata for tickets.

### knowledge_base
Help articles and documentation.

### password_resets
Temporary tokens for password reset functionality.

### sessions
Optional database session storage (can use file-based sessions instead).

## Performance Optimization
All tables include appropriate indexes for:
- Foreign key relationships
- Frequently queried columns (status, priority, dates)
- Search operations (FULLTEXT on knowledge base)
- Composite indexes for common query patterns

## Maintenance

### Cleanup Old Password Reset Tokens
```sql
DELETE FROM password_resets WHERE expires_at < NOW();
```

### Cleanup Old Sessions
```sql
DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 MINUTE);
```

### View Database Statistics
```sql
SELECT 
    table_name,
    table_rows,
    ROUND(data_length / 1024 / 1024, 2) AS data_size_mb,
    ROUND(index_length / 1024 / 1024, 2) AS index_size_mb
FROM information_schema.tables
WHERE table_schema = 'ticketportaal'
ORDER BY data_length DESC;
```
