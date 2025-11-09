<?php
/**
 * Basic Autoloader for SuiteCRM API
 *
 * Simple PSR-4 autoloader for environments without Composer.
 * Not recommended for production use - use Composer instead.
 *
 * @package SuiteAPI
 */

// Define the base directory for the SuiteAPI namespace
define('SUITE_API_BASE_DIR', __DIR__ . '/src/');

/**
 * Basic PSR-4 Autoloader
 *
 * @param string $className The fully qualified class name
 * @return void
 */
function suiteApiAutoloader(string $className): void
{
    // Only handle SuiteAPI namespace
    if (strpos($className, 'Ksfraser\SuiteAPI\\') !== 0) {
        return;
    }

    // Remove namespace prefix
    $relativeClass = substr($className, strlen('Ksfraser\SuiteAPI\\'));

    // Convert namespace separators to directory separators
    $filePath = SUITE_API_BASE_DIR . str_replace('\\', '/', $relativeClass) . '.php';

    // Check if file exists and include it
    if (file_exists($filePath)) {
        require_once $filePath;
    }
}

// Register the autoloader
spl_autoload_register('suiteApiAutoloader');

// Optional: Define commonly used classes for easier access
if (!class_exists('Ksfraser\SuiteAPI\\Core\\SuiteCrmApiFactory', false)) {
    require_once SUITE_API_BASE_DIR . 'Core/SuiteCrmApiFactory.php';
}

if (!class_exists('Ksfraser\SuiteAPI\\Core\\SuiteCrmConfig', false)) {
    require_once SUITE_API_BASE_DIR . 'Core/SuiteCrmConfig.php';
}

if (!class_exists('Ksfraser\SuiteAPI\\Exceptions\\SuiteApiException', false)) {
    require_once SUITE_API_BASE_DIR . 'Exceptions/SuiteApiExceptions.php';
}