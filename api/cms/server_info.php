<?php
/**
 * CMS API - Server Info
 * GET: Get server info content
 * POST: Update server info content
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../connection_manager.php';

try {
    $db = ConnectionManager::getAccountDB();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get server info
        $stmt = $db->query("SELECT TOP 1 InfoID, Content, UpdatedDate FROM TB_ServerInfo ORDER BY InfoID DESC");
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($info) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'info_id' => (int)$info['InfoID'],
                    'content' => $info['Content'],
                    'updated_date' => $info['UpdatedDate']
                ]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => true,
                'data' => [
                    'info_id' => null,
                    'content' => '',
                    'updated_date' => null
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    } 
    elseif ($method === 'POST') {
        // Update server info
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['content']) || trim($input['content']) === '') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Content is required'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $content = trim($input['content']);
        
        // Check if record exists
        $stmt = $db->query("SELECT TOP 1 InfoID FROM TB_ServerInfo ORDER BY InfoID DESC");
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing
            $stmt = $db->prepare("UPDATE TB_ServerInfo SET Content = ?, UpdatedDate = GETDATE() WHERE InfoID = ?");
            $stmt->execute([$content, $existing['InfoID']]);
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO TB_ServerInfo (Content, UpdatedDate) VALUES (?, GETDATE())");
            $stmt->execute([$content]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Server info updated successfully'
        ], JSON_UNESCAPED_UNICODE);
    }
    else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

