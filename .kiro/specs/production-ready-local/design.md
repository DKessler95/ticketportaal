# Production-Ready Application Design - Local Development

## Overview

This design document outlines the approach to make the ICT Ticketportaal production-ready on the local XAMPP environment. The focus is on completing missing features, fixing bugs, optimizing performance, hardening security, and ensuring the application is fully tested and documented before production deployment.

## Current State Assessment

### What's Already Complete (Based on tasks.md)

✅ **Core Infrastructure:**
- Database schema and classes
- User authentication and authorization
- Session management
- Basic security (CSRF, password hashing)

✅ **Ticket System:**
- Ticket creation (web)
- Ticket viewing and filtering
- Ticket assignment
- Status management
- File attachments
- SLA tracking

✅ **User Interfaces:**
- User dashboard and portal
- Agent dashboard and portal
- Admin dashboard
- Login/registration pages

✅ **Additional Features:**
- Knowledge base system
- Category management
- Email integration (EmailHandler classes)
- Reporting system
- CI and Change Management (partially)

### What Needs Completion

❌ **Missing/Incomplete:**
- Ticket comments system (task 5.3 not completed)
- Full testing of all features
- Performance optimization
- Security hardening review
- Code quality improvements
- Documentation completion
- CI/Change Management testing
- Email-to-ticket testing
- Bug fixes from testing

## Architecture for Production-Ready Code

### Code Organization Principles

**1. Separation of Concerns:**
```
/classes/          → Business logic (models)
/includes/         → Shared UI components and utilities
/config/           → Configuration (no business logic)
/admin|agent|user/ → Controllers and views (role-specific)
/api/              → API endpoints (AJAX, webhooks)
```

**2. Configuration Management:**
```php
// Development mode detection
define('ENVIRONMENT', getenv('APP_ENV') ?: 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Error handling based on environment
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}
```

**3. Error Handling Strategy:**
```php
// Centralized error logging
function logError($context, $message, $data = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'context' => $context,
        'message' => $message,
        'data' => $data,
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'url' => $_SERVER['REQUEST_URI'] ?? null
    ];
    
    $logFile = __DIR__ . '/../logs/app.log';
    error_log(json_encode($logEntry) . PHP_EOL, 3, $logFile);
    
    // In production, also log to system error log
    if (!DEBUG_MODE) {
        error_log("[{$context}] {$message}");
    }
}
```

## Components to Complete/Improve

### 1. Ticket Comments System

**Current State:** Task 5.3 marked as incomplete

**Implementation Plan:**

**Database:** Already exists (ticket_comments table)

**Ticket.php additions:**
```php
public function addComment($ticketId, $userId, $comment, $isInternal = false) {
    try {
        $sql = "INSERT INTO ticket_comments (ticket_id, user_id, comment, is_internal) 
                VALUES (?, ?, ?, ?)";
        $this->db->execute($sql, [$ticketId, $userId, $comment, $isInternal]);
        
        // Update ticket updated_at timestamp
        $this->db->execute(
            "UPDATE tickets SET updated_at = NOW() WHERE ticket_id = ?",
            [$ticketId]
        );
        
        return true;
    } catch (Exception $e) {
        logError('Ticket::addComment', $e->getMessage(), [
            'ticket_id' => $ticketId,
            'user_id' => $userId
        ]);
        return false;
    }
}

public function getComments($ticketId, $includeInternal = false) {
    try {
        $sql = "SELECT tc.*, u.first_name, u.last_name, u.role 
                FROM ticket_comments tc
                JOIN users u ON tc.user_id = u.user_id
                WHERE tc.ticket_id = ?";
        
        if (!$includeInternal) {
            $sql .= " AND tc.is_internal = 0";
        }
        
        $sql .= " ORDER BY tc.created_at ASC";
        
        return $this->db->fetchAll($sql, [$ticketId]);
    } catch (Exception $e) {
        logError('Ticket::getComments', $e->getMessage(), [
            'ticket_id' => $ticketId
        ]);
        return [];
    }
}
```

