# Security Functions Quick Reference

This document provides a quick reference for all security-related functions in the ICT Ticketportaal.

## Input Validation Functions

### validateRequired($value, $fieldName)
Validates that a field is not empty.

```php
$result = validateRequired($email, 'Email');
if (!$result['valid']) {
    $errors[] = $result['error'];
}
```

### validateEmail($email)
Validates email format and DNS records.

```php
if (!validateEmail($email)) {
    $errors[] = 'Invalid email address';
}
```

### validateLength($value, $min, $max, $fieldName)
Validates string length.

```php
$result = validateLength($title, 5, 255, 'Title');
if (!$result['valid']) {
    $errors[] = $result['error'];
}
```

### validatePassword($password)
Validates password strength (8+ chars, letters + numbers).

```php
$result = validatePassword($password);
if (!$result['valid']) {
    $errors[] = $result['error'];
}
```

### validateInteger($value, $fieldName)
Validates integer values.

```php
$result = validateInteger($categoryId, 'Category ID');
if (!$result['valid']) {
    $errors[] = $result['error'];
}
```

### validateEnum($value, $allowedValues, $fieldName)
Validates against allowed values.

```php
$result = validateEnum($priority, ['low', 'medium', 'high', 'urgent'], 'Priority');
if (!$result['valid']) {
    $errors[] = $result['error'];
}
```

### validateFileUpload($file)
Validates file uploads (type, size, errors).

```php
$result = validateFileUpload($_FILES['attachment']);
if (!$result['success']) {
    $errors[] = $result['error'];
}
```

## Sanitization Functions

### sanitizeInput($input)
Sanitizes text input (removes HTML, trims whitespace).

```php
$title = sanitizeInput($_POST['title']);
```

### sanitizeText($text)
Sanitizes text for database storage (preserves line breaks).

```php
$description = sanitizeText($_POST['description']);
```

### sanitizeHTML($html)
Sanitizes HTML content (allows only safe tags).

```php
$content = sanitizeHTML($_POST['content']);
```

### escapeOutput($string)
Escapes output for safe HTML display.

```php
echo escapeOutput($userInput);
```

## CSRF Protection Functions

### generateCSRFToken()
Generates and stores CSRF token in session.

```php
$token = generateCSRFToken();
```

### validateCSRFToken($token)
Validates CSRF token (timing-safe comparison).

```php
if (!validateCSRFToken($_POST['csrf_token'])) {
    die('Invalid token');
}
```

### outputCSRFField()
Outputs hidden CSRF token field for forms.

```php
<form method="POST">
    <?php outputCSRFField(); ?>
    <!-- form fields -->
</form>
```

### verifyCSRFToken()
Verifies CSRF token from POST request (dies if invalid).

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    // Process form...
}
```

## Session Management Functions

### initSession()
Initializes secure session with proper settings.

```php
initSession();
```

### checkLogin()
Checks if user is logged in and session is valid.

```php
if (!checkLogin()) {
    redirectTo('/login.php');
}
```

### requireLogin($redirectUrl)
Requires user to be logged in (redirects if not).

```php
requireLogin();
// or with custom redirect
requireLogin('/user/dashboard.php');
```

### checkRole($allowedRoles)
Checks if user has specific role(s).

```php
if (checkRole('admin')) {
    // Admin-only code
}

// Multiple roles
if (checkRole(['admin', 'agent'])) {
    // Admin or agent code
}
```

### requireRole($allowedRoles, $redirectUrl)
Requires user to have specific role (redirects if not).

```php
requireRole('admin');
// or
requireRole(['admin', 'agent']);
```

## Utility Functions

### redirectTo($url, $statusCode)
Safely redirects to URL (prevents header injection).

```php
redirectTo('/user/dashboard.php');
// or with status code
redirectTo('/login.php', 301);
```

### logError($context, $message, $data)
Logs error with context and user information.

```php
logError('Database', 'Connection failed', [
    'host' => DB_HOST,
    'error' => $e->getMessage()
]);
```

### generateRandomFilename($extension)
Generates random filename for uploads.

```php
$filename = generateRandomFilename('jpg');
// Returns: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6.jpg
```

## Common Usage Patterns

### Form with CSRF Protection

```php
<?php
initSession();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    
    // Validate and sanitize
    $title = sanitizeInput($_POST['title']);
    $result = validateLength($title, 5, 255, 'Title');
    
    if (!$result['valid']) {
        $errors[] = $result['error'];
    }
    
    // Process if no errors
    if (empty($errors)) {
        // Save to database...
    }
}
?>

<form method="POST">
    <?php outputCSRFField(); ?>
    <input type="text" name="title" value="<?php echo escapeOutput($title ?? ''); ?>">
    <button type="submit">Submit</button>
</form>
```

### File Upload Handling

```php
if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
    $validation = validateFileUpload($_FILES['attachment']);
    
    if ($validation['success']) {
        $file = $_FILES['attachment'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newFilename = generateRandomFilename($extension);
        $uploadPath = UPLOAD_PATH . 'tickets/' . $newFilename;
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // File uploaded successfully
        }
    } else {
        $errors[] = $validation['error'];
    }
}
```

### Role-Based Access Control

```php
<?php
initSession();
requireRole('admin'); // Only admins can access

// Admin-only code here
?>
```

### Database Query with Prepared Statement

```php
$db = Database::getInstance();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([sanitizeInput($email)]);
$user = $stmt->fetch();
```

### Complete Form Validation Example

```php
$errors = [];

// Validate email
if (empty($_POST['email'])) {
    $errors['email'] = 'Email is required';
} elseif (!validateEmail($_POST['email'])) {
    $errors['email'] = 'Invalid email address';
}

// Validate password
$passwordCheck = validatePassword($_POST['password']);
if (!$passwordCheck['valid']) {
    $errors['password'] = $passwordCheck['error'];
}

// Validate title length
$titleCheck = validateLength($_POST['title'], 5, 255, 'Title');
if (!$titleCheck['valid']) {
    $errors['title'] = $titleCheck['error'];
}

// Validate priority enum
$priorityCheck = validateEnum($_POST['priority'], ['low', 'medium', 'high', 'urgent'], 'Priority');
if (!$priorityCheck['valid']) {
    $errors['priority'] = $priorityCheck['error'];
}

if (empty($errors)) {
    // Process form...
}
```

## Security Checklist for New Features

When adding new features, ensure:

- [ ] All user inputs are validated
- [ ] All user inputs are sanitized
- [ ] All outputs are escaped
- [ ] Forms have CSRF protection
- [ ] Database queries use prepared statements
- [ ] Authentication is checked
- [ ] Authorization is verified
- [ ] File uploads are validated
- [ ] Errors are logged
- [ ] Sensitive data is not exposed in errors

