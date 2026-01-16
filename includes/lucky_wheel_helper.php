<?php
/**
 * Lucky Wheel Helper Functions
 * 
 * Workflow functions for lucky wheel feature:
 * - Get wheel items
 * - Calculate spin result based on win rates
 * - Process spin and deduct silk
 * - Claim rewards
 */

require_once __DIR__ . '/../connection_manager.php';

/**
 * Get active wheel items
 * Returns list of items sorted by display order
 */
function getLuckyWheelItems($includeInactive = false) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $sql = "
            SELECT 
                Id,
                ItemName,
                ItemCode,
                Quantity,
                IsRare,
                WinRate,
                DisplayOrder,
                IsActive
            FROM LuckyWheelItems
        ";
        
        if (!$includeInactive) {
            $sql .= " WHERE IsActive = 1";
        }
        
        $sql .= " ORDER BY DisplayOrder ASC, Id ASC";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting lucky wheel items: " . $e->getMessage());
        return [];
    }
}

/**
 * Get wheel configuration
 */
function getLuckyWheelConfig() {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $stmt = $db->query("
            SELECT TOP 1 
                FeatureEnabled,
                SpinCost
            FROM LuckyWheelConfig
            ORDER BY Id DESC
        ");
        
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            // Return default config
            return [
                'FeatureEnabled' => true,
                'SpinCost' => 10
            ];
        }
        
        return [
            'FeatureEnabled' => (bool)$config['FeatureEnabled'],
            'SpinCost' => intval($config['SpinCost'] ?? 10)
        ];
    } catch (Exception $e) {
        error_log("Error getting lucky wheel config: " . $e->getMessage());
        return [
            'FeatureEnabled' => false,
            'SpinCost' => 10
        ];
    }
}

/**
 * Calculate spin result based on win rates using Weighted Random algorithm
 * 
 * Algorithm:
 * 1. Calculate total win rate (sum of all WinRate values)
 * 2. Generate random number between 0 and totalRate
 * 3. Find item where cumulative rate >= random number
 * 
 * Example:
 * - Item A: 50%, Item B: 30%, Item C: 20%
 * - Total = 100
 * - Random = 45.67
 * - Cumulative: A=50 (45.67 <= 50? No), B=80 (45.67 <= 80? Yes) → Return B
 * 
 * @return array Item that was won
 * @throws Exception If no items available or calculation fails
 */