**UI Implementation:**
- Add comment form on ticket detail pages
- Display comments with user info and timestamp
- Show "Internal" badge for internal comments (agents/admins only)
- Add AJAX for real-time comment posting (optional)

### 2. Security Hardening Checklist

**Input Validation:**
```php
// Centralized validation functions in includes/functions.php

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateFileUpload($file, $allowedTypes, $maxSize) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'No file uploaded';
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds maximum allowed (' . 
                    formatBytes($maxSize) . ')';
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = 'File type not allowed';
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 
                          'jpg', 'jpeg', 'png', 'gif', 'txt'];
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = 'File extension not allowed';
    }
    
    return $errors;
}
```

**CSRF Protection:**
```php
// Ensure all forms have CSRF tokens
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Add to all forms:
// <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

// Validate in all POST handlers:
// if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
//     die('CSRF validation failed');
// }
```

**SQL Injection Prevention:**
- Audit all database queries
- Ensure all use prepared statements
- No string concatenation in SQL

**XSS Prevention:**
- Use htmlspecialchars() on all output
- Implement Content-Security-Policy header

### 3. Performance Optimization

**Database Indexing:**
```sql
-- Verify these indexes exist (should be in schema.sql)
CREATE INDEX idx_tickets_status_priority ON tickets(status, priority);
CREATE INDEX idx_tickets_assigned_status ON tickets(assigned_agent_id, status);
CREATE INDEX idx_tickets_created_at ON tickets(created_at);
CREATE INDEX idx_ticket_comments_ticket_id ON ticket_comments(ticket_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);

-- Add if missing
ALTER TABLE tickets ADD INDEX idx_user_status (user_id, status);
ALTER TABLE knowledge_base ADD INDEX idx_published_category (is_published, category_id);
```

**Query Optimization:**
```php
// Bad: N+1 query problem
$tickets = $this->db->fetchAll("SELECT * FROM tickets");
foreach ($tickets as $ticket) {
    $user = $this->db->fetchOne("SELECT * FROM users WHERE user_id = ?", 
                                 [$ticket['user_id']]);
}

// Good: Use JOIN
$tickets = $this->db->fetchAll("
    SELECT t.*, u.first_name, u.last_name, u.email,
           c.name as category_name,
           a.first_name as agent_first_name, a.last_name as agent_last_name
    FROM tickets t
    JOIN users u ON t.user_id = u.user_id
    JOIN categories c ON t.category_id = c.category_id
    LEFT JOIN users a ON t.assigned_agent_id = a.user_id
    WHERE t.status = ?
    ORDER BY t.created_at DESC
", [$status]);
```

**Pagination Implementation:**
```php
function getPaginatedTickets($page = 1, $perPage = 25, $filters = []) {
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT t.*, u.first_name, u.last_name, c.name as category_name
            FROM tickets t
            JOIN users u ON t.user_id = u.user_id
            JOIN categories c ON t.category_id = c.category_id
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND t.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['priority'])) {
        $sql .= " AND t.priority = ?";
        $params[] = $filters['priority'];
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM tickets t WHERE 1=1";
    if (!empty($filters['status'])) {
        $countSql .= " AND t.status = ?";
    }
    if (!empty($filters['priority'])) {
        $countSql .= " AND t.priority = ?";
    }
    $total = $this->db->fetchOne($countSql, $params)['total'];
    
    // Get paginated results
    $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    
    $tickets = $this->db->fetchAll($sql, $params);
    
    return [
        'tickets' => $tickets,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => ceil($total / $perPage)
    ];
}
```

### 4. Code Quality Improvements

