<?php
/**
 * CMS API - News
 * GET: List news (filter by category)
 * POST: Create/Update news
 * DELETE: Delete news (via query param ?news_id=)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../connection_manager.php';

try {
    $db = ConnectionManager::getAccountDB();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get single news by ID or list with optional category filter
        $newsID = isset($_GET['news_id']) ? (int)$_GET['news_id'] : null;
        
        if ($newsID) {
            // Get single news item
            $stmt = $db->prepare("SELECT NewsID, Category, Title, LinkURL, DisplayOrder, IsActive, CreatedDate, UpdatedDate FROM TB_News WHERE NewsID = ?");
            $stmt->execute([$newsID]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'news_id' => (int)$item['NewsID'],
                        'category' => $item['Category'],
                        'title' => $item['Title'],
                        'link_url' => $item['LinkURL'],
                        'display_order' => (int)$item['DisplayOrder'],
                        'is_active' => (bool)$item['IsActive'],
                        'created_date' => $item['CreatedDate'],
                        'updated_date' => $item['UpdatedDate']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'News not found'
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
        // Get news with optional category filter
        $category = isset($_GET['category']) ? trim($_GET['category']) : null;
        
        if ($category && $category !== 'Tất Cả') {
            $stmt = $db->prepare("SELECT NewsID, Category, Title, LinkURL, DisplayOrder, IsActive, CreatedDate, UpdatedDate FROM TB_News WHERE Category = ? ORDER BY DisplayOrder ASC, CreatedDate DESC");
            $stmt->execute([$category]);
        } else {
            $stmt = $db->query("SELECT NewsID, Category, Title, LinkURL, DisplayOrder, IsActive, CreatedDate, UpdatedDate FROM TB_News ORDER BY DisplayOrder ASC, CreatedDate DESC");
        }
        
        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => array_map(function($item) {
                return [
                    'news_id' => (int)$item['NewsID'],
                    'category' => $item['Category'],
                    'title' => $item['Title'],
                    'link_url' => $item['LinkURL'],
                    'display_order' => (int)$item['DisplayOrder'],
                    'is_active' => (bool)$item['IsActive'],
                    'created_date' => $item['CreatedDate'],
                    'updated_date' => $item['UpdatedDate']
                ];
            }, $news)
        ], JSON_UNESCAPED_UNICODE);
        }
    } 
    elseif ($method === 'POST') {
        // Create or Update news
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['category']) || !isset($input['title']) || !isset($input['link_url'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'category, title, and link_url are required'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $category = trim($input['category']);
        $title = trim($input['title']);
        $linkURL = trim($input['link_url']);
        $displayOrder = isset($input['display_order']) ? (int)$input['display_order'] : 0;
        $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
        $newsID = isset($input['news_id']) ? (int)$input['news_id'] : null;
        
        // Validate URL
        if (!filter_var($linkURL, FILTER_VALIDATE_URL) && $linkURL !== '#') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid link_url format'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Validate category
        $allowedCategories = ['Tất Cả', 'Tin Nóng', 'Sự Kiện', 'Cập Nhật'];
        if (!in_array($category, $allowedCategories)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid category. Allowed: ' . implode(', ', $allowedCategories)
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        if ($newsID) {
            // Update existing
            $stmt = $db->prepare("UPDATE TB_News SET Category = ?, Title = ?, LinkURL = ?, DisplayOrder = ?, IsActive = ?, UpdatedDate = GETDATE() WHERE NewsID = ?");
            $stmt->execute([$category, $title, $linkURL, $displayOrder, $isActive ? 1 : 0, $newsID]);
            
            echo json_encode([
                'success' => true,
                'message' => 'News updated successfully',
                'news_id' => $newsID
            ], JSON_UNESCAPED_UNICODE);
        } else {
            // Insert new
            $stmt = $db->prepare("INSERT INTO TB_News (Category, Title, LinkURL, DisplayOrder, IsActive, CreatedDate, UpdatedDate) VALUES (?, ?, ?, ?, ?, GETDATE(), GETDATE())");
            $stmt->execute([$category, $title, $linkURL, $displayOrder, $isActive ? 1 : 0]);
            
            $newID = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'News created successfully',
                'news_id' => (int)$newID
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    elseif ($method === 'DELETE') {
        // Delete news
        $newsID = isset($_GET['news_id']) ? (int)$_GET['news_id'] : null;
        
        if (!$newsID) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'news_id parameter is required'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $stmt = $db->prepare("DELETE FROM TB_News WHERE NewsID = ?");
        $stmt->execute([$newsID]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'News deleted successfully'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'News not found'
            ], JSON_UNESCAPED_UNICODE);
        }
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

