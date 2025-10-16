<?php
/**
 * Language System
 * 
 * Multi-language support for the ICT Ticketportaal
 * Default language: Dutch (nl)
 * Supported languages: Dutch (nl), English (en)
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current language from session or default to Dutch
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'nl'; // Default to Dutch
}

$currentLanguage = $_SESSION['language'];

/**
 * Load language file
 */
function loadLanguage($lang = null) {
    global $currentLanguage;
    
    if ($lang === null) {
        $lang = $currentLanguage;
    }
    
    $langFile = __DIR__ . "/languages/{$lang}.php";
    
    if (file_exists($langFile)) {
        return include $langFile;
    }
    
    // Fallback to Dutch if language file not found
    return include __DIR__ . "/languages/nl.php";
}

// Load current language
$lang = loadLanguage();

/**
 * Get translated text
 * 
 * @param string $key Translation key
 * @param array $params Optional parameters for string replacement
 * @return string Translated text
 */
function __($key, $params = []) {
    global $lang;
    
    // Split key by dot notation (e.g., 'common.welcome')
    $keys = explode('.', $key);
    $value = $lang;
    
    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $key; // Return key if translation not found
        }
    }
    
    // Replace parameters
    if (!empty($params) && is_string($value)) {
        foreach ($params as $param => $replacement) {
            $value = str_replace(':' . $param, $replacement, $value);
        }
    }
    
    return $value;
}

/**
 * Set language
 * 
 * @param string $lang Language code (nl, en)
 */
function setLanguage($lang) {
    global $currentLanguage;
    
    if (in_array($lang, ['nl', 'en'])) {
        $_SESSION['language'] = $lang;
        $currentLanguage = $lang;
        
        // Reload language
        global $lang;
        $lang = loadLanguage();
    }
}

/**
 * Get current language
 * 
 * @return string Current language code
 */
function getCurrentLanguage() {
    global $currentLanguage;
    return $currentLanguage;
}
