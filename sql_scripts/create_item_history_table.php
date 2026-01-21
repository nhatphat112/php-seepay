<?php
/**
 * Create Item History Table Script
 * Tạo bảng lưu lịch sử nhận vật phẩm từ vòng quay may mắn
 * 
 * Workflow:
 * 1. Create LuckyWheelItemHistory table
 * 2. Create indexes for performance
 * 
 * Usage:
 * - CLI: php sql_scripts/create_item_history_table.php
 * - Web: http://your-domain/sql_scripts/create_item_history_table.php
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if running from CLI or web
$isCLI = php_sapi_name() === 'cli';

// Load connection manager
require_once __DIR__ . '/../connection_manager.php';

// Output helper
function output($message, $type = 'info') {
    global $isCLI;
    
    if ($isCLI) {
        $colors = [
            'success' => "\033[32m", // Green
            'error' => "\033[31m",   // Red
            'warning' => "\033[33m", // Yellow
            'info' => "\033[36m",    // Cyan
            'reset' => "\033[0m"      // Reset
        ];
        echo $colors[$type] . $message . $colors['reset'] . PHP_EOL;
    } else {
        $styles = [
            'success' => 'color: #28a745; font-weight: bold;',
            'error' => 'color: #dc3545; font-weight: bold;',
            'warning' => 'color: #ffc107; font-weight: bold;',
            'info' => 'color: #17a2b8;'
        ];
        echo '<div style="' . ($styles[$type] ?? '') . '">' . htmlspecialchars($message) . '</div>';
    }
}

// Check if table exists
function tableExists($db, $tableName) {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as cnt 
            FROM sys.objects 
            WHERE object_id = OBJECT_ID(?) AND type in (N'U')
        ");
        $stmt->execute([$tableName]);
        return $stmt->fetch()['cnt'] > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Start migration
try {
    output("==========================================", 'info');
    output("Create Item History Table Script", 'info');
    output("==========================================", 'info');
    output("", 'info');
    
    $accountDb = ConnectionManager::getAccountDB();
    
    // Step 1: Create LuckyWheelItemHistory table
    output("Step 1: Creating LuckyWheelItemHistory table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelItemHistory]')) {
        // Check if foreign key tables exist first
        $rewardTableExists = tableExists($accountDb, '[dbo].[LuckyWheelRewards]');
        $accumulatedLogTableExists = tableExists($accountDb, '[dbo].[LuckyWheelAccumulatedLog]');
        
        // Create table with optional foreign keys
        $createTableSql = "
            CREATE TABLE LuckyWheelItemHistory (
                Id INT PRIMARY KEY IDENTITY(1,1),
                UserJID INT NOT NULL,
                Username NVARCHAR(50) NOT NULL,
                ItemName NVARCHAR(200) NOT NULL,
                ItemCode NVARCHAR(100) NOT NULL,
                Quantity INT NOT NULL,
                Source NVARCHAR(50) NOT NULL,
                CharName NVARCHAR(64) NULL,
                RewardId INT NULL,
                AccumulatedLogId INT NULL,
                ReceivedDate DATETIME DEFAULT GETDATE()
            )
        ";
        
        $accountDb->exec($createTableSql);
        
        // Add foreign keys if tables exist
        if ($rewardTableExists) {
            try {
                $accountDb->exec("
                    ALTER TABLE LuckyWheelItemHistory
                    ADD CONSTRAINT FK_ItemHistory_Reward 
                    FOREIGN KEY (RewardId) REFERENCES LuckyWheelRewards(Id) ON DELETE SET NULL
                ");
                output("  ✓ Added foreign key FK_ItemHistory_Reward", 'success');
            } catch (Exception $e) {
                output("  ⚠ Warning: Could not add FK_ItemHistory_Reward: " . $e->getMessage(), 'warning');
            }
        } else {
            output("  ⚠ Warning: LuckyWheelRewards table not found, skipping FK_ItemHistory_Reward", 'warning');
        }
        
        if ($accumulatedLogTableExists) {
            try {
                $accountDb->exec("
                    ALTER TABLE LuckyWheelItemHistory
                    ADD CONSTRAINT FK_ItemHistory_AccumulatedLog 
                    FOREIGN KEY (AccumulatedLogId) REFERENCES LuckyWheelAccumulatedLog(Id) ON DELETE SET NULL
                ");
                output("  ✓ Added foreign key FK_ItemHistory_AccumulatedLog", 'success');
            } catch (Exception $e) {
                output("  ⚠ Warning: Could not add FK_ItemHistory_AccumulatedLog: " . $e->getMessage(), 'warning');
            }
        } else {
            output("  ⚠ Warning: LuckyWheelAccumulatedLog table not found, skipping FK_ItemHistory_AccumulatedLog", 'warning');
        }
        
        // Create indexes
        try {
            $accountDb->exec("CREATE INDEX IX_ItemHistory_UserJID ON LuckyWheelItemHistory(UserJID)");
            output("  ✓ Created index IX_ItemHistory_UserJID", 'success');
        } catch (Exception $e) {
            output("  ⚠ Warning: Could not create index IX_ItemHistory_UserJID: " . $e->getMessage(), 'warning');
        }
        
        try {
            $accountDb->exec("CREATE INDEX IX_ItemHistory_Username ON LuckyWheelItemHistory(Username)");
            output("  ✓ Created index IX_ItemHistory_Username", 'success');
        } catch (Exception $e) {
            output("  ⚠ Warning: Could not create index IX_ItemHistory_Username: " . $e->getMessage(), 'warning');
        }
        
        try {
            $accountDb->exec("CREATE INDEX IX_ItemHistory_Source ON LuckyWheelItemHistory(Source)");
            output("  ✓ Created index IX_ItemHistory_Source", 'success');
        } catch (Exception $e) {
            output("  ⚠ Warning: Could not create index IX_ItemHistory_Source: " . $e->getMessage(), 'warning');
        }
        
        try {
            $accountDb->exec("CREATE INDEX IX_ItemHistory_ReceivedDate ON LuckyWheelItemHistory(ReceivedDate DESC)");
            output("  ✓ Created index IX_ItemHistory_ReceivedDate", 'success');
        } catch (Exception $e) {
            output("  ⚠ Warning: Could not create index IX_ItemHistory_ReceivedDate: " . $e->getMessage(), 'warning');
        }
        
        try {
            $accountDb->exec("CREATE INDEX IX_ItemHistory_RewardId ON LuckyWheelItemHistory(RewardId)");
            output("  ✓ Created index IX_ItemHistory_RewardId", 'success');
        } catch (Exception $e) {
            output("  ⚠ Warning: Could not create index IX_ItemHistory_RewardId: " . $e->getMessage(), 'warning');
        }
        
        try {
            $accountDb->exec("CREATE INDEX IX_ItemHistory_AccumulatedLogId ON LuckyWheelItemHistory(AccumulatedLogId)");
            output("  ✓ Created index IX_ItemHistory_AccumulatedLogId", 'success');
        } catch (Exception $e) {
            output("  ⚠ Warning: Could not create index IX_ItemHistory_AccumulatedLogId: " . $e->getMessage(), 'warning');
        }
        
        output("  ✓ LuckyWheelItemHistory table created successfully", 'success');
    } else {
        output("  ✓ LuckyWheelItemHistory table already exists", 'success');
    }
    
    // Summary
    output("", 'info');
    output("==========================================", 'success');
    output("Item History table created successfully!", 'success');
    output("==========================================", 'success');
    output("", 'info');
    output("Table created/verified:", 'info');
    output("  ✓ LuckyWheelItemHistory (Account DB) - Item receipt history", 'success');
    output("", 'info');
    output("Table structure:", 'info');
    output("  - UserJID: User ID who received the item", 'info');
    output("  - Username: Username for easier querying", 'info');
    output("  - ItemName: Name of the item received", 'info');
    output("  - ItemCode: Code of the item", 'info');
    output("  - Quantity: Amount of items received", 'info');
    output("  - Source: 'lucky_wheel' or 'accumulated_reward'", 'info');
    output("  - CharName: Character name who received the item", 'info');
    output("  - RewardId: Reference to LuckyWheelRewards (if from lucky wheel)", 'info');
    output("  - AccumulatedLogId: Reference to LuckyWheelAccumulatedLog (if from accumulated)", 'info');
    output("  - ReceivedDate: When the item was received", 'info');
    output("", 'info');
    output("Next steps:", 'info');
    output("  1. Test the log function using test script: php tests/test_item_history_log.php", 'info');
    output("  2. Items will be automatically logged when users claim rewards", 'info');
    output("  3. View history in User Panel > Lịch Sử Nhận Vật Phẩm", 'info');
    
} catch (Exception $e) {
    output("", 'error');
    output("==========================================", 'error');
    output("Failed to create item history table!", 'error');
    output("==========================================", 'error');
    output("", 'error');
    output("Error: " . $e->getMessage(), 'error');
    output("", 'error');
    output("Stack trace:", 'error');
    output($e->getTraceAsString(), 'error');
    exit(1);
}

// For web access, add some styling
if (!$isCLI) {
    echo '<style>
        body {
            font-family: monospace;
            background: #1a1f3a;
            color: #fff;
            padding: 20px;
            line-height: 1.6;
        }
        div {
            margin: 5px 0;
        }
    </style>';
}
?>
