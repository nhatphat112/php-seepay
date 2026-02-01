<?php
/**
 * API: Reset vật phẩm mốc quay (Admin only)
 * Clone toàn bộ quà hiện có (ID mới), sau đó xóa các bản ghi cũ (ID cũ).
 * VD: 10 item id 1-10 → clone thành id 11-20, xóa id 1-10.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

session_start();

require_once __DIR__ . '/../../../includes/auth_helper.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Forbidden. Admin access required.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    require_once __DIR__ . '/../../../connection_manager.php';

    $db = ConnectionManager::getAccountDB();
    $adminJID = (int)($_SESSION['user_id'] ?? 0);

    // 1. Lấy toàn bộ quà hiện có (kể cả IsActive=0)
    $stmt = $db->query("
        SELECT Id, ItemName, ItemCode, Quantity, RequiredSpins, DisplayOrder, IsActive, CreatedBy, UpdatedBy
        FROM LuckyWheelAccumulatedItems
        ORDER BY Id ASC
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($items)) {
        echo json_encode([
            'success' => false,
            'error' => 'Không có vật phẩm mốc quay nào để reset.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $oldIds = array_column($items, 'Id');

    $db->beginTransaction();

    try {
        // 2. Clone: INSERT từng bản ghi (giữ nguyên dữ liệu, Id mới do IDENTITY)
        $insertStmt = $db->prepare("
            INSERT INTO LuckyWheelAccumulatedItems
            (ItemName, ItemCode, Quantity, RequiredSpins, DisplayOrder, IsActive, CreatedDate, UpdatedDate, CreatedBy, UpdatedBy)
            VALUES (?, ?, ?, ?, ?, ?, GETDATE(), GETDATE(), ?, ?)
        ");

        foreach ($items as $row) {
            $insertStmt->execute([
                $row['ItemName'],
                $row['ItemCode'],
                (int)$row['Quantity'],
                (int)$row['RequiredSpins'],
                (int)$row['DisplayOrder'],
                (int)$row['IsActive'] ? 1 : 0,
                $row['CreatedBy'] ?? $adminJID,
                $row['UpdatedBy'] ?? $adminJID
            ]);
        }

        // 3. Xóa từng item cũ theo cách có sẵn: soft delete (IsActive = 0) giống delete_accumulated_item
        $updateStmt = $db->prepare("
            UPDATE LuckyWheelAccumulatedItems
            SET IsActive = 0, UpdatedDate = GETDATE(), UpdatedBy = ?
            WHERE Id = ?
        ");
        foreach ($oldIds as $oldId) {
            $updateStmt->execute([$adminJID, $oldId]);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Đã reset: clone ' . count($items) . ' vật phẩm (ID mới) và vô hiệu hóa ' . count($oldIds) . ' quà cũ (ID: ' . implode(', ', $oldIds) . ').',
            'data' => [
                'cloned_count' => count($items),
                'old_ids' => $oldIds
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi database',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    error_log("reset_accumulated_items: " . $e->getMessage());
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
