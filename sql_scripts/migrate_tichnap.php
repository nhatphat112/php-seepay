<?php
/**
 * Migrate TichNap Database Script
 * 
 * Workflow:
 * 1. Create SilkTichNap table (Mốc nạp tích lũy)
 * 2. Create LogTichNap table (Log nhận phần thưởng)
 * 3. Create TotalMoneyUser table (Backup - Optional)
 * 4. Create GiftCodeItem table (Vật phẩm phần thưởng)
 * 5. Create TaiLieuDinhKem table (Tài liệu đính kèm)
 * 6. Create TichNapConfig table (Cấu hình tính năng)
 * 7. Add AccumulatedDeposit column to TB_User table
 * 
 * NGUYÊN TẮC AN TOÀN:
 * - CHỈ BỔ SUNG: Tạo bảng mới, thêm cột mới, tạo index mới
 * - KHÔNG XÓA: Không DROP TABLE, DROP COLUMN, DELETE, TRUNCATE
 * - KHÔNG SỬA: Không ALTER COLUMN, không thay đổi kiểu dữ liệu
 * - IDEMPOTENT: Có thể chạy nhiều lần mà không gây lỗi
 * - TƯƠNG THÍCH: Không làm lỗi các câu query cũ
 * 
 * Usage:
 * - CLI: php sql_scripts/migrate_tichnap.php
 * - Web: http://your-domain/sql_scripts/migrate_tichnap.php
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

// Check if index exists
function indexExists($db, $tableName, $indexName) {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as cnt 
            FROM sys.indexes 
            WHERE name = ? AND object_id = OBJECT_ID(?)
        ");
        $stmt->execute([$indexName, $tableName]);
        return $stmt->fetch()['cnt'] > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Start migration
try {
    output("==========================================", 'info');
    output("TichNap Database Migration Script", 'info');
    output("==========================================", 'info');
    output("", 'info');
    
    $accountDb = ConnectionManager::getAccountDB();
    
    // Step 1: Create SilkTichNap table
    output("Step 1: Creating SilkTichNap table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[SilkTichNap]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[SilkTichNap] (
                [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
                [Rank] INT NOT NULL,
                [DsItem] NVARCHAR(MAX),
                [ItemsJson] NVARCHAR(MAX) NULL,
                [Description] NVARCHAR(MAX),
                [IsActive] BIT DEFAULT 0,
                [CreatedDate] DATETIME DEFAULT GETDATE(),
                [CreatedId] UNIQUEIDENTIFIER,
                [UpdatedDate] DATETIME,
                [UpdatedId] UNIQUEIDENTIFIER,
                [IsDelete] BIT DEFAULT 0
            )
        ");
        output("  ✓ SilkTichNap table created", 'success');
    } else {
        output("  ✓ SilkTichNap table already exists", 'success');
        
        // Add IsActive column if not exists
        if (!columnExists($accountDb, '[dbo].[SilkTichNap]', 'IsActive')) {
            $accountDb->exec("ALTER TABLE [dbo].[SilkTichNap] ADD [IsActive] BIT DEFAULT 0");
            output("  ✓ Added IsActive column to SilkTichNap", 'success');
        }
        
        // Add ItemsJson column if not exists
        if (!columnExists($accountDb, '[dbo].[SilkTichNap]', 'ItemsJson')) {
            $accountDb->exec("ALTER TABLE [dbo].[SilkTichNap] ADD [ItemsJson] NVARCHAR(MAX) NULL");
            output("  ✓ Added ItemsJson column to SilkTichNap", 'success');
        }
    }
    
    // Step 2: Create LogTichNap table
    output("", 'info');
    output("Step 2: Creating LogTichNap table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[LogTichNap]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[LogTichNap] (
                [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
                [CharName] NVARCHAR(50) NOT NULL,
                [IdTichNap] UNIQUEIDENTIFIER NOT NULL,
                [Status] BIT DEFAULT 1,
                [MaxPrice] BIGINT,
                [CreatedDate] DATETIME DEFAULT GETDATE(),
                [CreatedId] UNIQUEIDENTIFIER,
                [IsDelete] BIT DEFAULT 0
            )
        ");
        
        // Create indexes
        try {
            if (!indexExists($accountDb, '[dbo].[LogTichNap]', 'IX_LogTichNap_CharName')) {
                $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LogTichNap_CharName] ON [dbo].[LogTichNap] ([CharName])");
            }
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            if (!indexExists($accountDb, '[dbo].[LogTichNap]', 'IX_LogTichNap_IdTichNap')) {
                $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_LogTichNap_IdTichNap] ON [dbo].[LogTichNap] ([IdTichNap])");
            }
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ LogTichNap table created with indexes", 'success');
    } else {
        output("  ✓ LogTichNap table already exists", 'success');
    }
    
    // Step 3: Create TotalMoneyUser table
    output("", 'info');
    output("Step 3: Creating TotalMoneyUser table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[TotalMoneyUser]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[TotalMoneyUser] (
                [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
                [UserJID] INT NOT NULL,
                [TotalMoney] BIGINT NOT NULL,
                [CreateDate] DATETIME NOT NULL DEFAULT GETDATE(),
                [CreatedDate] DATETIME DEFAULT GETDATE(),
                [CreatedId] UNIQUEIDENTIFIER,
                [IsDelete] BIT DEFAULT 0
            )
        ");
        
        // Create index
        try {
            if (!indexExists($accountDb, '[dbo].[TotalMoneyUser]', 'IX_TotalMoneyUser_UserJID')) {
                $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_TotalMoneyUser_UserJID] ON [dbo].[TotalMoneyUser] ([UserJID])");
            }
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ TotalMoneyUser table created with index", 'success');
    } else {
        output("  ✓ TotalMoneyUser table already exists", 'success');
    }
    
    // Step 4: Create GiftCodeItem table
    output("", 'info');
    output("Step 4: Creating GiftCodeItem table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[GiftCodeItem]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[GiftCodeItem] (
                [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
                [CodeItem] NVARCHAR(50) NOT NULL,
                [NameItem] NVARCHAR(200),
                [quanlity] INT DEFAULT 1,
                [CreatedDate] DATETIME DEFAULT GETDATE(),
                [IsDelete] BIT DEFAULT 0
            )
        ");
        
        // Create index
        try {
            if (!indexExists($accountDb, '[dbo].[GiftCodeItem]', 'IX_GiftCodeItem_CodeItem')) {
                $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_GiftCodeItem_CodeItem] ON [dbo].[GiftCodeItem] ([CodeItem])");
            }
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ GiftCodeItem table created with index", 'success');
    } else {
        output("  ✓ GiftCodeItem table already exists", 'success');
    }
    
    // Step 5: Create TaiLieuDinhKem table
    output("", 'info');
    output("Step 5: Creating TaiLieuDinhKem table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[TaiLieuDinhKem]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[TaiLieuDinhKem] (
                [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
                [Item_ID] UNIQUEIDENTIFIER,
                [LoaiTaiLieu] NVARCHAR(50),
                [DuongDanFile] NVARCHAR(500),
                [NgayPhatHanh] DATETIME,
                [CreatedDate] DATETIME DEFAULT GETDATE()
            )
        ");
        
        // Create indexes
        try {
            if (!indexExists($accountDb, '[dbo].[TaiLieuDinhKem]', 'IX_TaiLieuDinhKem_Item_ID')) {
                $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_TaiLieuDinhKem_Item_ID] ON [dbo].[TaiLieuDinhKem] ([Item_ID])");
            }
        } catch (Exception $e) {
            // Index might already exist
        }
        
        try {
            if (!indexExists($accountDb, '[dbo].[TaiLieuDinhKem]', 'IX_TaiLieuDinhKem_LoaiTaiLieu')) {
                $accountDb->exec("CREATE NONCLUSTERED INDEX [IX_TaiLieuDinhKem_LoaiTaiLieu] ON [dbo].[TaiLieuDinhKem] ([LoaiTaiLieu])");
            }
        } catch (Exception $e) {
            // Index might already exist
        }
        
        output("  ✓ TaiLieuDinhKem table created with indexes", 'success');
    } else {
        output("  ✓ TaiLieuDinhKem table already exists", 'success');
    }
    
    // Step 6: Create TichNapConfig table
    output("", 'info');
    output("Step 6: Creating TichNapConfig table...", 'info');
    
    if (!tableExists($accountDb, '[dbo].[TichNapConfig]')) {
        $accountDb->exec("
            CREATE TABLE [dbo].[TichNapConfig] (
                [Id] INT PRIMARY KEY IDENTITY(1,1),
                [FeatureEnabled] BIT DEFAULT 1,
                [EventStartDate] DATETIME NULL,
                [EventEndDate] DATETIME NULL,
                [UpdatedDate] DATETIME DEFAULT GETDATE(),
                [UpdatedBy] INT
            )
        ");
        
        // Insert default config
        $accountDb->exec("
            INSERT INTO [dbo].[TichNapConfig] (FeatureEnabled, UpdatedDate)
            VALUES (1, GETDATE())
        ");
        
        output("  ✓ TichNapConfig table created with default config", 'success');
    } else {
        output("  ✓ TichNapConfig table already exists", 'success');
        
        // Add EventStartDate column if not exists
        if (!columnExists($accountDb, '[dbo].[TichNapConfig]', 'EventStartDate')) {
            $accountDb->exec("ALTER TABLE [dbo].[TichNapConfig] ADD [EventStartDate] DATETIME NULL");
            output("  ✓ Added EventStartDate column to TichNapConfig", 'success');
        }
        
        // Add EventEndDate column if not exists
        if (!columnExists($accountDb, '[dbo].[TichNapConfig]', 'EventEndDate')) {
            $accountDb->exec("ALTER TABLE [dbo].[TichNapConfig] ADD [EventEndDate] DATETIME NULL");
            output("  ✓ Added EventEndDate column to TichNapConfig", 'success');
        }
        
        // Check if config exists, if not insert default
        $checkConfig = $accountDb->query("SELECT COUNT(*) as cnt FROM [dbo].[TichNapConfig]");
        $configCount = $checkConfig->fetch()['cnt'];
        
        if ($configCount == 0) {
            $accountDb->exec("
                INSERT INTO [dbo].[TichNapConfig] (FeatureEnabled, UpdatedDate)
                VALUES (1, GETDATE())
            ");
            output("  ✓ Inserted default config", 'success');
        }
    }
    
    // Step 7: Add AccumulatedDeposit column to TB_User
    output("", 'info');
    output("Step 7: Adding AccumulatedDeposit column to TB_User...", 'info');
    
    if (!columnExists($accountDb, '[dbo].[TB_User]', 'AccumulatedDeposit')) {
        $accountDb->exec("
            ALTER TABLE [dbo].[TB_User]
            ADD [AccumulatedDeposit] BIGINT NOT NULL DEFAULT 0
        ");
        
        output("  ✓ Added AccumulatedDeposit column to TB_User", 'success');
        
        // Update initial values from TB_Order
        try {
            $accountDb->exec("
                UPDATE u
                SET u.AccumulatedDeposit = ISNULL((
                    SELECT SUM(CAST(o.Amount AS BIGINT))
                    FROM TB_Order o
                    WHERE o.JID = u.JID 
                    AND o.Status = 'completed'
                ), 0)
                FROM TB_User u
                WHERE u.AccumulatedDeposit = 0
            ");
            output("  ✓ Updated initial AccumulatedDeposit values from TB_Order", 'success');
        } catch (Exception $e) {
            output("  ⚠ Warning: Could not update AccumulatedDeposit from TB_Order: " . $e->getMessage(), 'warning');
        }
    } else {
        output("  ✓ AccumulatedDeposit column already exists in TB_User", 'success');
    }
    
    // Summary
    output("", 'info');
    output("==========================================", 'success');
    output("Migration completed successfully!", 'success');
    output("==========================================", 'success');
    output("", 'info');
    output("Tables created/verified:", 'info');
    output("  ✓ SilkTichNap (Mốc nạp tích lũy)", 'success');
    output("  ✓ LogTichNap (Log nhận phần thưởng)", 'success');
    output("  ✓ TotalMoneyUser (Backup - Optional)", 'success');
    output("  ✓ GiftCodeItem (Vật phẩm phần thưởng)", 'success');
    output("  ✓ TaiLieuDinhKem (Tài liệu đính kèm)", 'success');
    output("  ✓ TichNapConfig (Cấu hình tính năng)", 'success');
    output("", 'info');
    output("Columns added to existing tables:", 'info');
    output("  ✓ TB_User.AccumulatedDeposit (Lưu trữ tích lũy nạp)", 'success');
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
