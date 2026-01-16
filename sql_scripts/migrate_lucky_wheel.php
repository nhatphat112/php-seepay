<?php
/**
 * Migrate Lucky Wheel Database Script
 * 
 * Workflow:
 * 1. Create LuckyWheelConfig table (Cấu hình tính năng)
 * 2. Create LuckyWheelItems table (Vật phẩm trong vòng quay)
 * 3. Create LuckyWheelLog table (Log quay vòng)
 * 4. Create LuckyWheelRewards table (Vật phẩm đã trúng, chờ nhận)
 * 
 * Usage:
 * - CLI: php sql_scripts/migrate_lucky_wheel.php
 * - Web: http://your-domain/sql_scripts/migrate_lucky_wheel.php
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
            WHERE object_id = OBJECT_ID(?) AND name = ?
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
    output("Lucky Wheel Database Migration Script", 'info');
    output("==========================================", 'info');
    output("", 'info');
    
    $accountDb = ConnectionManager::getAccountDB();
    
    // Step 1: Create LuckyWheelConfig table
    output("Step 1: Creating LuckyWheelConfig table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelConfig]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[LuckyWheelConfig] (
                [Id] INT PRIMARY KEY IDENTITY(1,1),
                [FeatureEnabled] BIT DEFAULT 1,
                [SpinCost] INT DEFAULT 10,
                [UpdatedDate] DATETIME DEFAULT GETDATE(),
                [UpdatedBy] INT NULL
            )
        ");
        
        // Insert default config
        $accountDb->exec("
            INSERT INTO [dbo].[LuckyWheelConfig] (FeatureEnabled, SpinCost, UpdatedDate)
            VALUES (1, 10, GETDATE())
        ");
        
        output("  ✓ LuckyWheelConfig table created with default config", 'success');
    } else {
        output("  ✓ LuckyWheelConfig table already exists", 'success');
        
        // Check if config exists, if not insert default
        $checkConfig = $accountDb->query("SELECT COUNT(*) as cnt FROM [dbo].[LuckyWheelConfig]");
        $configCount = $checkConfig->fetch()['cnt'];
        
        if ($configCount == 0) {
            $accountDb->exec("
                INSERT INTO [dbo].[LuckyWheelConfig] (FeatureEnabled, SpinCost, UpdatedDate)
                VALUES (1, 10, GETDATE())
            ");
            output("  ✓ Inserted default config", 'success');
        }
    }
    
    // Step 2: Create LuckyWheelItems table
    output("", 'info');
    output("Step 2: Creating LuckyWheelItems table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelItems]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[LuckyWheelItems] (
                [Id] INT PRIMARY KEY IDENTITY(1,1),
                [ItemName] NVARCHAR(100) NOT NULL,
                [ItemCode] NVARCHAR(50) NOT NULL,
                [Quantity] INT NOT NULL DEFAULT 1,
                [IsRare] BIT DEFAULT 0,
                [WinRate] DECIMAL(5,2) NOT NULL,
                [DisplayOrder] INT DEFAULT 0,
                [IsActive] BIT DEFAULT 1,
                [CreatedDate] DATETIME DEFAULT GETDATE(),
                [UpdatedDate] DATETIME DEFAULT GETDATE(),
                [CreatedBy] INT NULL,
                [UpdatedBy] INT NULL
            )
        ");
        
        // Create indexes
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelItems_IsActive] ON [dbo].[LuckyWheelItems] ([IsActive])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelItems_DisplayOrder] ON [dbo].[LuckyWheelItems] ([DisplayOrder])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ LuckyWheelItems table created with indexes", 'success');
    } else {
        output("  ✓ LuckyWheelItems table already exists", 'success');
    }
    
    // Step 3: Create LuckyWheelLog table
    output("", 'info');
    output("Step 3: Creating LuckyWheelLog table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelLog]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[LuckyWheelLog] (
                [Id] INT PRIMARY KEY IDENTITY(1,1),
                [UserJID] INT NOT NULL,
                [ItemId] INT NOT NULL,
                [ItemName] NVARCHAR(100) NOT NULL,
                [ItemCode] NVARCHAR(50) NOT NULL,
                [Quantity] INT NOT NULL,
                [IsRare] BIT DEFAULT 0,
                [SpinDate] DATETIME DEFAULT GETDATE(),
                FOREIGN KEY ([UserJID]) REFERENCES [dbo].[TB_User]([JID]),
                FOREIGN KEY ([ItemId]) REFERENCES [dbo].[LuckyWheelItems]([Id])
            )
        ");
        
        // Create indexes
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelLog_UserJID] ON [dbo].[LuckyWheelLog] ([UserJID])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelLog_SpinDate] ON [dbo].[LuckyWheelLog] ([SpinDate])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelLog_IsRare] ON [dbo].[LuckyWheelLog] ([IsRare])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ LuckyWheelLog table created with indexes", 'success');
    } else {
        output("  ✓ LuckyWheelLog table already exists", 'success');
    }
    
    // Step 4: Create LuckyWheelRewards table
    output("", 'info');
    output("Step 4: Creating LuckyWheelRewards table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelRewards]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[LuckyWheelRewards] (
                [Id] INT PRIMARY KEY IDENTITY(1,1),
                [UserJID] INT NOT NULL,
                [LogId] INT NOT NULL,
                [ItemId] INT NOT NULL,
                [ItemName] NVARCHAR(100) NOT NULL,
                [ItemCode] NVARCHAR(50) NOT NULL,
                [Quantity] INT NOT NULL,
                [IsRare] BIT DEFAULT 0,
                [Status] VARCHAR(20) DEFAULT 'pending',
                [WonDate] DATETIME DEFAULT GETDATE(),
                [ClaimedDate] DATETIME NULL,
                FOREIGN KEY ([UserJID]) REFERENCES [dbo].[TB_User]([JID]),
                FOREIGN KEY ([LogId]) REFERENCES [dbo].[LuckyWheelLog]([Id]),
                FOREIGN KEY ([ItemId]) REFERENCES [dbo].[LuckyWheelItems]([Id])
            )
        ");
        
        // Create indexes
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelRewards_UserJID] ON [dbo].[LuckyWheelRewards] ([UserJID])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelRewards_Status] ON [dbo].[LuckyWheelRewards] ([Status])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelRewards_IsRare] ON [dbo].[LuckyWheelRewards] ([IsRare])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ LuckyWheelRewards table created with indexes", 'success');
    } else {
        output("  ✓ LuckyWheelRewards table already exists", 'success');
        
        // Add IsRare index if table exists but index doesn't (for existing installations)
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelRewards_IsRare] ON [dbo].[LuckyWheelRewards] ([IsRare])");
            output("  ✓ Added IsRare index to existing LuckyWheelRewards table", 'success');
        } catch (Exception $e) {
            // Index might already exist, ignore
        }
    }
    
    // Step 5: Add TotalSpins column to TB_User (if not exists)
    output("", 'info');
    output("Step 5: Adding TotalSpins column to TB_User...", 'info');
    
    if (!columnExists($accountDb, '[dbo].[TB_User]', 'TotalSpins')) {
        $accountDb->exec("
            ALTER TABLE [dbo].[TB_User]
            ADD [TotalSpins] INT DEFAULT 0
        ");
        output("  ✓ TotalSpins column added to TB_User", 'success');
    } else {
        output("  ✓ TotalSpins column already exists in TB_User", 'success');
    }
    
    // Step 6: Create LuckyWheelAccumulatedItems table
    output("", 'info');
    output("Step 6: Creating LuckyWheelAccumulatedItems table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelAccumulatedItems]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[LuckyWheelAccumulatedItems] (
                [Id] INT PRIMARY KEY IDENTITY(1,1),
                [ItemName] NVARCHAR(100) NOT NULL,
                [ItemCode] NVARCHAR(50) NOT NULL,
                [Quantity] INT NOT NULL DEFAULT 1,
                [RequiredSpins] INT NOT NULL,
                [DisplayOrder] INT DEFAULT 0,
                [IsActive] BIT DEFAULT 1,
                [CreatedDate] DATETIME DEFAULT GETDATE(),
                [UpdatedDate] DATETIME DEFAULT GETDATE(),
                [CreatedBy] INT NULL,
                [UpdatedBy] INT NULL
            )
        ");
        
        // Create indexes
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelAccumulatedItems_IsActive] ON [dbo].[LuckyWheelAccumulatedItems] ([IsActive])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelAccumulatedItems_RequiredSpins] ON [dbo].[LuckyWheelAccumulatedItems] ([RequiredSpins])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ LuckyWheelAccumulatedItems table created with indexes", 'success');
    } else {
        output("  ✓ LuckyWheelAccumulatedItems table already exists", 'success');
    }
    
    // Step 7: Create LuckyWheelAccumulatedLog table
    output("", 'info');
    output("Step 7: Creating LuckyWheelAccumulatedLog table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LuckyWheelAccumulatedLog]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[LuckyWheelAccumulatedLog] (
                [Id] INT PRIMARY KEY IDENTITY(1,1),
                [UserJID] INT NOT NULL,
                [AccumulatedItemId] INT NOT NULL,
                [ItemName] NVARCHAR(100) NOT NULL,
                [ItemCode] NVARCHAR(50) NOT NULL,
                [Quantity] INT NOT NULL,
                [RequiredSpins] INT NOT NULL,
                [TotalSpinsAtClaim] INT NOT NULL,
                [CharName] NVARCHAR(50) NOT NULL,
                [ClaimedDate] DATETIME DEFAULT GETDATE(),
                FOREIGN KEY ([UserJID]) REFERENCES [dbo].[TB_User]([JID]),
                FOREIGN KEY ([AccumulatedItemId]) REFERENCES [dbo].[LuckyWheelAccumulatedItems]([Id])
            )
        ");
        
        // Create indexes
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelAccumulatedLog_UserJID] ON [dbo].[LuckyWheelAccumulatedLog] ([UserJID])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LuckyWheelAccumulatedLog_AccumulatedItemId] ON [dbo].[LuckyWheelAccumulatedLog] ([AccumulatedItemId])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        // Unique constraint: User can only claim each accumulated item once
        try {
            $accountDb->exec("CREATE UNIQUE NONCLUSTERED INDEX [IX_LuckyWheelAccumulatedLog_User_Item] ON [dbo].[LuckyWheelAccumulatedLog] ([UserJID], [AccumulatedItemId])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ LuckyWheelAccumulatedLog table created with indexes", 'success');
    } else {
        output("  ✓ LuckyWheelAccumulatedLog table already exists", 'success');
    }
    
    // Summary
    output("", 'info');
    output("==========================================", 'success');
    output("Migration completed successfully!", 'success');
    output("==========================================", 'success');
    output("", 'info');
    output("Tables created/verified:", 'info');
    output("  ✓ LuckyWheelConfig (Cấu hình tính năng)", 'success');
    output("  ✓ LuckyWheelItems (Vật phẩm trong vòng quay)", 'success');
    output("  ✓ LuckyWheelLog (Log quay vòng)", 'success');
    output("  ✓ LuckyWheelRewards (Vật phẩm đã trúng, chờ nhận)", 'success');
    output("  ✓ TB_User.TotalSpins (Tổng số vòng quay của user)", 'success');
    output("  ✓ LuckyWheelAccumulatedItems (Vật phẩm tích lũy)", 'success');
    output("  ✓ LuckyWheelAccumulatedLog (Log nhận phần thưởng tích lũy)", 'success');
    output("", 'info');
    output("✓ No data was deleted", 'success');
    output("✓ Safe migration completed!", 'success');
    
} catch (Exception $e) {
    output("", 'error');
    output("==========================================", 'error');
    output("Migration failed!", 'error');
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
