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
 * Lấy tổng tiền đã nạp từ column AccumulatedDeposit trong TB_User
 * Column này được cập nhật khi order completed và khi admin reset/cộng tích lũy
 * 
 * @param int $userJID JID của user
 * @param PDO $db Database connection
 * @return int Tổng tiền đã nạp (tích lũy)
 */
function getTotalMoneyFromOrders($userJID, $db) {
    try {
        $stmt = $db->prepare("
            SELECT AccumulatedDeposit 
            FROM TB_User 
            WHERE JID = ?
        ");
        $stmt->execute([$userJID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['AccumulatedDeposit'] ?? 0);
    } catch (Exception $e) {
        error_log("Error getting total money from AccumulatedDeposit: " . $e->getMessage());
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
 * Lấy tên nhân vật đầu tiên từ UserJID
 * 
 * Workflow:
 * 1. Lấy CharName từ SR_ShardCharNames (bảng mapping UserJID -> CharName)
 * 2. Verify và lấy CharName16 từ SRO_VT_SHARD.dbo._Char (theo SQL script)
 * 
 * @param int $userJID JID của user
 * @param PDO $shardDb Shard database connection
 * @param PDO|null $accountDb Account database connection (không dùng, để tương thích)
 * @return string|null Tên nhân vật (CharName16) hoặc null nếu không tìm thấy
 */
function getFirstCharacterNameFromJID($userJID, $shardDb, $accountDb = null) {
    try {
        // Bước 1: Lấy CharName từ SR_ShardCharNames (bảng mapping)
        $stmt = $shardDb->prepare("
            SELECT TOP 1 CharName 
            FROM SR_ShardCharNames 
            WHERE UserJID = ?
            ORDER BY CharName ASC
        ");
        $stmt->execute([$userJID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || empty($result['CharName'])) {
            error_log("Warning: Could not find character in SR_ShardCharNames for UserJID: {$userJID}");
            return null;
        }
        
        $charName = trim($result['CharName']);
        error_log("Found character from SR_ShardCharNames for UserJID {$userJID}: {$charName}");
        
        // Bước 2: Verify và lấy CharName16 từ SRO_VT_SHARD.dbo._Char (theo SQL script)
        // SELECT @CharName = CharName16 FROM SRO_VT_SHARD.dbo._Char WHERE CharID = @CharID
        // Nhưng ở đây ta có CharName, nên query trực tiếp:
        $stmt = $shardDb->prepare("
            SELECT TOP 1 CharName16, CharID
            FROM SRO_VT_SHARD.dbo._Char 
            WHERE CharName16 = ?
        ");
        $stmt->execute([$charName]);
        $charResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($charResult && !empty($charResult['CharName16'])) {
            $charName16 = trim($charResult['CharName16']);
            $charID = (int)$charResult['CharID'];
            error_log("Verified character in SRO_VT_SHARD.dbo._Char for UserJID {$userJID}: CharName16={$charName16}, CharID={$charID}");
            return $charName16;
        }
        
        error_log("Warning: Character '{$charName}' not found in SRO_VT_SHARD.dbo._Char for UserJID: {$userJID}");
        return null;
    } catch (Exception $e) {
        error_log("Error getting first character name from JID {$userJID}: " . $e->getMessage());
        return null;
    }
}

/**
 * Kiểm tra nhân vật có tồn tại không
 * Sử dụng SRO_VT_SHARD.dbo._Char
 * 
 * @param string $charName Tên nhân vật
 * @param PDO $shardDb Shard database connection
 * @return bool True nếu nhân vật tồn tại
 */
function checkCharacterExists($charName, $shardDb) {
    try {
        $stmt = $shardDb->prepare("
            SELECT COUNT(*) as count 
            FROM SRO_VT_SHARD.dbo._Char 
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
 * Lấy CharID từ CharName
 * Sử dụng SRO_VT_SHARD.dbo._Char
 * 
 * @param string $charName Tên nhân vật
 * @param PDO $shardDb Shard database connection
 * @return int|null CharID hoặc null nếu không tìm thấy
 */
function getCharIDFromName($charName, $shardDb) {
    try {
        $stmt = $shardDb->prepare("
            SELECT TOP 1 CharID 
            FROM SRO_VT_SHARD.dbo._Char 
            WHERE CharName16 = ?
        ");
        $stmt->execute([$charName]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['CharID'] : null;
    } catch (Exception $e) {
        error_log("Error getting CharID from name: " . $e->getMessage());
        return null;
    }
}

/**
 * Thêm item vào game qua bảng _InstantItemDelivery
 * Sử dụng SRO_VT_SHARD.dbo._Char để lấy CharID
 * Sử dụng SRO_VT_FILTER.dbo._InstantItemDelivery để insert item
 * 
 * @param string $charName Tên nhân vật
 * @param string $codeItem Mã item (ví dụ: 'ITEM_MALL_QUIVER')
 * @param int $count Số lượng
 * @param PDO $shardDb Shard database connection (để lấy CharID từ SRO_VT_SHARD)
 * @param PDO|null $filterDb Filter database connection (null thì dùng shardDb với database prefix)
 * @param string $filterDatabase Tên database FILTER (mặc định: 'SRO_VT_FILTER')
 * @return bool True nếu thành công
 */
function addItemToCharacterViaInstantDelivery($charName, $codeItem, $count = 1, $shardDb, $filterDb = null, $filterDatabase = 'SRO_VT_FILTER') {
    try {
        // 1. Lấy CharID từ CharName (từ SRO_VT_SHARD.dbo._Char)
        $charID = getCharIDFromName($charName, $shardDb);
        if ($charID === null) {
            error_log("Character not found: $charName");
            return false;
        }
        
        // 2. Xác định database connection cho FILTER
        $db = $filterDb;
        if ($db === null) {
            // Nếu không có filterDb riêng, dùng shardDb với database prefix
            $db = $shardDb;
        }
        
        // 3. Insert vào SRO_VT_FILTER.dbo._InstantItemDelivery
        // Nếu dùng cùng connection, cần chỉ định database trong query
        if ($filterDb === null) {
            // Dùng database prefix trong query
            $stmt = $db->prepare("
                INSERT INTO [{$filterDatabase}].[dbo].[_InstantItemDelivery]
                    ([CharID], [StorageType], [CodeName], [Count], [Plus], [AddMagParams], [MagParams], [VarianceRand])
                VALUES (?, 0, ?, ?, 0, NULL, NULL, NULL)
            ");
        } else {
            // Dùng connection riêng cho FILTER database
            $stmt = $db->prepare("
                INSERT INTO [dbo].[_InstantItemDelivery]
                    ([CharID], [StorageType], [CodeName], [Count], [Plus], [AddMagParams], [MagParams], [VarianceRand])
                VALUES (?, 0, ?, ?, 0, NULL, NULL, NULL)
            ");
        }
        
        $stmt->execute([$charID, $codeItem, $count]);
        error_log("Successfully added item {$codeItem} (count: {$count}) to character {$charName} (CharID: {$charID})");
        return true;
    } catch (Exception $e) {
        error_log("Error adding item via InstantItemDelivery: " . $e->getMessage());
        return false;
    }
}

/**
 * Thêm nhiều item vào game cùng lúc qua bảng _InstantItemDelivery
 * Sử dụng SRO_VT_SHARD.dbo._Char để lấy CharID
 * Sử dụng SRO_VT_FILTER.dbo._InstantItemDelivery để insert items
 * 
 * @param string $charName Tên nhân vật
 * @param array $items Mảng các item: [['codeName' => 'ITEM_MALL_QUIVER', 'count' => 1], ...]
 * @param PDO $shardDb Shard database connection (để lấy CharID từ SRO_VT_SHARD)
 * @param PDO|null $filterDb Filter database connection (null thì dùng shardDb với database prefix)
 * @param string $filterDatabase Tên database FILTER (mặc định: 'SRO_VT_FILTER')
 * @return array ['success' => bool, 'added' => int, 'failed' => int, 'errors' => array]
 */
function addMultipleItemsToCharacter($charName, $items, $shardDb, $filterDb = null, $filterDatabase = 'SRO_VT_FILTER') {
    $result = [
        'success' => true,
        'added' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    try {
        // 1. Lấy CharID từ CharName (từ SRO_VT_SHARD.dbo._Char)
        $charID = getCharIDFromName($charName, $shardDb);
        if ($charID === null) {
            $result['success'] = false;
            $result['errors'][] = "Character not found: $charName";
            error_log("Character not found in SRO_VT_SHARD.dbo._Char: $charName");
            return $result;
        }
        
        error_log("Found CharID {$charID} for character {$charName}, adding " . count($items) . " items");
        
        // 2. Xác định database connection cho FILTER
        $db = $filterDb;
        if ($db === null) {
            $db = $shardDb;
        }
        
        // 3. Insert nhiều item cùng lúc vào SRO_VT_FILTER.dbo._InstantItemDelivery
        foreach ($items as $item) {
            $codeName = $item['codeName'] ?? $item['codeItem'] ?? '';
            $count = (int)($item['count'] ?? $item['quanlity'] ?? 1);
            
            if (empty($codeName)) {
                $result['failed']++;
                $result['errors'][] = "Invalid item code";
                continue;
            }
            
            try {
                if ($filterDb === null) {
                    // Dùng database prefix trong query
                    $stmt = $db->prepare("
                        INSERT INTO [{$filterDatabase}].[dbo].[_InstantItemDelivery]
                            ([CharID], [StorageType], [CodeName], [Count], [Plus], [AddMagParams], [MagParams], [VarianceRand])
                        VALUES (?, 0, ?, ?, 0, NULL, NULL, NULL)
                    ");
                } else {
                    // Dùng connection riêng cho FILTER database
                    $stmt = $db->prepare("
                        INSERT INTO [dbo].[_InstantItemDelivery]
                            ([CharID], [StorageType], [CodeName], [Count], [Plus], [AddMagParams], [MagParams], [VarianceRand])
                        VALUES (?, 0, ?, ?, 0, NULL, NULL, NULL)
                    ");
                }
                
                $stmt->execute([$charID, $codeName, $count]);
                $result['added']++;
                error_log("Successfully added item {$codeName} (count: {$count}) to character {$charName} (CharID: {$charID})");
            } catch (Exception $e) {
                $result['failed']++;
                $result['errors'][] = "Failed to add {$codeName}: " . $e->getMessage();
                error_log("Error adding item {$codeName} to character {$charName}: " . $e->getMessage());
            }
        }
        
        $result['success'] = ($result['failed'] == 0);
        error_log("addMultipleItemsToCharacter completed: added={$result['added']}, failed={$result['failed']}, success={$result['success']}");
        return $result;
    } catch (Exception $e) {
        $result['success'] = false;
        $result['errors'][] = $e->getMessage();
        error_log("Error in addMultipleItemsToCharacter: " . $e->getMessage());
        return $result;
    }
}

/**
 * Thêm item vào game qua stored procedure (Cách cũ - giữ lại để tương thích)
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

/**
 * Kiểm tra tính năng nạp tích lũy có đang hoạt động không
 * 
 * @param PDO $db Database connection
 * @return array ['enabled' => bool, 'inTimeRange' => bool, 'message' => string|null, 'config' => array]
 */
function checkTichNapFeatureStatus($db) {
    try {
        $stmt = $db->prepare("
            SELECT TOP 1 FeatureEnabled, EventStartDate, EventEndDate
            FROM TichNapConfig
            ORDER BY UpdatedDate DESC
        ");
        $stmt->execute();
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            return [
                'enabled' => false,
                'inTimeRange' => false,
                'message' => 'Tính năng nạp tích lũy chưa được cấu hình',
                'config' => null
            ];
        }
        
        $featureEnabled = (bool)$config['FeatureEnabled'];
        $eventStartDate = $config['EventStartDate'] ?? null;
        $eventEndDate = $config['EventEndDate'] ?? null;
        
        // Kiểm tra tính năng có bật không
        if (!$featureEnabled) {
            return [
                'enabled' => false,
                'inTimeRange' => false,
                'message' => 'Tính năng nạp tích lũy đang tạm thời tắt',
                'config' => [
                    'featureEnabled' => false,
                    'eventStartDate' => $eventStartDate,
                    'eventEndDate' => $eventEndDate
                ]
            ];
        }
        
        // Kiểm tra thời gian sự kiện
        $now = new DateTime();
        $inTimeRange = true;
        $message = null;
        
        if ($eventStartDate) {
            $startDate = new DateTime($eventStartDate);
            if ($now < $startDate) {
                $inTimeRange = false;
                $message = 'Sự kiện nạp tích lũy chưa bắt đầu. Thời gian bắt đầu: ' . $startDate->format('d/m/Y H:i');
            }
        }
        
        if ($eventEndDate) {
            $endDate = new DateTime($eventEndDate);
            if ($now > $endDate) {
                $inTimeRange = false;
                $message = 'Sự kiện nạp tích lũy đã kết thúc. Thời gian kết thúc: ' . $endDate->format('d/m/Y H:i');
            }
        }
        
        return [
            'enabled' => true,
            'inTimeRange' => $inTimeRange,
            'message' => $message,
            'config' => [
                'featureEnabled' => true,
                'eventStartDate' => $eventStartDate,
                'eventEndDate' => $eventEndDate
            ]
        ];
    } catch (Exception $e) {
        error_log("Error checking TichNap feature status: " . $e->getMessage());
        return [
            'enabled' => false,
            'inTimeRange' => false,
            'message' => 'Lỗi kiểm tra cấu hình: ' . $e->getMessage(),
            'config' => null
        ];
    }
}

/**
 * Lấy JID từ username
 * 
 * @param string $username Username (StrUserID)
 * @param PDO $db Database connection
 * @return int|null JID hoặc null nếu không tìm thấy
 */
function getJIDFromUsername($username, $db) {
    try {
        $stmt = $db->prepare("
            SELECT JID 
            FROM TB_User 
            WHERE StrUserID = ?
        ");
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['JID'] : null;
    } catch (Exception $e) {
        error_log("Error getting JID from username {$username}: " . $e->getMessage());
        return null;
    }
}

/**
 * Reset tích lũy cho user (set AccumulatedDeposit = 0)
 * 
 * @param int|null $userJID JID của user (null = tất cả users)
 * @param PDO $db Database connection
 * @return array ['success' => bool, 'affected' => int, 'message' => string]
 */
function resetTotalMoney($userJID, $db) {
    try {
        if ($userJID === null) {
            // Reset tất cả users
            $stmt = $db->prepare("
                UPDATE TB_User 
                SET AccumulatedDeposit = 0
            ");
            $stmt->execute();
            $affected = $stmt->rowCount();
            return [
                'success' => true,
                'affected' => $affected,
                'message' => "Đã reset tích lũy cho {$affected} người dùng"
            ];
        } else {
            // Reset user cụ thể
            $stmt = $db->prepare("
                UPDATE TB_User 
                SET AccumulatedDeposit = 0
                WHERE JID = ?
            ");
            $stmt->execute([$userJID]);
            $affected = $stmt->rowCount();
            return [
                'success' => true,
                'affected' => $affected,
                'message' => "Đã reset tích lũy cho user JID {$userJID}"
            ];
        }
    } catch (Exception $e) {
        error_log("Error resetting total money: " . $e->getMessage());
        return [
            'success' => false,
            'affected' => 0,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}

/**
 * Cộng tích lũy cho user (cộng vào AccumulatedDeposit trong TB_User)
 * 
 * @param int|null $userJID JID của user (null = tất cả users)
 * @param int $amount Số tiền cộng thêm
 * @param PDO $db Database connection
 * @return array ['success' => bool, 'affected' => int, 'message' => string]
 */
function addTotalMoney($userJID, $amount, $db) {
    try {
        error_log("addTotalMoney called - userJID: " . ($userJID ?? 'null') . ", amount: {$amount}");
        
        if ($userJID === null) {
            // Cộng cho tất cả users
            $sql = "
                UPDATE TB_User 
                SET AccumulatedDeposit = AccumulatedDeposit + ?
                WHERE JID IS NOT NULL
            ";
            error_log("addTotalMoney - Executing SQL for all users: " . $sql);
            $stmt = $db->prepare($sql);
            $stmt->execute([$amount]);
            $affected = $stmt->rowCount();
            
            // Nếu rowCount() trả về 0, có thể do driver không hỗ trợ, thử query lại để đếm
            if ($affected === 0) {
                $countStmt = $db->query("SELECT COUNT(*) as cnt FROM TB_User WHERE JID IS NOT NULL");
                $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
                $totalUsers = $countResult['cnt'] ?? 0;
                error_log("addTotalMoney - rowCount returned 0, but total users: {$totalUsers}");
                $affected = $totalUsers; // Sử dụng số lượng users thực tế
            }
            
            error_log("addTotalMoney - Affected rows: {$affected}");
            
            return [
                'success' => true,
                'affected' => $affected,
                'message' => "Đã cộng " . number_format($amount) . " VND tích lũy cho {$affected} người dùng"
            ];
        } else {
            // Cộng cho user cụ thể
            $sql = "
                UPDATE TB_User 
                SET AccumulatedDeposit = AccumulatedDeposit + ?
                WHERE JID = ?
            ";
            error_log("addTotalMoney - Executing SQL for user JID {$userJID}: " . $sql);
            $stmt = $db->prepare($sql);
            $stmt->execute([$amount, $userJID]);
            $affected = $stmt->rowCount();
            
            error_log("addTotalMoney - Affected rows: {$affected}");
            
            if ($affected === 0) {
                return [
                    'success' => false,
                    'affected' => 0,
                    'message' => "Không tìm thấy user JID {$userJID}"
                ];
            }
            
            return [
                'success' => true,
                'affected' => $affected,
                'message' => "Đã cộng " . number_format($amount) . " VND tích lũy cho user JID {$userJID}"
            ];
        }
    } catch (Exception $e) {
        error_log("Error adding total money: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        return [
            'success' => false,
            'affected' => 0,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}

