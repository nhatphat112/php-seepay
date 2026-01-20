<?php
/**
 * Create Leaderboard Tables Script
 * Tạo các bảng cho hệ thống Bảng Xếp Hạng Vòng Quay
 * 
 * Workflow:
 * 1. Create LuckyWheelSeasons table
 * 2. Create LuckyWheelSeasonLog table
 * 3. Add SeasonId column to LuckyWheelLog table
 * 
 * Usage:
 * - CLI: php sql_scripts/create_leaderboard_tables.php
 * - Web: http://your-domain/sql_scripts/create_leaderboard_tables.php
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

// Check if column exists
function columnExists($db, $tableName, $columnName) {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as cnt 
            FROM sys.columns 
            WHERE object_id = OBJECT_ID(?) 
            AND name = ?
        ");
        $stmt->execute([$tableName, $columnName]);
        return $stmt->fetch()['cnt'] > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Start migration
try {
    output("==========================================", 'info');
    output("Create Leaderboard Tables Script", 'info');
    output("==========================================", 'info');
    output("", 'info');
    
    $accountDb = ConnectionManager::getAccountDB();
    
    // Step 1: Create LuckyWheelSeasons table
    output("Step 1: Creating LuckyWheelSeasons table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelSeasons]')) {
        $accountDb->exec("
            CREATE TABLE LuckyWheelSeasons (
                Id INT PRIMARY KEY IDENTITY(1,1),
                SeasonName NVARCHAR(100) NOT NULL,
                SeasonType NVARCHAR(20) NOT NULL,
                StartDate DATETIME NOT NULL,
                EndDate DATETIME NULL,
                IsActive BIT DEFAULT 0,
                Status NVARCHAR(20) DEFAULT 'PENDING',
                CreatedDate DATETIME DEFAULT GETDATE(),
                UpdatedDate DATETIME DEFAULT GETDATE(),
                CONSTRAINT UQ_SeasonName UNIQUE(SeasonName)
            )
        ");
        
        // Create indexes
        try {
            $accountDb->exec("CREATE INDEX IX_Seasons_IsActive ON LuckyWheelSeasons(IsActive)");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE INDEX IX_Seasons_StartDate ON LuckyWheelSeasons(StartDate)");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE INDEX IX_Seasons_Status ON LuckyWheelSeasons(Status)");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ LuckyWheelSeasons table created with indexes", 'success');
    } else {
        output("  ✓ LuckyWheelSeasons table already exists", 'success');
        
        // Check and add Status column if not exists
        if (!columnExists($accountDb, '[dbo].[LuckyWheelSeasons]', 'Status')) {
            try {
                $accountDb->exec("ALTER TABLE LuckyWheelSeasons ADD Status NVARCHAR(20) DEFAULT 'PENDING'");
                output("  ✓ Added 'Status' column to LuckyWheelSeasons", 'success');
            } catch (Exception $e) {
                output("  ⚠ Warning: Could not add Status column: " . $e->getMessage(), 'warning');
            }
        }
    }
    
    // Step 2: Create LuckyWheelSeasonLog table
    output("", 'info');
    output("Step 2: Creating LuckyWheelSeasonLog table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelSeasonLog]')) {
        $accountDb->exec("
            CREATE TABLE LuckyWheelSeasonLog (
                Id INT PRIMARY KEY IDENTITY(1,1),
                SeasonId INT NOT NULL,
                UserJID INT NOT NULL,
                Username NVARCHAR(50) NOT NULL,
                TotalSpins INT DEFAULT 0,
                LastSpinDate DATETIME NULL,
                Rank INT NULL,
                CreatedDate DATETIME DEFAULT GETDATE(),
                UpdatedDate DATETIME DEFAULT GETDATE(),
                CONSTRAINT FK_SeasonLog_Season FOREIGN KEY (SeasonId) REFERENCES LuckyWheelSeasons(Id) ON DELETE CASCADE,
                CONSTRAINT UQ_SeasonLog_User UNIQUE(SeasonId, UserJID)
            )
        ");
        
        // Create indexes
        try {
            $accountDb->exec("CREATE INDEX IX_SeasonLog_SeasonId_TotalSpins ON LuckyWheelSeasonLog(SeasonId, TotalSpins DESC)");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE INDEX IX_SeasonLog_UserJID ON LuckyWheelSeasonLog(UserJID)");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ LuckyWheelSeasonLog table created with indexes", 'success');
    } else {
        output("  ✓ LuckyWheelSeasonLog table already exists", 'success');
    }
    
    // Step 3: Add SeasonId column to LuckyWheelLog
    output("", 'info');
    output("Step 3: Adding SeasonId column to LuckyWheelLog...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelLog]')) {
        output("  ⚠ Warning: LuckyWheelLog table does not exist, skipping SeasonId column", 'warning');
    } else {
        if (!columnExists($accountDb, '[dbo].[LuckyWheelLog]', 'SeasonId')) {
            try {
                $accountDb->exec("ALTER TABLE LuckyWheelLog ADD SeasonId INT NULL");
                
                // Add foreign key constraint
                try {
                    $accountDb->exec("
                        ALTER TABLE LuckyWheelLog
                        ADD CONSTRAINT FK_LuckyWheelLog_Season 
                        FOREIGN KEY (SeasonId) REFERENCES LuckyWheelSeasons(Id)
                    ");
                } catch (Exception $e) {
                    output("  ⚠ Warning: Could not add foreign key constraint: " . $e->getMessage(), 'warning');
                }
                
                // Create index
                try {
                    $accountDb->exec("CREATE INDEX IX_LuckyWheelLog_SeasonId ON LuckyWheelLog(SeasonId)");
                } catch (Exception $e) {
                    // Index might already exist
                }
                
                output("  ✓ Added 'SeasonId' column to LuckyWheelLog", 'success');
            } catch (Exception $e) {
                output("  ⚠ Warning: Could not add SeasonId column: " . $e->getMessage(), 'warning');
            }
        } else {
            output("  ✓ SeasonId column already exists in LuckyWheelLog", 'success');
        }
    }
    
    // Summary
    output("", 'info');
    output("==========================================", 'success');
    output("Leaderboard tables created successfully!", 'success');
    output("==========================================", 'success');
    output("", 'info');
    output("Tables created/verified:", 'info');
    output("  ✓ LuckyWheelSeasons (Account DB) - Season information", 'success');
    output("  ✓ LuckyWheelSeasonLog (Account DB) - Leaderboard data", 'success');
    output("  ✓ LuckyWheelLog.SeasonId (Account DB) - Season reference", 'success');
    output("", 'info');
    output("Next steps:", 'info');
    output("  1. Create your first season in Admin Panel > Lucky Wheel > Quản Lý Mùa", 'info');
    output("  2. The system will automatically transition seasons when users login or spin", 'info');
    output("  3. Leaderboard will be displayed on lucky wheel page and home page", 'info');
    
} catch (Exception $e) {
    output("", 'error');
    output("==========================================", 'error');
    output("Failed to create leaderboard tables!", 'error');
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
