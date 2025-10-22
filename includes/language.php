<?php
/**
 * Language Helper Functions
 * Multi-language support for ICT Ticketportaal
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default language
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'nl'; // Default to Dutch
}

// Load language file
$currentLanguage = $_SESSION['language'];
$languageFile = __DIR__ . '/languages/' . $currentLanguage . '.php';

if (file_exists($languageFile)) {
    $translations = require $languageFile;
} else {
    // Fallback to Dutch if language file not found
    $translations = require __DIR__ . '/languages/nl.php';
}

/**
 * Get translation for a key
 * 
 * @param string $key Translation key
 * @param string $default Default value if key not found
 * @return string Translated text
 */
function __($key, $default = '') {
    global $translations;
    return $translations[$key] ?? $default ?? $key;
}

/**
 * Echo translation
 * 
 * @param string $key Translation key
 * @param string $default Default value if key not found
 */
function _e($key, $default = '') {
    echo __($key, $default);
}

/**
 * Set current language
 * 
 * @param string $lang Language code (nl, en)
 */
function setLanguage($lang) {
    $validLanguages = ['nl', 'en'];
    if (in_array($lang, $validLanguages)) {
        $_SESSION['language'] = $lang;
        // Reload translations
        global $translations;
        $languageFile = __DIR__ . '/languages/' . $lang . '.php';
        if (file_exists($languageFile)) {
            $translations = require $languageFile;
        }
    }
}

/**
 * Get current language
 * 
 * @return string Current language code
 */
function getCurrentLanguage() {
    return $_SESSION['language'] ?? 'nl';
}

/**
 * Get available languages
 * 
 * @return array Available languages
 */
function getAvailableLanguages() {
    return [
        'nl' => 'Nederlands',
        'en' => 'English'
    ];
}