**Consistent Error Handling:**
```php
// All class methods should follow this pattern
public function someMethod($param) {
    try {
        // Validate input
        if (empty($param)) {
            throw new InvalidArgumentException('Parameter cannot be empty');
        }
        
        // Perform operation
        $result = $this->db->execute($sql, $params);
        
        // Log success if important
        logError('ClassName::someMethod', 'Operation successful', [
            'param' => $param
        ]);
        
        return $result;
        
    } catch (Exception $e) {
        // Log error with context
        logError('ClassName::someMethod', $e->getMessage(), [
            'param' => $param,
            'trace' => $e->getTraceAsString()
        ]);
        
        // Return false or throw depending on context
        return false;
    }
}
```

**PHPDoc Comments:**
```php
/**
 * Create a new ticket
 * 
 * @param int $userId User ID creating the ticket
 * @param string $title Ticket title
 * @param string $description Ticket description
 * @param int $categoryId Category ID
 * @param string $priority Priority level (low, medium, high, urgent)
 * @param string $source Source of ticket (web, email, phone)
 * @return int|false Ticket ID on success, false on failure
 */
public function createTicket($userId, $title, $description, $categoryId, 
                             $priority = 'medium', $source = 'web') {
    // Implementation
}
```

### 5. UI/UX Polish

**Consistent Styling:**
```css
/* Status badges */
.badge-open { background-color: #17a2b8; }
.badge-in-progress { background-color: #ffc107; color: #000; }
.badge-pending { background-color: #6c757d; }
.badge-resolved { background-color: #28a745; }
.badge-closed { background-color: #343a40; }

/* Priority badges */
.badge-low { background-color: #6c757d; }
.badge-medium { background-color: #17a2b8; }
.badge-high { background-color: #fd7e14; }
.badge-urgent { background-color: #dc3545; }

/* Form validation */
.is-invalid { border-color: #dc3545; }
.invalid-feedback { color: #dc3545; display: block; }
.is-valid { border-color: #28a745; }
.valid-feedback { color: #28a745; display: block; }

/* Loading states */
.loading { opacity: 0.6; pointer-events: none; }
.spinner { /* spinner animation */ }
```

**JavaScript Enhancements:**
```javascript
// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        }
    });
    
    return isValid;
}

// AJAX form submission
function submitFormAjax(formId, successCallback) {
    const form = document.getElementById(formId);
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            if (successCallback) successCallback(data);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        showAlert('danger', 'An error occurred. Please try again.');
        console.error('Error:', error);
    });
}

// Alert messages
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.container').prepend(alertDiv);
    
    setTimeout(() => alertDiv.remove(), 5000);
}
```

### 6. Testing Strategy

**Test Checklist:**

**Functional Testing:**
- [ ] User registration with validation
- [ ] User login with correct/incorrect credentials
- [ ] Password reset flow
- [ ] Ticket creation (all fields)
- [ ] Ticket viewing and filtering
- [ ] Ticket assignment
- [ ] Ticket status updates
- [ ] Ticket comments (public and internal)
- [ ] File uploads (various types and sizes)
- [ ] Email notifications (all types)
- [ ] Knowledge base search
- [ ] Knowledge base article viewing
- [ ] Admin user management
- [ ] Admin category management
- [ ] Agent dashboard functionality
- [ ] Reports generation
- [ ] CI Management CRUD
- [ ] Change Management workflow

**Security Testing:**
- [ ] SQL injection attempts
- [ ] XSS attempts
- [ ] CSRF token validation
- [ ] File upload validation (malicious files)
- [ ] Session hijacking prevention
- [ ] Password strength enforcement
- [ ] Authorization checks (role-based access)

**Performance Testing:**
- [ ] Page load times (< 2 seconds)
- [ ] Database query performance
- [ ] File upload performance
- [ ] Concurrent user simulation (10+ users)

**Browser Testing:**
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Edge (latest)
- [ ] Safari (latest)
- [ ] Mobile browsers (iOS Safari, Chrome Mobile)

### 7. Documentation Structure

