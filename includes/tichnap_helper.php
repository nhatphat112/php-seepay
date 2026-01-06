<?php
/**
 * TichNap Helper Functions
 * Hàm hỗ trợ cho chức năng Nạp Tích Lũy
 */

/**
 * Format số tiền thành định dạng VND
 * 
 * @param int|float $amount Số tiền
 * @return string Định dạng "100.000 VND"
 */
function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . ' VND';
}

/**
 * Parse GUID từ string
 * 
 * @param string $guidString GUID string
 * @return string GUID đã format
 */
function parseGuid($guidString) {
    // Remove dashes và format lại
    $guid = str_replace('-', '', $guidString);
    if (strlen($guid) == 32) {
        return substr($guid, 0, 8) . '-' . 
               substr($guid, 8, 4) . '-' . 
               substr($guid, 12, 4) . '-' . 
               substr($guid, 16, 4) . '-' . 
               substr($guid, 20, 12);
    }
    return $guidString;
}

/**
 * Lấy tổng tiền đã nạp từ TB_Order
 * 
 * @param int $userJID JID của user
 * @param PDO $db Database connection
 * @return int Tổng tiền đã nạp
 */
function getTotalMoneyFromOrders($userJID, $db) {
    try {
        $stmt = $db->prepare("
            SELECT SUM(Amount) as total 
            FROM TB_Order 
            WHERE JID = ? AND Status = 'completed'
        ");
        $stmt->execute([$userJID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Error getting total money: " . $e->getMessage());
        return 0;
    }
}

/**
 * Lấy tổng tiền đã nạp từ TotalMoneyUser (nếu dùng bảng này)
 * 
 * @param int $userJID JID của user
 * @param PDO $db Database connection
 * @return int Tổng tiền đã nạp
 */
function getTotalMoneyFromTotalMoneyUser($userJID, $db) {
    try {
        $stmt = $db->prepare("
            SELECT SUM(TotalMoney) as total 
            FROM TotalMoneyUser 
            WHERE UserJID = ? AND IsDelete = 0
        ");
        $stmt->execute([$userJID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        error_log("Error getting total money from TotalMoneyUser: " . $e->getMessage());
        return 0;
    }
}

/**
 * Kiểm tra nhân vật có tồn tại không
 * 
 * @param string $charName Tên nhân vật
 * @param PDO $shardDb Shard database connection
 * @return bool True nếu nhân vật tồn tại
 */
function checkCharacterExists($charName, $shardDb) {
    try {
        $stmt = $shardDb->prepare("
            SELECT COUNT(*) as count 
            FROM _Char 
            WHERE CharName16 = ?
        ");
        $stmt->execute([$charName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['count'] ?? 0) > 0;
    } catch (Exception $e) {
        error_log("Error checking character: " . $e->getMessage());
        return false;
    }
}

/**
 * Thêm item vào game qua stored procedure
 * 
 * @param string $charName Tên nhân vật
 * @param string $codeItem Mã item
 * @param int $amount Số lượng
 * @param PDO $shardDb Shard database connection
 * @return bool True nếu thành công
 */
function addItemToCharacter($charName, $codeItem, $amount, $shardDb) {
    try {
        $stmt = $shardDb->prepare("
            EXEC [dbo].[_AddItemByName]
                @CharName = ?,
                @CodeName = ?,
                @Amount = ?,
                @OptLevel = 0
        ");
        $stmt->execute([$charName, $codeItem, $amount]);
        return true;
    } catch (Exception $e) {
        error_log("Error adding item to character: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate GUID format
 * 
 * @param string $guid GUID string
 * @return bool True nếu hợp lệ
 */
function isValidGuid($guid) {
    // Remove dashes và check length
    $guid = str_replace('-', '', $guid);
    return strlen($guid) == 32 && ctype_xdigit($guid);
}

/**
 * Parse danh sách item IDs từ DsItem string
 * 
 * @param string $dsItem String chứa IDs phân cách bằng dấu phẩy
 * @return array Mảng các ID đã trim
 */
function parseItemIds($dsItem) {
    if (empty($dsItem)) {
        return [];
    }
    $ids = explode(',', $dsItem);
    return array_map('trim', array_filter($ids));
}

