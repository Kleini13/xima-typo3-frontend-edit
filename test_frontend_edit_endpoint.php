<?php
/**
 * Test script to directly test the frontend edit endpoint
 * Place this in your TYPO3 web root and access it via browser
 */

// Initialize TYPO3
require_once __DIR__ . '/vendor/autoload.php';

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Bootstrap TYPO3
Bootstrap::init();

echo "<h1>Frontend Edit Endpoint Test</h1>";

// Test the endpoint directly
$testUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/?type=1729341864';

echo "<h2>Testing Endpoint: $testUrl</h2>";

// Create a test POST request
$postData = json_encode([]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest',
            'User-Agent: Frontend Edit Test Script'
        ],
        'content' => $postData
    ]
]);

echo "<h3>Making POST request...</h3>";

$result = @file_get_contents($testUrl, false, $context);

if ($result === false) {
    echo "<p style='color: red;'><strong>Request failed!</strong></p>";
    $error = error_get_last();
    if ($error) {
        echo "<p>Error: " . htmlspecialchars($error['message']) . "</p>";
    }
} else {
    echo "<p style='color: green;'><strong>Request successful!</strong></p>";
    echo "<h3>Response:</h3>";
    
    // Check if it's JSON
    $decoded = json_decode($result, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<pre>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        // It's probably HTML (error page)
        echo "<h4>Raw Response (first 2000 characters):</h4>";
        echo "<pre>" . htmlspecialchars(substr($result, 0, 2000)) . "</pre>";
        
        // Try to extract the error message from TYPO3 exception page
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $result, $matches)) {
            echo "<h4>Error Title:</h4>";
            echo "<p style='color: red;'>" . htmlspecialchars(strip_tags($matches[1])) . "</p>";
        }
        
        if (preg_match('/<p class="exception-message-body"[^>]*>(.*?)<\/p>/s', $result, $matches)) {
            echo "<h4>Error Message:</h4>";
            echo "<p style='color: red;'>" . htmlspecialchars(strip_tags($matches[1])) . "</p>";
        }
        
        if (preg_match('/<pre class="exception-trace"[^>]*>(.*?)<\/pre>/s', $result, $matches)) {
            echo "<h4>Stack Trace (first 1000 characters):</h4>";
            echo "<pre style='color: red; font-size: 12px;'>" . htmlspecialchars(substr(strip_tags($matches[1]), 0, 1000)) . "</pre>";
        }
    }
}

echo "<h3>HTTP Response Headers:</h3>";
if (isset($http_response_header)) {
    echo "<pre>";
    foreach ($http_response_header as $header) {
        echo htmlspecialchars($header) . "\n";
    }
    echo "</pre>";
}

echo "<h3>Debug Information:</h3>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>TYPO3 Version:</strong> " . \TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version() . "</p>";
echo "<p><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";

// Check if we're in DDEV
if (strpos($_SERVER['HTTP_HOST'] ?? '', '.ddev.site') !== false) {
    echo "<p><strong>DDEV Environment:</strong> Detected</p>";
}