**README.md:**
- Project overview
- Features list
- Installation instructions (XAMPP)
- Configuration guide
- Usage guide
- Troubleshooting

**DEPLOYMENT.md:**
- Production deployment guide
- Server requirements
- Installation steps
- Configuration
- Security hardening
- Backup procedures

**API_DOCUMENTATION.md:**
- API endpoints
- Request/response formats
- Authentication
- Error codes

**DATABASE_SCHEMA.md:**
- Table descriptions
- Relationships
- Indexes
- Sample queries

**SECURITY.md:**
- Security features
- Best practices
- Vulnerability reporting

## Implementation Phases

### Phase 1: Complete Missing Features (Days 1-2)
- Implement ticket comments system
- Test CI/Change Management modules
- Fix any broken functionality

### Phase 2: Bug Fixes and Testing (Days 3-4)
- Comprehensive functional testing
- Fix all identified bugs
- Security testing and fixes

### Phase 3: Performance Optimization (Day 5)
- Database query optimization
- Add missing indexes
- Implement pagination
- Optimize slow pages

### Phase 4: Code Quality and Documentation (Days 6-7)
- Code review and refactoring
- Add PHPDoc comments
- Complete documentation
- Final testing

### Phase 5: Production Preparation (Day 8)
- Create production config templates
- Test backup/restore procedures
- Final security audit
- Create deployment checklist

## Dynamic Category Fields System Design

### Overview

The dynamic category fields system allows administrators to configure custom fields for each ticket category. When users create tickets, they see category-specific fields that collect additional information relevant to that category type.

### Architecture

**Components:**
1. **Admin Interface** - Manage fields per category
2. **Field Renderer** - JavaScript that dynamically loads and renders fields
3. **Field Storage** - Database tables for field definitions and values
4. **Template Integration** - Placeholder system for using field values in templates

### Database Schema

**Existing Tables:**
- `category_fields` - Field definitions (already exists)
- `ticket_field_values` - Field values per ticket (already exists)

**category_fields structure:**
```sql
- field_id (PK)
- category_id (FK to categories)
- field_name (unique per category, e.g., 'hardware_type')
- field_type (text, textarea, select, radio, checkbox, date, number, email, tel)
- field_label (display label, e.g., 'Hardware Type')
- field_options (JSON for select/radio/checkbox options)
- is_required (boolean)
- field_order (int for sorting)
- conditional_logic (JSON for conditional display rules)
- created_at, updated_at
```

**ticket_field_values structure:**
```sql
- value_id (PK)
- ticket_id (FK to tickets)
- field_id (FK to category_fields)
- field_value (TEXT to store any value type)
- created_at
```

### Component Details

#### 1. Admin Interface (admin/category_fields.php)

**Features:**
- Overview page showing all categories with field counts
- "Manage Fields" button per category opens modal
- Modal with field list and add/edit forms
- Drag & drop to reorder fields
- Real-time preview of field rendering

**UI Flow:**
```
1. Admin clicks "Manage Fields" for category
2. Modal opens with existing fields list
3. Admin can:
   - Add new field (opens form)
   - Edit field (opens form with data)
   - Delete field (confirmation)
   - Drag to reorder fields
4. Form includes:
   - Field name (slug, e.g., hardware_type)
   - Field label (display text)
   - Field type (dropdown)
   - Required checkbox
   - Options editor (for select/radio/checkbox)
   - Conditional logic (optional)
5. Preview updates in real-time
6. Save updates database via AJAX
```

