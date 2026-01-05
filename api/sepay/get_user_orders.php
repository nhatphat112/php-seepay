<?php
/**
 * Sepay Get User Orders API
 * GET /api/sepay/get_user_orders.php?limit=10&page=1&order_code=xxx&status=completed
 * 
 * Response:
 * {
 *   "success": true,
 *   "orders": [...],
 *   "pagination": {...}
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

session_start();
require_once __DIR__ . '/../../connection_manager.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized. Please login first.'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo '{"success":false,"error":"Internal server error"}';
    }
    exit;
}

try {
    $userJID = $_SESSION['user_id'];
    $db = ConnectionManager::getAccountDB();
    
    // Get parameters
    $orderCode = isset($_GET['order_code']) ? trim($_GET['order_code']) : null;
    $status = isset($_GET['status']) ? trim($_GET['status']) : null;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause - Only get orders for current user
    $whereConditions = ["o.JID = ?"];
    $params = [$userJID];
    
    if ($orderCode) {
        $whereConditions[] = "o.OrderCode LIKE ?";
        $params[] = '%' . $orderCode . '%';
    }
    
    if ($status) {
        $whereConditions[] = "o.Status = ?";
        $params[] = $status;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM TB_Order o $whereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get orders with pagination
    $offset = (int)$offset;
    $limit = (int)$limit;
    
    $sql = "SELECT 
                o.OrderID, 
                o.OrderCode, 
                o.Amount, 
                o.SilkAmount, 
                o.Status, 
                o.PaymentMethod, 
                o.CreatedDate, 
                o.UpdatedDate, 
                o.CompletedDate
            FROM TB_Order o 
            $whereClause
            ORDER BY o.CreatedDate DESC
            OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format orders
    $formattedOrders = array_map(function($order) {
        return [
            'OrderID' => (int)$order['OrderID'],
            'OrderCode' => $order['OrderCode'],
            'Amount' => (float)$order['Amount'],
            'SilkAmount' => (int)$order['SilkAmount'],
            'Status' => $order['Status'],
            'PaymentMethod' => $order['PaymentMethod'],
            'CreatedDate' => $order['CreatedDate'],
            'UpdatedDate' => $order['UpdatedDate'],
            'CompletedDate' => $order['CompletedDate']
        ];
    }, $orders);
    
    http_response_code(200);
    try {
        echo json_encode([
            'success' => true,
            'orders' => $formattedOrders,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ]
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(500);
        echo '{"success":false,"error":"Internal server error","orders":[]}';
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    try {
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error: ' . $e->getMessage(),
            'orders' => []
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $encodeError) {
        echo '{"success":false,"error":"Internal server error","orders":[]}';
    }
}

