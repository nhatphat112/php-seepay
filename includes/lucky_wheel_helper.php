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
 * Get last order ID for silk transaction
 * Returns a new order ID based on timestamp
 */
function getLastOrderId() {
    try {
        // Generate order ID based on timestamp and random number
        // Format: LW{timestamp}{random} (LW = Lucky Wheel)
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return 'LW' . $timestamp . $random;
    } catch (Exception $e) {
        error_log("Error generating order ID: " . $e->getMessage());
        // Fallback: use timestamp only
        return 'LW' . time();
    }
}

/**
 * Deduct silk using stored procedure CGI.CGI_WebPurchaseSilk
 * 
 * @param int $userJID User JID (integer ID)
 * @param int $silkAmount Amount to deduct (negative value)
 * @return string Result from stored procedure, empty string on error
 */
function deductSilk($userJID, $silkAmount) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        // Get username from userJID
        // @UserID in stored procedure is username (varchar), not JID
        $userStmt = $db->prepare("SELECT StrUserID FROM TB_User WHERE JID = ?");
        $userStmt->execute([$userJID]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || empty($user['StrUserID'])) {
            throw new Exception("User not found or username is empty for JID: $userJID");
        }
        
        $username = $user['StrUserID']; // This is the username, not JID
        
        // Get order ID
        $orderID = getLastOrderId();
        
        // Prepare stored procedure call
        // Parameters from system query: @OrderID, @UserID (username), @PkgID, @NumSilk, @Price
        $query = "EXEC CGI.CGI_WebPurchaseSilk @OrderID = ?, @UserID = ?, @PkgID = ?, @NumSilk = ?, @Price = ?";
        
        $stmt = $db->prepare($query);
        
        // Parameters based on stored procedure signature
        $stmt->execute([
            $orderID,           // @OrderID (varchar, 25)
            $username,          // @UserID (varchar, 25) - username, not JID
            0,                  // @PkgID (int) - package ID, set to 0 for lucky wheel
            $silkAmount,        // @NumSilk (int) - negative to deduct
            abs($silkAmount)    // @Price (int) - absolute value, positive
        ]);
        
        // Stored procedure may not return a result set
        // If execution succeeds without exception, consider it successful
        $resultString = 'SUCCESS';
        
        // Try to get result if available (some stored procedures return values)
        try {
            // Check if there's a result set
            if ($stmt->columnCount() > 0) {
                $result = $stmt->fetchColumn(0);
                if ($result !== false) {
                    $resultString = (string)$result;
                }
            } else {
                // Try next result set if available
                if ($stmt->nextRowset()) {
                    if ($stmt->columnCount() > 0) {
                        $result = $stmt->fetchColumn(0);
                        if ($result !== false) {
                            $resultString = (string)$result;
                        }
                    }
                }
            }
        } catch (Exception $fetchError) {
            // If no result set, that's okay - stored procedure executed successfully
            // The absence of exception means the procedure completed
            error_log("Stored procedure executed but no result set returned (this may be normal): " . $fetchError->getMessage());
        }
        
        // Log transaction
        error_log("Silk deduction - OrderID: $orderID, UserJID: $userJID, Username: $username, Amount: $silkAmount, Result: $resultString");
        return $resultString;
        
    } catch (Exception $e) {
        error_log("Error deducting silk via stored procedure: " . $e->getMessage());
        error_log("  - UserJID: $userJID, Amount: $silkAmount");
        return '';
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
        
        // Check and update season before processing spin
        // This ensures user always spins in the correct active season
        $currentSeason = checkAndUpdateSeason();
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Trừ silk trong game bằng stored procedure
            // Note: deductSilk will get username from userJID internally
            $silkResult = deductSilk($userJID, -$totalCost); // Negative value to deduct
            error_log("LuckyWheel processSpin: silkResult = " . var_export($silkResult, true));
            
            if (empty($silkResult)) {
                throw new Exception("Lỗi khi trừ silk trong game, thử lại sau!");
            }
            
            // Update total spins (accumulate)
            $updateSpins = $db->prepare("
                UPDATE TB_User 
                SET TotalSpins = ISNULL(TotalSpins, 0) + ?
                WHERE JID = ?
            ");
            $updateSpins->execute([$spinCount, $userJID]);
            
            // Update season log for leaderboard
            if ($currentSeason) {
                updateSeasonLog($userJID, $spinCount, $currentSeason['Id']);
            }
            
            $results = [];
            
            // Process each spin
            for ($i = 0; $i < $spinCount; $i++) {
                // Calculate result
                $wonItem = calculateSpinResult();
                
                // Create log entry
                // Convert IsRare to integer (handle both boolean and integer from database)
                $isRare = 0;
                if (isset($wonItem['IsRare'])) {
                    if (is_bool($wonItem['IsRare'])) {
                        $isRare = $wonItem['IsRare'] ? 1 : 0;
                    } else {
                        $isRare = (intval($wonItem['IsRare']) == 1) ? 1 : 0;
                    }
                }
                
                // Get current season ID for logging
                $seasonIdForLog = $currentSeason ? $currentSeason['Id'] : null;
                
                // Check if SeasonId column exists before inserting
                $logStmt = $db->prepare("
                    INSERT INTO LuckyWheelLog 
                    (UserJID, ItemId, ItemName, ItemCode, Quantity, IsRare, SpinDate" . 
                    ($seasonIdForLog !== null ? ", SeasonId" : "") . ")
                    VALUES (?, ?, ?, ?, ?, ?, GETDATE()" . 
                    ($seasonIdForLog !== null ? ", ?" : "") . ")
                ");
                
                $logParams = [
                    $userJID,
                    $wonItem['Id'],
                    $wonItem['ItemName'],
                    $wonItem['ItemCode'],
                    $wonItem['Quantity'],
                    $isRare
                ];
                
                if ($seasonIdForLog !== null) {
                    $logParams[] = $seasonIdForLog;
                }
                
                $logStmt->execute($logParams);
                
                $logId = $db->lastInsertId();
                
                // Create reward entry
                // Convert IsRare to integer (handle both boolean and integer from database)
                $isRare = 0;
                if (isset($wonItem['IsRare'])) {
                    if (is_bool($wonItem['IsRare'])) {
                        $isRare = $wonItem['IsRare'] ? 1 : 0;
                    } else {
                        $isRare = (intval($wonItem['IsRare']) == 1) ? 1 : 0;
                    }
                }
                
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
                    $isRare
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
        
        // Query rare wins for the homepage ticker.
        // Prefer ClaimedDate if available; otherwise use WonDate.
        // IsRare is BIT in SQL Server - direct comparison works.
        // Order by the most recent timestamp (ClaimedDate fallback to WonDate).
        // SQL Server requires FETCH parameter to be explicitly bound as INT.
        $limit = max(1, min(100, intval($limit))); // Ensure limit is between 1 and 100

        // Filter to "today" only (server timezone). Use a half-open range: [today 00:00, tomorrow 00:00).
        $todayStart = (new DateTime('today'))->format('Y-m-d H:i:s');
        $tomorrowStart = (new DateTime('tomorrow'))->format('Y-m-d H:i:s');
        
        $stmt = $db->prepare("
            SELECT 
                lw.UserJID,
                u.StrUserID as Username,
                lw.ItemName,
                CASE 
                    WHEN lw.ClaimedDate IS NOT NULL THEN lw.ClaimedDate
                    ELSE lw.WonDate
                END as WonDate
            FROM LuckyWheelRewards lw
            INNER JOIN TB_User u ON u.JID = lw.UserJID
            WHERE lw.IsRare = 1
              AND (
                CASE 
                  WHEN lw.ClaimedDate IS NOT NULL THEN lw.ClaimedDate
                  ELSE lw.WonDate
                END
              ) >= ?
              AND (
                CASE 
                  WHEN lw.ClaimedDate IS NOT NULL THEN lw.ClaimedDate
                  ELSE lw.WonDate
                END
              ) < ?
            ORDER BY 
                CASE 
                    WHEN lw.ClaimedDate IS NOT NULL THEN lw.ClaimedDate
                    ELSE lw.WonDate
                END DESC
            OFFSET 0 ROWS
            FETCH NEXT ? ROWS ONLY
        ");
        
        $stmt->bindValue(1, $todayStart);
        $stmt->bindValue(2, $tomorrowStart);
        // Bind parameter as INT (required by SQL Server for FETCH)
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Log for debugging
        if (empty($results)) {
            error_log("No rare wins found in database. Checking if any rare items exist...");
            
            // Debug query 1: Check total rewards and rare counts (using direct BIT comparison)
            try {
                $debugStmt = $db->query("
                    SELECT 
                        COUNT(*) as total, 
                        SUM(CASE WHEN IsRare = 1 THEN 1 ELSE 0 END) as rare_count,
                        SUM(CASE WHEN IsRare = 1 AND Status = 'claimed' THEN 1 ELSE 0 END) as rare_claimed_count,
                        SUM(CASE WHEN IsRare = 1 AND Status = 'pending' THEN 1 ELSE 0 END) as rare_pending_count
                    FROM LuckyWheelRewards
                ");
                $debug = $debugStmt->fetch(PDO::FETCH_ASSOC);
                error_log("Total rewards: " . ($debug['total'] ?? 0) . 
                         ", Rare rewards: " . ($debug['rare_count'] ?? 0) . 
                         ", Rare claimed: " . ($debug['rare_claimed_count'] ?? 0) .
                         ", Rare pending: " . ($debug['rare_pending_count'] ?? 0));
            } catch (Exception $e) {
                error_log("Error in debug query 1: " . $e->getMessage());
            }
            
            // Debug query 2: Check sample rare rewards (using direct BIT comparison)
            try {
                $sampleStmt = $db->query("
                    SELECT TOP 5
                        Id, UserJID, ItemName, 
                        IsRare,
                        Status, WonDate, ClaimedDate
                    FROM LuckyWheelRewards
                    WHERE IsRare = 1
                    ORDER BY WonDate DESC
                ");
                $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($samples)) {
                    error_log("Sample rare rewards found: " . count($samples));
                    foreach ($samples as $sample) {
                        // Convert BIT to readable value
                        $isRareValue = $sample['IsRare'];
                        if (is_bool($isRareValue)) {
                            $isRareValue = $isRareValue ? 1 : 0;
                        } elseif (is_string($isRareValue)) {
                            $isRareValue = ($isRareValue === '1' || $isRareValue === 'true') ? 1 : 0;
                        }
                        error_log("  - ID: " . $sample['Id'] . 
                                 ", Status: " . $sample['Status'] . 
                                 ", IsRare: " . $isRareValue .
                                 ", ClaimedDate: " . ($sample['ClaimedDate'] ?? 'NULL'));
                    }
                } else {
                    error_log("No rare rewards found in sample query");
                }
            } catch (Exception $e) {
                error_log("Error in debug query 2: " . $e->getMessage());
            }
            
            // Debug query 3: Check if there are any claimed rewards at all
            try {
                $claimedStmt = $db->query("
                    SELECT COUNT(*) as claimed_count
                    FROM LuckyWheelRewards
                    WHERE Status = 'claimed' AND ClaimedDate IS NOT NULL
                ");
                $claimed = $claimedStmt->fetch(PDO::FETCH_ASSOC);
                error_log("Total claimed rewards: " . ($claimed['claimed_count'] ?? 0));
            } catch (Exception $e) {
                error_log("Error in debug query 3: " . $e->getMessage());
            }
        } else {
            error_log("Found " . count($results) . " rare wins");
        }
        
        return $results;
    } catch (Exception $e) {
        error_log("Error getting recent rare wins: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
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

/**
 * Get accumulated spin items (for admin and user)
 */
function getAccumulatedSpinItems($includeInactive = false) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $sql = "
            SELECT 
                Id,
                ItemName,
                ItemCode,
                Quantity,
                RequiredSpins,
                DisplayOrder,
                IsActive
            FROM LuckyWheelAccumulatedItems
        ";
        
        if (!$includeInactive) {
            $sql .= " WHERE IsActive = 1";
        }
        
        $sql .= " ORDER BY RequiredSpins ASC, DisplayOrder ASC, Id ASC";
        
        $stmt = $db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting accumulated spin items: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's total spins
 */
/**
 * Get user's total spins for the current active season.
 * This is used for accumulated rewards, making them season-based.
 */
function getUserTotalSpinsForCurrentSeason($userJID) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $currentSeason = getCurrentSeason();
        if (!$currentSeason) {
            return 0; // No active season, no spins.
        }
        
        $stmt = $db->prepare("
            SELECT ISNULL(TotalSpins, 0) as TotalSpins
            FROM LuckyWheelSeasonLog
            WHERE UserJID = ? AND SeasonId = ?
        ");
        $stmt->execute([$userJID, $currentSeason['Id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return intval($result['TotalSpins'] ?? 0);
    } catch (Exception $e) {
        error_log("Error getting user total spins for current season: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get user's LIFETIME total spins (legacy)
 */
function getUserTotalSpins($userJID) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $stmt = $db->prepare("
            SELECT ISNULL(TotalSpins, 0) as TotalSpins
            FROM TB_User
            WHERE JID = ?
        ");
        $stmt->execute([$userJID]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return intval($result['TotalSpins'] ?? 0);
    } catch (Exception $e) {
        error_log("Error getting user total spins: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get available accumulated rewards for user
 * Returns items that user has reached but not yet claimed
 */
function getAvailableAccumulatedRewards($userJID) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $totalSpins = getUserTotalSpinsForCurrentSeason($userJID);
        
        // Get all active accumulated items from table
        $items = getAccumulatedSpinItems(false);
        
        // Get items user has already claimed
        $claimedStmt = $db->prepare("
            SELECT AccumulatedItemId
            FROM LuckyWheelAccumulatedLog
            WHERE UserJID = ?
        ");
        $claimedStmt->execute([$userJID]);
        $claimedItems = $claimedStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Only return items from active table (not from log)
        // Deleted items (IsActive = 0) will not be displayed, but users can still claim from their rewards
        // Rewards are stored in LuckyWheelAccumulatedLog with full item info, so claim API can work independently
        $allItems = $items;
        
        // Return ALL items with can_claim status
        // This allows UI to show progress for all rewards, not just claimable ones
        $available = [];
        foreach ($allItems as $item) {
            $itemId = intval($item['Id']);
            $requiredSpins = intval($item['RequiredSpins']);
            $hasReached = $totalSpins >= $requiredSpins;
            $hasClaimed = in_array($itemId, $claimedItems);
            $canClaim = $hasReached && !$hasClaimed;
            
            $available[] = [
                'id' => $itemId,
                'item_name' => $item['ItemName'],
                'item_code' => $item['ItemCode'],
                'quantity' => intval($item['Quantity']),
                'required_spins' => $requiredSpins,
                'total_spins' => $totalSpins,
                'progress' => min(100, ($totalSpins / $requiredSpins) * 100),
                'can_claim' => (bool)$canClaim  // Explicitly cast to boolean for JSON
            ];
        }
        
        return $available;
    } catch (Exception $e) {
        error_log("Error getting available accumulated rewards: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if user has already claimed an accumulated reward
 */
function hasClaimedAccumulatedReward($userJID, $accumulatedItemId) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM LuckyWheelAccumulatedLog
            WHERE UserJID = ? AND AccumulatedItemId = ?
        ");
        $stmt->execute([$userJID, $accumulatedItemId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return intval($result['count'] ?? 0) > 0;
    } catch (Exception $e) {
        error_log("Error checking claimed accumulated reward: " . $e->getMessage());
        return false;
    }
}

// ==========================================
// Leaderboard Functions
// ==========================================

/**
 * Get current active season
 * Returns season array or null if no active season
 */
function getCurrentSeason() {
    try {
        $db = ConnectionManager::getAccountDB();
        
        // Current season = season whose time range wraps "now"
        $stmt = $db->query("
            SELECT TOP 1 
                Id,
                SeasonName,
                SeasonType,
                StartDate,
                EndDate,
                IsActive,
                Status
            FROM LuckyWheelSeasons
            WHERE StartDate <= GETDATE()
              AND EndDate   > GETDATE()
            ORDER BY StartDate DESC
        ");
        
        $season = $stmt->fetch(PDO::FETCH_ASSOC);
        $now = (new DateTime())->format('Y-m-d H:i:s');
        if ($season) {
            error_log("[getCurrentSeason][$now] Id={$season['Id']}, Name={$season['SeasonName']}, Start={$season['StartDate']}, End={$season['EndDate']}");
        } else {
            error_log("[getCurrentSeason][$now] No active season for time window");
        }
        return $season ? $season : null;
    } catch (Exception $e) {
        error_log("Error getting current season: " . $e->getMessage());
        return null;
    }
}

/**
 * Check and update season - auto transition to new season if needed
 * Called when user logs in or spins
 * 
 * @return array Current active season
 */
function checkAndUpdateSeason() {
    try {
        $db = ConnectionManager::getAccountDB();
        
        $db->beginTransaction();
        
        try {
            // 1) End any active season whose EndDate has passed
            $db->prepare("
                UPDATE LuckyWheelSeasons
                SET IsActive = 0,
                    Status = 'ENDED',
                    UpdatedDate = GETDATE()
                WHERE Status = 'ACTIVE'
                  AND EndDate <= GETDATE()
            ")->execute();
            
            // 2) Activate the season that should currently be running (time-based)
            // Pick the latest that wraps now.
            $toActivateStmt = $db->query("
                SELECT TOP 1 Id
                FROM LuckyWheelSeasons
                WHERE StartDate <= GETDATE()
                  AND EndDate   > GETDATE()
                ORDER BY StartDate DESC
            ");
            $seasonToActivate = $toActivateStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($seasonToActivate) {
                // Deactivate others to keep uniqueness, then activate this one
                $db->prepare("
                    UPDATE LuckyWheelSeasons
                    SET IsActive = 0
                    WHERE IsActive = 1 AND Id != ?
                ")->execute([$seasonToActivate['Id']]);
                
                $db->prepare("
                    UPDATE LuckyWheelSeasons
                    SET IsActive = 1,
                        Status = 'ACTIVE',
                        UpdatedDate = GETDATE()
                    WHERE Id = ?
                ")->execute([$seasonToActivate['Id']]);
            } else {
                // No season wraps now: ensure none marked active
                $db->prepare("
                    UPDATE LuckyWheelSeasons
                    SET IsActive = 0
                    WHERE IsActive = 1
                ")->execute();
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        
        // Return current season determined by time window and log snapshot
        $season = getCurrentSeason();
        $now = (new DateTime())->format('Y-m-d H:i:s');
        if ($season) {
            error_log("[SeasonChecker][$now] Active season: ID={$season['Id']}, Name={$season['SeasonName']}, Start={$season['StartDate']}, End={$season['EndDate']}");
        } else {
            error_log("[SeasonChecker][$now] No active season for current time window");
        }
        return $season;
        
    } catch (Exception $e) {
        error_log("Error checking and updating season: " . $e->getMessage());
        // Return current season even if check failed
        return getCurrentSeason();
    }
}

/**
 * Update season log for user when spinning
 * Creates record if not exists, updates if exists
 * 
 * @param int $userJID User JID
 * @param int $spinCount Number of spins
 * @param int $seasonId Season ID (optional, will use current if not provided)
 * @return bool Success
 */
function updateSeasonLog($userJID, $spinCount, $seasonId = null) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        // Get season ID if not provided
        if ($seasonId === null) {
            $season = getCurrentSeason();
            if (!$season) {
                error_log("No active season found, skipping season log update");
                return false;
            }
            $seasonId = $season['Id'];
        }
        
        // Get username
        $userStmt = $db->prepare("SELECT StrUserID FROM TB_User WHERE JID = ?");
        $userStmt->execute([$userJID]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || empty($user['StrUserID'])) {
            error_log("User not found for JID: $userJID");
            return false;
        }
        
        $username = $user['StrUserID'];
        
        // Check if record exists
        $checkStmt = $db->prepare("
            SELECT Id, TotalSpins 
            FROM LuckyWheelSeasonLog 
            WHERE SeasonId = ? AND UserJID = ?
        ");
        $checkStmt->execute([$seasonId, $userJID]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing record
            $updateStmt = $db->prepare("
                UPDATE LuckyWheelSeasonLog 
                SET TotalSpins = TotalSpins + ?,
                    LastSpinDate = GETDATE(),
                    UpdatedDate = GETDATE()
                WHERE Id = ?
            ");
            $updateStmt->execute([$spinCount, $existing['Id']]);
        } else {
            // Insert new record
            $insertStmt = $db->prepare("
                INSERT INTO LuckyWheelSeasonLog 
                (SeasonId, UserJID, Username, TotalSpins, LastSpinDate)
                VALUES (?, ?, ?, ?, GETDATE())
            ");
            $insertStmt->execute([$seasonId, $userJID, $username, $spinCount]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error updating season log: " . $e->getMessage());
        return false;
    }
}

/**
 * Get leaderboard for a season (top 5)
 * 
 * @param int $seasonId Season ID (optional, will use current if not provided)
 * @param int $limit Limit number of results (default 5)
 * @return array Leaderboard data with rank
 */
function getLeaderboard($seasonId = null, $limit = 5) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        // Get season ID if not provided
        if ($seasonId === null) {
            $season = getCurrentSeason();
            if (!$season) {
                return [];
            }
            $seasonId = $season['Id'];
        }
        
        // SQL Server doesn't allow parameter in TOP clause, use dynamic SQL or subquery
        $limit = intval($limit);
        $stmt = $db->prepare("
            SELECT TOP ($limit)
                UserJID,
                Username,
                TotalSpins,
                LastSpinDate,
                ROW_NUMBER() OVER (ORDER BY TotalSpins DESC, LastSpinDate ASC) as Rank
            FROM LuckyWheelSeasonLog
            WHERE SeasonId = ?
            ORDER BY TotalSpins DESC, LastSpinDate ASC
        ");
        
        $stmt->execute([$seasonId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert Rank to integer
        foreach ($results as &$result) {
            $result['Rank'] = intval($result['Rank']);
        }
        
        return $results;
        
    } catch (Exception $e) {
        error_log("Error getting leaderboard: " . $e->getMessage());
        return [];
    }
}

/**
 * Get season history (last N seasons)
 * 
 * @param int $limit Number of seasons to return (default 3)
 * @return array Seasons with top 5 leaderboard for each
 */
function getSeasonHistory($limit = 3) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        // SQL Server doesn't allow parameter in TOP clause, use dynamic SQL
        $limit = intval($limit);
        $stmt = $db->prepare("
            SELECT TOP ($limit)
                Id,
                SeasonName,
                SeasonType,
                StartDate,
                EndDate,
                Status
            FROM LuckyWheelSeasons
            WHERE EndDate < GETDATE()
            ORDER BY StartDate DESC
        ");
        
        $stmt->execute();
        $seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get top 5 for each season
        foreach ($seasons as &$season) {
            $season['top_5'] = getLeaderboard($season['Id'], 5);
            
            // Get total participants
            $countStmt = $db->prepare("
                SELECT COUNT(*) as total_participants,
                       SUM(TotalSpins) as total_spins
                FROM LuckyWheelSeasonLog
                WHERE SeasonId = ?
            ");
            $countStmt->execute([$season['Id']]);
            $stats = $countStmt->fetch(PDO::FETCH_ASSOC);
            
            $season['total_participants'] = intval($stats['total_participants'] ?? 0);
            $season['total_spins'] = intval($stats['total_spins'] ?? 0);
        }
        
        return $seasons;
        
    } catch (Exception $e) {
        error_log("Error getting season history: " . $e->getMessage());
        return [];
    }
}

?>