**CategoryField.php Methods:**
```php
class CategoryField {
    // Create new field
    public function createField($categoryId, $fieldData) {
        // Validate field_name is unique for category
        // Insert into category_fields
        // Return field_id or false
    }
    
    // Update existing field
    public function updateField($fieldId, $fieldData) {
        // Validate field exists
        // Update category_fields
        // Return true or false
    }
    
    // Delete field
    public function deleteField($fieldId) {
        // Check if field has values in tickets
        // If yes, soft delete or prevent deletion
        // If no, delete from category_fields
    }
    
    // Get all fields for category
    public function getFieldsByCategory($categoryId, $orderBy = 'field_order') {
        // Fetch fields ordered by field_order
        // Return array of fields
    }
    
    // Update field order (for drag & drop)
    public function updateFieldOrder($fieldOrderArray) {
        // $fieldOrderArray = [field_id => new_order, ...]
        // Update field_order for each field
        // Return true or false
    }
    
    // Get field by ID
    public function getFieldById($fieldId) {
        // Fetch single field
        // Return field data or false
    }
}
```

#### 2. Dynamic Field Rendering (assets/js/dynamic-fields.js)

**JavaScript Architecture:**
```javascript
const DynamicFields = {
    // Load fields when category is selected
    loadCategoryFields: function(categoryId) {
        // AJAX call to api/get_category_fields.php?category_id=X
        // Clear existing dynamic fields container
        // Render each field based on type
        // Attach event listeners for conditional logic
        // Attach validation
    },
    
    // Render different field types
    renderField: function(field) {
        switch(field.field_type) {
            case 'text':
            case 'email':
            case 'tel':
            case 'number':
                return this.renderTextField(field);
            case 'textarea':
                return this.renderTextareaField(field);
            case 'select':
                return this.renderSelectField(field);
            case 'radio':
                return this.renderRadioField(field);
            case 'checkbox':
                return this.renderCheckboxField(field);
            case 'date':
                return this.renderDateField(field);
        }
    },
    
    // Individual renderers
    renderTextField: function(field) {
        // Create input element with proper attributes
        // Add label, required indicator, placeholder
        // Return HTML string or DOM element
    },
    
    // ... other renderers
    
    // Conditional logic handler
    handleConditionalLogic: function() {
        // Listen to field changes
        // Show/hide dependent fields based on conditions
        // Example: if field_license_needed === 'yes', show field_license_details
    },
    
    // Client-side validation
    validateFields: function() {
        // Check required fields are filled
        // Validate field types (email format, number range, etc.)
        // Show inline error messages
        // Return true/false
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            DynamicFields.loadCategoryFields(this.value);
        });
    }
});
```

**Field Rendering Examples:**

**Text Field:**
```html
<div class="mb-3">
    <label for="field_hardware_type" class="form-label">
        Hardware Type <span class="text-danger">*</span>
    </label>
    <input type="text" 
           class="form-control" 
           id="field_hardware_type" 
           name="fields[hardware_type]" 
           required>
</div>
```

**Select Field:**
```html
<div class="mb-3">
    <label for="field_os" class="form-label">Operating System</label>
    <select class="form-select" id="field_os" name="fields[os]">
        <option value="">-- Selecteer --</option>
        <option value="windows">Windows</option>
        <option value="macos">macOS</option>
        <option value="linux">Linux</option>
    </select>
</div>
```

**Conditional Field Example:**
```javascript
// Field configuration in database:
{
    "field_name": "license_details",
    "conditional_logic": {
        "field": "license_needed",
        "operator": "equals",
        "value": "yes"
    }
}

// JavaScript handling:
document.getElementById('field_license_needed').addEventListener('change', function() {
    const licenseDetailsField = document.getElementById('field_license_details').closest('.mb-3');
    if (this.value === 'yes') {
        licenseDetailsField.style.display = 'block';
    } else {
        licenseDetailsField.style.display = 'none';
    }
});
```

#### 3. Field Value Storage

