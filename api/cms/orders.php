<?php
/**
 * CMS API - Orders Management
 * GET: List orders with search/filter
 * Parameters: order_code, username, status, page, limit
 * Sort: Default CreatedDate DESC
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../connection_manager.php';

try {
    $db = ConnectionManager::getAccountDB();
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get parameters
        $orderCode = isset($_GET['order_code']) ? trim($_GET['order_code']) : null;
        $username = isset($_GET['username']) ? trim($_GET['username']) : null;
        $status = isset($_GET['status']) ? trim($_GET['status']) : null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause
        $whereConditions = [];
        $params = [];
        
        if ($orderCode) {
            $whereConditions[] = "o.OrderCode LIKE ?";
            $params[] = '%' . $orderCode . '%';
        }
        
        if ($username) {
            $whereConditions[] = "u.StrUserID LIKE ?";
            $params[] = '%' . $username . '%';
        }
        
        if ($status) {
            $whereConditions[] = "o.Status = ?";
            $params[] = $status;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM TB_Order o 
                     LEFT JOIN TB_User u ON o.JID = u.JID 
                     $whereClause";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $totalCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get orders
        // Note: SQL Server requires OFFSET and FETCH NEXT to be integers
        // We need to embed them directly in SQL (safely, since we've already validated them)
        $offset = (int)$offset;
        $limit = (int)$limit;
        
        $sql = "SELECT o.OrderID, o.OrderCode, o.Amount, o.SilkAmount, o.Status, 
                       o.PaymentMethod, o.CreatedDate, o.UpdatedDate, o.CompletedDate,
                       u.StrUserID as Username
                FROM TB_Order o 
                LEFT JOIN TB_User u ON o.JID = u.JID 
                $whereClause
                ORDER BY o.CreatedDate DESC
                OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => array_map(function($order) {
                return [
                    'order_id' => (int)$order['OrderID'],
                    'order_code' => $order['OrderCode'],
                    'username' => $order['Username'],
                    'amount' => (float)$order['Amount'],
                    'silk_amount' => (int)$order['SilkAmount'],
                    'status' => $order['Status'],
                    'payment_method' => $order['PaymentMethod'],
                    'created_date' => $order['CreatedDate'],
                    'updated_date' => $order['UpdatedDate'],
                    'completed_date' => $order['CompletedDate']
                ];
            }, $orders),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
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

