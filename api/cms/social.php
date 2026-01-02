<?php
/**
 * CMS API - Social Links
 * GET: Get all social links
 * POST: Update social link (by type)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../connection_manager.php';

try {
    $db = ConnectionManager::getAccountDB();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get all social links
        $stmt = $db->query("SELECT LinkID, LinkType, LinkURL, DisplayName, DisplayOrder, UpdatedDate FROM TB_SocialLinks ORDER BY DisplayOrder ASC");
        $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert array to object format for easier access
        $result = [];
        foreach ($links as $link) {
            $linkType = strtolower($link['LinkType']);
            $result[$linkType] = [
                'url' => $link['LinkURL'],
                'name' => $link['DisplayName']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $result
        ], JSON_UNESCAPED_UNICODE);
    } 
    elseif ($method === 'POST') {
        // Update social links (can update multiple at once)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !is_array($input)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid input data'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Map of form field names to link types
        $linkTypeMap = [
            'facebook' => 'facebook',
            'facebook_group' => 'facebook_group',
            'zalo' => 'zalo',
            'discord' => 'discord'
        ];
        
        // Display names
        $displayNames = [
            'facebook' => 'Facebook Page',
            'facebook_group' => 'Facebook Group',
            'zalo' => 'Zalo',
            'discord' => 'Discord'
        ];
        
        // Display order
        $displayOrders = [
            'facebook' => 1,
            'facebook_group' => 2,
            'zalo' => 3,
            'discord' => 4
        ];
        
        $updated = 0;
        $errors = [];
        
        // Process each link type
        foreach ($linkTypeMap as $fieldName => $linkType) {
            if (isset($input[$fieldName])) {
                $linkURL = trim($input[$fieldName]);
                
                // Skip if empty (allow clearing links)
                if (empty($linkURL)) {
                    // Optionally delete empty links, or just skip
                    continue;
                }
                
                // Validate URL
                if (!filter_var($linkURL, FILTER_VALIDATE_URL)) {
                    $errors[] = "Invalid URL format for {$fieldName}: {$linkURL}";
                    continue;
                }
                
                $displayName = $displayNames[$linkType] ?? null;
                $displayOrder = $displayOrders[$linkType] ?? 0;
                
                // Check if link type exists
                $stmt = $db->prepare("SELECT LinkID FROM TB_SocialLinks WHERE LinkType = ?");
                $stmt->execute([$linkType]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing) {
                    // Update existing
                    $stmt = $db->prepare("UPDATE TB_SocialLinks SET LinkURL = ?, DisplayName = ?, DisplayOrder = ?, UpdatedDate = GETDATE() WHERE LinkType = ?");
                    $stmt->execute([$linkURL, $displayName, $displayOrder, $linkType]);
                } else {
                    // Insert new
                    $stmt = $db->prepare("INSERT INTO TB_SocialLinks (LinkType, LinkURL, DisplayName, DisplayOrder, UpdatedDate) VALUES (?, ?, ?, ?, GETDATE())");
                    $stmt->execute([$linkType, $linkURL, $displayName, $displayOrder]);
                }
                
                $updated++;
            }
        }
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => implode('; ', $errors)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Updated {$updated} social link(s) successfully"
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