**Ticket.php Integration:**
```php
class Ticket {
    // Save field values when ticket is created
    public function saveFieldValues($ticketId, $fieldValues) {
        // $fieldValues = ['hardware_type' => 'Laptop', 'os' => 'windows', ...]
        
        try {
            foreach ($fieldValues as $fieldName => $value) {
                // Get field_id from field_name
                $field = $this->db->fetchOne(
                    "SELECT field_id FROM category_fields WHERE field_name = ?",
                    [$fieldName]
                );
                
                if ($field) {
                    // Handle checkbox arrays
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    
                    // Insert value
                    $this->db->execute(
                        "INSERT INTO ticket_field_values (ticket_id, field_id, field_value) 
                         VALUES (?, ?, ?)",
                        [$ticketId, $field['field_id'], $value]
                    );
                }
            }
            return true;
        } catch (Exception $e) {
            logError('Ticket::saveFieldValues', $e->getMessage(), [
                'ticket_id' => $ticketId
            ]);
            return false;
        }
    }
    
    // Get field values for ticket
    public function getFieldValues($ticketId) {
        try {
            $sql = "SELECT cf.field_name, cf.field_label, cf.field_type, 
                           tfv.field_value
                    FROM ticket_field_values tfv
                    JOIN category_fields cf ON tfv.field_id = cf.field_id
                    WHERE tfv.ticket_id = ?
                    ORDER BY cf.field_order";
            
            $values = $this->db->fetchAll($sql, [$ticketId]);
            
            // Format values based on type
            foreach ($values as &$value) {
                if ($value['field_type'] === 'checkbox') {
                    $value['field_value'] = json_decode($value['field_value'], true);
                }
            }
            
            return $values;
        } catch (Exception $e) {
            logError('Ticket::getFieldValues', $e->getMessage(), [
                'ticket_id' => $ticketId
            ]);
            return [];
        }
    }
}
```

**Integration in create_ticket.php:**
```php
// After ticket is created
if ($ticketId) {
    // Save dynamic field values
    if (isset($_POST['fields']) && is_array($_POST['fields'])) {
        $ticket->saveFieldValues($ticketId, $_POST['fields']);
    }
    
    // Continue with rest of ticket creation...
}
```

#### 4. Display Field Values

**On Ticket Detail Pages:**
```php
// In user/ticket_detail.php and agent/ticket_detail.php
$fieldValues = $ticket->getFieldValues($ticketId);

if (!empty($fieldValues)) {
    echo '<div class="card mt-3">';
    echo '<div class="card-header"><h5>Aanvullende Informatie</h5></div>';
    echo '<div class="card-body">';
    echo '<dl class="row">';
    
    foreach ($fieldValues as $field) {
        echo '<dt class="col-sm-4">' . htmlspecialchars($field['field_label']) . '</dt>';
        echo '<dd class="col-sm-8">';
        
        // Format based on field type
        switch ($field['field_type']) {
            case 'checkbox':
                if (is_array($field['field_value'])) {
                    echo implode(', ', array_map('htmlspecialchars', $field['field_value']));
                } else {
                    echo $field['field_value'] ? 'Ja' : 'Nee';
                }
                break;
            case 'date':
                echo date('d-m-Y', strtotime($field['field_value']));
                break;
            case 'email':
                echo '<a href="mailto:' . htmlspecialchars($field['field_value']) . '">' . 
                     htmlspecialchars($field['field_value']) . '</a>';
                break;
            default:
                echo htmlspecialchars($field['field_value']);
        }
        
        echo '</dd>';
    }
    
    echo '</dl>';
    echo '</div>';
    echo '</div>';
}
```

#### 5. Template Integration

