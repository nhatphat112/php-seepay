<?php
/**
 * API: Lấy danh sách users (Admin only)
 * 
 * Workflow:
 * 1. Check admin authentication
 * 2. Get search parameters (username, email)
 * 3. Query users from TB_User with silk from SK_Silk
 * 4. Return paginated results
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

session_start();

// Check admin authentication
require_once __DIR__ . '/../../includes/auth_helper.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden. Admin access required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../connection_manager.php';
    
    // Get search parameters
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 20))); // Max 100 per page
    $offset = ($page - 1) * $limit;
    
    $accountDb = ConnectionManager::getAccountDB();
    
    // Build WHERE clause for search
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(u.StrUserID LIKE ? OR u.Email LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count
    if (empty($whereClause)) {
        $countSql = "SELECT COUNT(*) as total FROM [dbo].[TB_User]";
        $countStmt = $accountDb->prepare($countSql);
        $countStmt->execute();
    } else {
        $countSql = "
            SELECT COUNT(*) as total
            FROM [dbo].[TB_User] u
            $whereClause
        ";
        $countStmt = $accountDb->prepare($countSql);
        $countStmt->execute($params);
    }
    
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $totalCount = $countResult ? intval($countResult['total']) : 0;
    
    // Get users with silk
    if (empty($whereClause)) {
        $sql = "
            SELECT 
                u.JID,
                u.StrUserID as username,
                u.Email,
                ISNULL(s.silk_own, 0) as silk_own,
                ISNULL(u.role, 'user') as role,
                u.regtime
            FROM [dbo].[TB_User] u
            LEFT JOIN [dbo].[SK_Silk] s ON s.JID = u.JID
            ORDER BY u.regtime DESC
            OFFSET ? ROWS
            FETCH NEXT ? ROWS ONLY
        ";
        $queryParams = [$offset, $limit];
    } else {
        $sql = "
            SELECT 
                u.JID,
                u.StrUserID as username,
                u.Email,
                ISNULL(s.silk_own, 0) as silk_own,
                ISNULL(u.role, 'user') as role,
                u.regtime
            FROM [dbo].[TB_User] u
            LEFT JOIN [dbo].[SK_Silk] s ON s.JID = u.JID
            $whereClause
            ORDER BY u.regtime DESC
            OFFSET ? ROWS
            FETCH NEXT ? ROWS ONLY
        ";
        $queryParams = array_merge($params, [$offset, $limit]);
    }
    
    $stmt = $accountDb->prepare($sql);
    $stmt->execute($queryParams);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format results
    $results = [];
    foreach ($users as $user) {
        $results[] = [
            'jid' => intval($user['JID']),
            'username' => $user['username'],
            'email' => $user['Email'] ?? '',
            'silk_own' => intval($user['silk_own'] ?? 0),
            'role' => $user['role'] ?? 'user',
            'regtime' => $user['regtime'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => intval($totalCount),
            'total_pages' => ceil($totalCount / $limit)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
