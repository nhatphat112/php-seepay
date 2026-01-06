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
 * Lấy CharID từ CharName
 * 
 * @param string $charName Tên nhân vật
 * @param PDO $shardDb Shard database connection
 * @return int|null CharID hoặc null nếu không tìm thấy
 */
function getCharIDFromName($charName, $shardDb) {
    try {
        $stmt = $shardDb->prepare("
            SELECT TOP 1 CharID 
            FROM _Char 
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
 * Thêm item vào game qua bảng _InstantItemDelivery (Cách mới)
 * 
 * @param string $charName Tên nhân vật
 * @param string $codeItem Mã item (ví dụ: 'ITEM_MALL_QUIVER')
 * @param int $count Số lượng
 * @param PDO $shardDb Shard database connection (để lấy CharID)
 * @param PDO|null $filterDb Filter database connection (null thì dùng shardDb với database prefix)
 * @param string $filterDatabase Tên database FILTER (mặc định: 'SRO_VT_FILTER')
 * @return bool True nếu thành công
 */
function addItemToCharacterViaInstantDelivery($charName, $codeItem, $count = 1, $shardDb, $filterDb = null, $filterDatabase = 'SRO_VT_FILTER') {
    try {
        // 1. Lấy CharID từ CharName
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
        
        // 3. Insert vào _InstantItemDelivery
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
        return true;
    } catch (Exception $e) {
        error_log("Error adding item via InstantItemDelivery: " . $e->getMessage());
        return false;
    }
}

/**
 * Thêm nhiều item vào game cùng lúc qua bảng _InstantItemDelivery
 * 
 * @param string $charName Tên nhân vật
 * @param array $items Mảng các item: [['codeName' => 'ITEM_MALL_QUIVER', 'count' => 1], ...]
 * @param PDO $shardDb Shard database connection
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
        // 1. Lấy CharID từ CharName
        $charID = getCharIDFromName($charName, $shardDb);
        if ($charID === null) {
            $result['success'] = false;
            $result['errors'][] = "Character not found: $charName";
            return $result;
        }
        
        // 2. Xác định database connection cho FILTER
        $db = $filterDb;
        if ($db === null) {
            $db = $shardDb;
        }
        
        // 3. Insert nhiều item cùng lúc
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
                    $stmt = $db->prepare("
                        INSERT INTO [{$filterDatabase}].[dbo].[_InstantItemDelivery]
                            ([CharID], [StorageType], [CodeName], [Count], [Plus], [AddMagParams], [MagParams], [VarianceRand])
                        VALUES (?, 0, ?, ?, 0, NULL, NULL, NULL)
                    ");
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO [dbo].[_InstantItemDelivery]
                            ([CharID], [StorageType], [CodeName], [Count], [Plus], [AddMagParams], [MagParams], [VarianceRand])
                        VALUES (?, 0, ?, ?, 0, NULL, NULL, NULL)
                    ");
                }
                
                $stmt->execute([$charID, $codeName, $count]);
                $result['added']++;
            } catch (Exception $e) {
                $result['failed']++;
                $result['errors'][] = "Failed to add {$codeName}: " . $e->getMessage();
                error_log("Error adding item {$codeName}: " . $e->getMessage());
            }
        }
        
        $result['success'] = ($result['failed'] == 0);
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

