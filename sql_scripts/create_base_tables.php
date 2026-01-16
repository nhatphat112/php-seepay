<?php
/**
 * Create Base Tables Script
 * Tạo các bảng cơ bản cần thiết cho hệ thống
 * 
 * Workflow:
 * 1. Create TB_User table (Account database)
 * 2. Create SK_Silk table (Account database)
 * 3. Create TB_Order table (Account database)
 * 4. Create _AccountJID table (Shard database)
 * 
 * Usage:
 * - CLI: php sql_scripts/create_base_tables.php
 * - Web: http://your-domain/sql_scripts/create_base_tables.php
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
    output("Create Base Tables Script", 'info');
    output("==========================================", 'info');
    output("", 'info');
    
    $accountDb = ConnectionManager::getAccountDB();
    $shardDb = ConnectionManager::getShardDB();
    
    // Step 1: Create TB_User table
    output("Step 1: Creating TB_User table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[TB_User]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[TB_User] (
                [JID] INT IDENTITY(1,1) PRIMARY KEY,
                [StrUserID] VARCHAR(128) NOT NULL,
                [password] VARCHAR(50) NOT NULL,
                [Email] VARCHAR(50) NULL,
                [Name] VARCHAR(20) NULL,
                [Status] TINYINT DEFAULT 0,
                [sec_primary] TINYINT DEFAULT 0,
                [sec_content] TINYINT DEFAULT 0,
                [regtime] DATETIME DEFAULT GETDATE(),
                [AccumulatedDeposit] BIGINT DEFAULT 0,
                [AccPlayTime] INT DEFAULT 0,
                [LatestUpdateTime_ToPlayTime] INT DEFAULT 0,
                [Play123Time] INT DEFAULT 0,
                [role] VARCHAR(20) NULL DEFAULT 'user'
            )
        ");
        
        // Create indexes
        try {
            $accountDb->exec("CREATE UNIQUE NONCLUSTERED INDEX [IX_TB_User_StrUserID] ON [dbo].[TB_User] ([StrUserID])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_TB_User_Email] ON [dbo].[TB_User] ([Email]) WHERE [Email] IS NOT NULL");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ TB_User table created with indexes", 'success');
    } else {
        output("  ✓ TB_User table already exists", 'success');
        
        // Check and add role column if not exists
        try {
            $checkRole = $accountDb->query("
                SELECT COUNT(*) as cnt 
                FROM sys.columns 
                WHERE object_id = OBJECT_ID('[dbo].[TB_User]') 
                AND name = 'role'
            ");
            if ($checkRole->fetch()['cnt'] == 0) {
                $accountDb->exec("ALTER TABLE [dbo].[TB_User] ADD [role] VARCHAR(20) NULL DEFAULT 'user'");
                output("  ✓ Added 'role' column to TB_User", 'success');
            }
        } catch (Exception $e) {
            // Column might already exist
        }
        
        // Check and add AccumulatedDeposit column if not exists
        try {
            $checkAccDep = $accountDb->query("
                SELECT COUNT(*) as cnt 
                FROM sys.columns 
                WHERE object_id = OBJECT_ID('[dbo].[TB_User]') 
                AND name = 'AccumulatedDeposit'
            ");
            if ($checkAccDep->fetch()['cnt'] == 0) {
                $accountDb->exec("ALTER TABLE [dbo].[TB_User] ADD [AccumulatedDeposit] BIGINT DEFAULT 0");
                output("  ✓ Added 'AccumulatedDeposit' column to TB_User", 'success');
            }
        } catch (Exception $e) {
            // Column might already exist
        }
    }
    
    // Step 2: Create SK_Silk table
    output("", 'info');
    output("Step 2: Creating SK_Silk table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[SK_Silk]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[SK_Silk] (
                [JID] INT NOT NULL PRIMARY KEY,
                [silk_own] INT DEFAULT 0,
                [silk_gift] INT DEFAULT 0,
                [silk_point] INT DEFAULT 0,
                FOREIGN KEY ([JID]) REFERENCES [dbo].[TB_User]([JID])
            )
        ");
        output("  ✓ SK_Silk table created", 'success');
    } else {
        output("  ✓ SK_Silk table already exists", 'success');
    }
    
    // Step 3: Create TB_Order table
    output("", 'info');
    output("Step 3: Creating TB_Order table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[TB_Order]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[TB_Order] (
                [Id] INT IDENTITY(1,1) PRIMARY KEY,
                [JID] INT NOT NULL,
                [OrderCode] VARCHAR(50) NOT NULL,
                [Amount] INT NOT NULL,
                [SilkAmount] INT NOT NULL,
                [Status] VARCHAR(20) DEFAULT 'pending',
                [PaymentMethod] VARCHAR(50) NULL,
                [QRCode] NVARCHAR(MAX) NULL,
                [BankAccount] VARCHAR(50) NULL,
                [BankName] VARCHAR(100) NULL,
                [AccountName] VARCHAR(100) NULL,
                [Content] VARCHAR(200) NULL,
                [CreatedDate] DATETIME DEFAULT GETDATE(),
                [CompletedDate] DATETIME NULL,
                FOREIGN KEY ([JID]) REFERENCES [dbo].[TB_User]([JID])
            )
        ");
        
        // Create indexes
        try {
            $accountDb->exec("CREATE UNIQUE NONCLUSTERED INDEX [IX_TB_Order_OrderCode] ON [dbo].[TB_Order] ([OrderCode])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_TB_Order_JID] ON [dbo].[TB_Order] ([JID])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_TB_Order_Status] ON [dbo].[TB_Order] ([Status])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ TB_Order table created with indexes", 'success');
    } else {
        output("  ✓ TB_Order table already exists", 'success');
    }
    
    // Step 4: Create _AccountJID table (in Shard database)
    output("", 'info');
    output("Step 4: Creating _AccountJID table (Shard DB)...", 'info');
    
    if (!tableExists($shardDb, '[dbo].[_AccountJID]')) {
        $shardDb->exec("
            CREATE TABLE [dbo].[_AccountJID] (
                [AccountID] VARCHAR(128) NOT NULL,
                [JID] INT NOT NULL,
                [gold] BIGINT DEFAULT 0,
                PRIMARY KEY ([AccountID], [JID])
            )
        ");
        
        // Create index
        try {
            $shardDb->exec("CREATE NONCLUSTERED INDEX [IX_AccountJID_JID] ON [dbo].[_AccountJID] ([JID])");
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ _AccountJID table created with index", 'success');
    } else {
        output("  ✓ _AccountJID table already exists", 'success');
    }
    
    // Step 5: Create _LogEventUser table (in Log database) - for logging
    output("", 'info');
    output("Step 5: Creating _LogEventUser table (Log DB)...", 'info');
    
    try {
        $logDb = ConnectionManager::getLogDB();
        
        if (!tableExists($logDb, '[dbo].[_LogEventUser]')) {
            $logDb->exec("
                CREATE TABLE [dbo].[_LogEventUser] (
                    [Id] INT IDENTITY(1,1) PRIMARY KEY,
                    [UserJID] INT NOT NULL,
                    [EventID] INT NOT NULL,
                    [EventData] NVARCHAR(MAX) NULL,
                    [RegDate] DATETIME DEFAULT GETDATE(),
                    [regtime] DATETIME DEFAULT GETDATE()
                )
            ");
            
            // Create index
            try {
                $logDb->exec("CREATE NONCLUSTERED INDEX [IX_LogEventUser_UserJID] ON [dbo].[_LogEventUser] ([UserJID])");
            } catch (Exception $e) {
                // Index might already exist
            }
            
            output("  ✓ _LogEventUser table created with index", 'success');
        } else {
            output("  ✓ _LogEventUser table already exists", 'success');
        }
    } catch (Exception $e) {
        output("  ⚠ Warning: Could not create _LogEventUser: " . $e->getMessage(), 'warning');
    }
    
    // Summary
    output("", 'info');
    output("==========================================", 'success');
    output("Base tables created successfully!", 'success');
    output("==========================================", 'success');
    output("", 'info');
    output("Tables created/verified:", 'info');
    output("  ✓ TB_User (Account DB) - User accounts", 'success');
    output("  ✓ SK_Silk (Account DB) - Silk balance", 'success');
    output("  ✓ TB_Order (Account DB) - Payment orders", 'success');
    output("  ✓ _AccountJID (Shard DB) - Account mapping", 'success');
    output("  ✓ _LogEventUser (Log DB) - Event logging", 'success');
    output("", 'info');
    output("You can now run other migration scripts:", 'info');
    output("  - php sql_scripts/migrate_schema.php", 'info');
    output("  - php sql_scripts/migrate_admin_account.php", 'info');
    output("  - php sql_scripts/migrate_tichnap.php", 'info');
    
} catch (Exception $e) {
    output("", 'error');
    output("==========================================", 'error');
    output("Failed to create base tables!", 'error');
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