**Template.php Enhancement:**
```php
class Template {
    // Get field placeholders for ticket
    public function getFieldPlaceholders($ticketId) {
        $ticket = new Ticket($this->db);
        $fieldValues = $ticket->getFieldValues($ticketId);
        
        $placeholders = [];
        foreach ($fieldValues as $field) {
            $placeholderKey = '{field_' . $field['field_name'] . '}';
            $placeholders[$placeholderKey] = $field['field_value'];
        }
        
        return $placeholders;
    }
    
    // Apply template with field placeholders
    public function applyTemplate($templateId, $ticketId) {
        $template = $this->getTemplateById($templateId);
        if (!$template) return false;
        
        $content = $template['content'];
        
        // Get ticket data
        $ticket = new Ticket($this->db);
        $ticketData = $ticket->getTicketById($ticketId);
        
        // Standard placeholders
        $placeholders = [
            '{ticket_number}' => $ticketData['ticket_number'],
            '{title}' => $ticketData['title'],
            '{description}' => $ticketData['description'],
            // ... other standard placeholders
        ];
        
        // Add field placeholders
        $fieldPlaceholders = $this->getFieldPlaceholders($ticketId);
        $placeholders = array_merge($placeholders, $fieldPlaceholders);
        
        // Replace all placeholders
        foreach ($placeholders as $key => $value) {
            $content = str_replace($key, $value, $content);
        }
        
        return $content;
    }
}
```

**Template Editor Enhancement:**
```php
// In admin/templates.php
// Show available placeholders per category
$categoryFields = new CategoryField($db);
$categories = $db->fetchAll("SELECT * FROM categories WHERE is_active = 1");

echo '<div class="alert alert-info">';
echo '<h6>Beschikbare Placeholders:</h6>';
echo '<p><strong>Standaard:</strong> {ticket_number}, {title}, {description}, {user_name}, {user_email}</p>';

foreach ($categories as $category) {
    $fields = $categoryFields->getFieldsByCategory($category['category_id']);
    if (!empty($fields)) {
        echo '<p><strong>' . htmlspecialchars($category['name']) . ':</strong> ';
        $fieldPlaceholders = array_map(function($f) {
            return '{field_' . $f['field_name'] . '}';
        }, $fields);
        echo implode(', ', $fieldPlaceholders);
        echo '</p>';
    }
}
echo '</div>';
```

### API Endpoints

**api/get_category_fields.php:**
```php
<?php
require_once '../includes/session.php';
require_once '../classes/Database.php';
require_once '../classes/CategoryField.php';

header('Content-Type: application/json');

if (!isset($_GET['category_id'])) {
    echo json_encode(['error' => 'Category ID required']);
    exit;
}

$db = new Database();
$categoryField = new CategoryField($db);

$fields = $categoryField->getFieldsByCategory($_GET['category_id']);

echo json_encode([
    'success' => true,
    'fields' => $fields
]);
```

**api/update_field_order.php:**
```php
<?php
require_once '../includes/session.php';
require_once '../classes/Database.php';
require_once '../classes/CategoryField.php';

// Admin only
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// CSRF check
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF validation failed']);
    exit;
}

$fieldOrder = json_decode($_POST['field_order'], true);

$db = new Database();
$categoryField = new CategoryField($db);

$result = $categoryField->updateFieldOrder($fieldOrder);

echo json_encode([
    'success' => $result,
    'message' => $result ? 'Order updated' : 'Failed to update order'
]);
```

### Security Considerations

1. **Input Validation:**
   - Validate field types are in allowed list
   - Sanitize field names (alphanumeric + underscore only)
   - Validate JSON for field_options
   - Validate conditional_logic JSON structure

2. **Authorization:**
   - Only admins can manage fields
   - Users can only see fields for their selected category
   - Field values are tied to tickets (users can only see their own tickets)

3. **SQL Injection:**
   - Use prepared statements for all queries
   - Never concatenate user input in SQL

4. **XSS Prevention:**
   - Use htmlspecialchars() on all field labels and values
   - Sanitize field options before rendering

### Performance Considerations

1. **Caching:**
   - Cache field definitions per category (rarely change)
   - Use session storage for loaded fields

2. **Database:**
   - Index on category_fields.category_id
   - Index on ticket_field_values.ticket_id
   - Composite index on (ticket_id, field_id)

3. **Frontend:**
   - Load fields via AJAX only when category changes
   - Debounce field order updates during drag & drop

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
- ✅ Dynamic category fields system is fully functional
- ✅ All field types render and save correctly
- ✅ Template integration works with field placeholders
