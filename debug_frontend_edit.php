<?php
/**
 * Debug script for TYPO3 Frontend Edit extension
 * Place this file in your TYPO3 web root and access it via browser to debug the issue
 */

// Initialize TYPO3
require_once __DIR__ . '/vendor/autoload.php';

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

// Bootstrap TYPO3
Bootstrap::init();

echo "<h1>TYPO3 Frontend Edit Debug</h1>";

// Check if extension is loaded
$extensionLoaded = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xima_typo3_frontend_edit');
echo "<p><strong>Extension loaded:</strong> " . ($extensionLoaded ? 'YES' : 'NO') . "</p>";

if (!$extensionLoaded) {
    echo "<p style='color: red;'>The extension is not loaded. Please install and activate it.</p>";
    exit;
}

// Check middleware registration
$middlewares = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['middlewares']['frontend'] ?? [];
$middlewareRegistered = isset($middlewares['xima/frontend-edit-information']);
echo "<p><strong>Middleware registered:</strong> " . ($middlewareRegistered ? 'YES' : 'NO') . "</p>";

if (!$middlewareRegistered) {
    echo "<p style='color: red;'>Middleware not registered. Check Configuration/RequestMiddlewares.php</p>";
}

// Check backend user authentication
echo "<h2>Backend User Status</h2>";
if (isset($GLOBALS['BE_USER'])) {
    echo "<p><strong>BE_USER exists:</strong> YES</p>";
    if ($GLOBALS['BE_USER']->user) {
        echo "<p><strong>User authenticated:</strong> YES</p>";
        echo "<p><strong>User ID:</strong> " . $GLOBALS['BE_USER']->user['uid'] . "</p>";
        echo "<p><strong>Username:</strong> " . $GLOBALS['BE_USER']->user['username'] . "</p>";
    } else {
        echo "<p style='color: red;'><strong>User authenticated:</strong> NO</p>";
    }
} else {
    echo "<p style='color: red;'><strong>BE_USER exists:</strong> NO</p>";
}

// Test database connection
echo "<h2>Database Connection</h2>";
try {
    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
    $count = $queryBuilder
        ->count('uid')
        ->from('tt_content')
        ->executeQuery()
        ->fetchOne();
    echo "<p><strong>Database connection:</strong> OK (Found $count content elements)</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Database error:</strong> " . $e->getMessage() . "</p>";
}

// Test the specific endpoint
echo "<h2>Endpoint Test</h2>";
$testUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/?type=1729341864';
echo "<p><strong>Test URL:</strong> <a href='$testUrl' target='_blank'>$testUrl</a></p>";

echo "<h2>Configuration</h2>";
try {
    $extConfig = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
        ->get('xima_typo3_frontend_edit');
    echo "<pre>" . print_r($extConfig, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error getting extension configuration: " . $e->getMessage() . "</p>";
}