function calculateSpinResult() {
    try {
        $items = getLuckyWheelItems();
        
        if (empty($items)) {
            throw new Exception("No active items in lucky wheel");
        }
        
        // Filter out items with invalid win rates
        $validItems = [];
        $totalRate = 0;
        
        foreach ($items as $item) {
            $winRate = floatval($item['WinRate']);
            if ($winRate > 0) {
                $validItems[] = $item;
                $totalRate += $winRate;
            }
        }
        
        if (empty($validItems)) {
            throw new Exception("No valid items with win rate > 0");
        }
        
        // Generate random number with higher precision (0.0001 instead of 0.01)
        // This handles small win rates better (e.g., 0.01%)
        $maxValue = intval($totalRate * 10000); // Precision: 0.0001
        if ($maxValue <= 0) {
            throw new Exception("Total win rate is too small");
        }
        
        $random = mt_rand(0, $maxValue) / 10000;
        
        // Linear search through cumulative ranges
        // O(n) complexity, but fast enough for typical wheel (< 20 items)
        $cumulative = 0;
        foreach ($validItems as $item) {
            $cumulative += floatval($item['WinRate']);
            // Use <= to ensure we always find an item
            if ($random <= $cumulative) {
                return $item;
            }
        }
        
        // Fallback: return last item (shouldn't happen due to <= check above)
        // But kept for safety
        return end($validItems);
        
    } catch (Exception $e) {
        error_log("Error calculating spin result: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Process spin - deduct silk and create log/reward
 * Returns array with spin result
 */
function processSpin($userJID, $spinCount = 1) {
    try {
        $db = ConnectionManager::getAccountDB();
        $logDb = ConnectionManager::getLogDB();
        
        // Get config
        $config = getLuckyWheelConfig();
        if (!$config['FeatureEnabled']) {
            throw new Exception("Lucky wheel feature is disabled");
        }
        
        $totalCost = $config['SpinCost'] * $spinCount;
        
        // Check user silk
        $silkStmt = $db->prepare("SELECT silk_own FROM SK_Silk WHERE JID = ?");
        $silkStmt->execute([$userJID]);
        $silk = $silkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$silk || intval($silk['silk_own']) < $totalCost) {
            throw new Exception("Not enough silk. Required: $totalCost, Available: " . ($silk['silk_own'] ?? 0));
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Deduct silk
            $updateSilk = $db->prepare("UPDATE SK_Silk SET silk_own = silk_own - ? WHERE JID = ?");
            $updateSilk->execute([$totalCost, $userJID]);
            
            $results = [];
            
            // Process each spin
            for ($i = 0; $i < $spinCount; $i++) {
                // Calculate result
                $wonItem = calculateSpinResult();
                
                // Create log entry
                $logStmt = $db->prepare("
                    INSERT INTO LuckyWheelLog 
                    (UserJID, ItemId, ItemName, ItemCode, Quantity, IsRare, SpinDate)
                    VALUES (?, ?, ?, ?, ?, ?, GETDATE())
                ");
                $logStmt->execute([
                    $userJID,
                    $wonItem['Id'],
                    $wonItem['ItemName'],
                    $wonItem['ItemCode'],
                    $wonItem['Quantity'],
                    $wonItem['IsRare'] ? 1 : 0
                ]);
                
                $logId = $db->lastInsertId();
                
                // Create reward entry
                $rewardStmt = $db->prepare("
                    INSERT INTO LuckyWheelRewards
                    (UserJID, LogId, ItemId, ItemName, ItemCode, Quantity, IsRare, Status, WonDate)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', GETDATE())
                ");
                $rewardStmt->execute([
                    $userJID,
                    $logId,
                    $wonItem['Id'],
                    $wonItem['ItemName'],
                    $wonItem['ItemCode'],
                    $wonItem['Quantity'],
                    $wonItem['IsRare'] ? 1 : 0
                ]);
                
                $results[] = [
                    'item_id' => intval($wonItem['Id']),
                    'item_name' => $wonItem['ItemName'],
                    'item_code' => $wonItem['ItemCode'],
                    'quantity' => intval($wonItem['Quantity']),
                    'is_rare' => (bool)$wonItem['IsRare'],
                    'log_id' => intval($logId)
                ];
            }
            
            // Commit transaction
            $db->commit();
            
            return [
                'success' => true,
                'total_cost' => $totalCost,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Error processing spin: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Get user pending rewards
 */
function getUserPendingRewards($userJID) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $stmt = $db->prepare("
            SELECT 
                Id,
                ItemName,
                ItemCode,
                Quantity,
                IsRare,
                WonDate,
                Status
            FROM LuckyWheelRewards
            WHERE UserJID = ? AND Status = 'pending'
            ORDER BY WonDate DESC
        ");
        $stmt->execute([$userJID]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user pending rewards: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent rare wins (for homepage ticker)
 * Returns list of users who won rare items, ordered by date
 */
function getRecentRareWins($limit = 20) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $stmt = $db->prepare("
            SELECT TOP ?
                lw.UserJID,
                u.StrUserID as Username,
                lw.ItemName,
                lw.WonDate
            FROM LuckyWheelRewards lw
            JOIN TB_User u ON u.JID = lw.UserJID
            WHERE lw.IsRare = 1
            ORDER BY lw.WonDate DESC
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting recent rare wins: " . $e->getMessage());
        return [];
    }
}

/**
 * Claim reward - process reward using tichnap workflow
 * This will use the same workflow as tichnap rewards
 */
function claimLuckyWheelReward($userJID, $rewardId) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        // Get reward info
        $stmt = $db->prepare("
            SELECT 
                Id,
                UserJID,
                ItemCode,
                Quantity,
                Status
            FROM LuckyWheelRewards
            WHERE Id = ? AND UserJID = ? AND Status = 'pending'
        ");
        $stmt->execute([$rewardId, $userJID]);
        $reward = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reward) {
            throw new Exception("Reward not found or already claimed");
        }
        
        // TODO: Integrate with tichnap reward system
        // For now, just mark as claimed
        $updateStmt = $db->prepare("
            UPDATE LuckyWheelRewards
            SET Status = 'claimed', ClaimedDate = GETDATE()
            WHERE Id = ?
        ");
        $updateStmt->execute([$rewardId]);
        
        return [
            'success' => true,
            'item_code' => $reward['ItemCode'],
            'quantity' => intval($reward['Quantity'])
        ];
        
    } catch (Exception $e) {
        error_log("Error claiming reward: " . $e->getMessage());
        throw $e;
    }
}

?>
