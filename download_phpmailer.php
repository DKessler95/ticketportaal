<?php
/**
 * Download and Install PHPMailer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes

echo "<h1>PHPMailer Installer</h1>";
echo "<hr>";

$targetDir = __DIR__ . '/includes/PHPMailer';
$zipFile = __DIR__ . '/phpmailer.zip';
$downloadUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip';

echo "<h2>Step 1: Download PHPMailer</h2>";
echo "<p>Downloading from GitHub...</p>";

// Download the file
$zipContent = @file_get_contents($downloadUrl);

if ($zipContent === false) {
    echo "<p style='color: red;'>✗ Failed to download PHPMailer</p>";
    echo "<p><strong>Manual Installation:</strong></p>";
    echo "<ol>";
    echo "<li>Download: <a href='$downloadUrl' target='_blank'>PHPMailer v6.9.1</a></li>";
    echo "<li>Extract the ZIP file</li>";
    echo "<li>Copy the 'src' folder to: <code>includes/PHPMailer/src/</code></li>";
    echo "</ol>";
    exit;
}

file_put_contents($zipFile, $zipContent);
echo "<p style='color: green;'>✓ Downloaded PHPMailer (" . number_format(strlen($zipContent)) . " bytes)</p>";

echo "<h2>Step 2: Extract Files</h2>";

if (!class_exists('ZipArchive')) {
    echo "<p style='color: red;'>✗ ZipArchive extension not available</p>";
    echo "<p><strong>Manual Extraction Required:</strong></p>";
    echo "<ol>";
    echo "<li>The file has been downloaded to: <code>$zipFile</code></li>";
    echo "<li>Extract it manually</li>";
    echo "<li>Copy the 'src' folder to: <code>includes/PHPMailer/src/</code></li>";
    echo "</ol>";
    exit;
}

$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    // Create target directory
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Extract only the src folder
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        
        // Only extract files from the src directory
        if (strpos($filename, '/src/') !== false) {
            $newFilename = str_replace('PHPMailer-6.9.1/', '', $filename);
            $targetPath = $targetDir . '/' . $newFilename;
            
            // Create directory if needed
            $dir = dirname($targetPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Extract file
            if (substr($filename, -1) !== '/') {
                $content = $zip->getFromIndex($i);
                file_put_contents($targetPath, $content);
            }
        }
    }
    
    $zip->close();
    echo "<p style='color: green;'>✓ Extracted PHPMailer files</p>";
    
    // Clean up
    unlink($zipFile);
    echo "<p style='color: green;'>✓ Cleaned up temporary files</p>";
} else {
    echo "<p style='color: red;'>✗ Failed to extract ZIP file</p>";
    exit;
}

echo "<h2>Step 3: Verify Installation</h2>";

$requiredFiles = [
    'src/PHPMailer.php',
    'src/SMTP.php',
    'src/Exception.php'
];

$allFilesExist = true;
foreach ($requiredFiles as $file) {
    $fullPath = $targetDir . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p style='color: green;'>✓ $file</p>";
    } else {
        echo "<p style='color: red;'>✗ $file NOT FOUND</p>";
        $allFilesExist = false;
    }
}

if ($allFilesExist) {
    echo "<hr>";
    echo "<div style='background: #d4edda; padding: 20px; border: 2px solid #28a745; border-radius: 5px;'>";
    echo "<h2 style='color: #155724; margin-top: 0;'>✓ PHPMailer Installed Successfully!</h2>";
    echo "<p>PHPMailer has been installed to: <code>$targetDir</code></p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>The EmailHandler will be automatically updated to use PHPMailer</li>";
    echo "<li>Test email sending with the updated handler</li>";
    echo "</ol>";
    echo "<p><a href='test_email_phpmailer.php' class='btn btn-primary'>Test Email with PHPMailer</a></p>";
    echo "</div>";
} else {
    echo "<hr>";
    echo "<div style='background: #f8d7da; padding: 20px; border: 2px solid #dc3545; border-radius: 5px;'>";
    echo "<h2 style='color: #721c24; margin-top: 0;'>✗ Installation Incomplete</h2>";
    echo "<p>Some files are missing. Please install manually.</p>";
    echo "</div>";
}
?>
