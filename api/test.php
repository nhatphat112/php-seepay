<?php
/**
 * API Test - Simple test to check if API is working
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Test 1: Basic response
$response = [
    'status' => 'success',
    'message' => 'API is working!',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion()
];

// Test 2: Check if connection_manager can be loaded
try {
    require_once '../connection_manager.php';
    $response['connection_manager'] = 'loaded';
    
    // Test 3: Try to get connection status
    try {
        $status = ConnectionManager::getConnectionStatus();
        $response['database_status'] = 'connected';
        $response['connections'] = array_keys($status['connections']);
    } catch (Exception $e) {
        $response['database_status'] = 'failed';
        $response['database_error'] = $e->getMessage();
    }
} catch (Exception $e) {
    $response['connection_manager'] = 'failed';
    $response['error'] = $e->getMessage();
}

// Test 4: Check extensions
$response['extensions'] = [
    'pdo' => extension_loaded('pdo'),
    'pdo_sqlsrv' => extension_loaded('pdo_sqlsrv'),
    'sqlsrv' => extension_loaded('sqlsrv')
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>

