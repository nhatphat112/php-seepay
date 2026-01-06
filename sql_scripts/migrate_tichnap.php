<?php
/**
 * Migration Script: Tạo bảng cho chức năng Nạp Tích Lũy
 * Script này chỉ tạo các bảng nếu chưa tồn tại, không xóa bất kỳ dữ liệu nào
 * 
 * Usage:
 *   php sql_scripts/migrate_tichnap.php
 */

require_once __DIR__ . '/../connection_manager.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=============================================\n";
echo "Migration Script: Nạp Tích Lũy\n";
echo "=============================================\n\n";

try {
    $db = ConnectionManager::getAccountDB();
    
    // 1. Kiểm tra và tạo bảng SilkTichNap
    echo "1. Kiểm tra bảng SilkTichNap...\n";
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'SilkTichNap'
    ");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] == 0) {
        echo "   → Tạo bảng SilkTichNap...\n";
        $db->exec("
            CREATE TABLE [dbo].[SilkTichNap] (
                [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
                [Rank] INT NOT NULL,
                [DsItem] NVARCHAR(MAX),
                [Description] NVARCHAR(MAX),
                [IsActive] BIT DEFAULT 0,
                [CreatedDate] DATETIME DEFAULT GETDATE(),
                [CreatedId] UNIQUEIDENTIFIER,
                [UpdatedDate] DATETIME,
                [UpdatedId] UNIQUEIDENTIFIER,
                [IsDelete] BIT DEFAULT 0
            )
        ");
        echo "   ✓ Đã tạo bảng SilkTichNap\n";
    } else {
        echo "   ✓ Bảng SilkTichNap đã tồn tại\n";
        
        // Kiểm tra và thêm cột IsActive nếu chưa có
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = 'dbo' 
            AND TABLE_NAME = 'SilkTichNap' 
            AND COLUMN_NAME = 'IsActive'
        ");
        $hasIsActive = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($hasIsActive['count'] == 0) {
            echo "   → Thêm cột IsActive...\n";
            $db->exec("ALTER TABLE [dbo].[SilkTichNap] ADD [IsActive] BIT DEFAULT 0");
            echo "   ✓ Đã thêm cột IsActive\n";
        }
    }
    echo "\n";
    
    // 2. Kiểm tra và tạo bảng LogTichNap
    echo "2. Kiểm tra bảng LogTichNap...\n";
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'LogTichNap'
    ");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] == 0) {
        echo "   → Tạo bảng LogTichNap...\n";
        $db->exec("
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
        
        // Tạo indexes
        try {
            $db->exec("CREATE NONCLUSTERED INDEX [IX_LogTichNap_CharName] ON [dbo].[LogTichNap] ([CharName])");
            $db->exec("CREATE NONCLUSTERED INDEX [IX_LogTichNap_IdTichNap] ON [dbo].[LogTichNap] ([IdTichNap])");
            echo "   ✓ Đã tạo indexes\n";
        } catch (Exception $e) {
            // Index có thể đã tồn tại, bỏ qua
        }
        
        echo "   ✓ Đã tạo bảng LogTichNap\n";
    } else {
        echo "   ✓ Bảng LogTichNap đã tồn tại\n";
    }
    echo "\n";
    
    // 3. Kiểm tra và tạo bảng TotalMoneyUser
    echo "3. Kiểm tra bảng TotalMoneyUser...\n";
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'TotalMoneyUser'
    ");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] == 0) {
        echo "   → Tạo bảng TotalMoneyUser...\n";
        $db->exec("
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
        
        // Tạo index
        try {
            $db->exec("CREATE NONCLUSTERED INDEX [IX_TotalMoneyUser_UserJID] ON [dbo].[TotalMoneyUser] ([UserJID])");
            echo "   ✓ Đã tạo index\n";
        } catch (Exception $e) {
            // Index có thể đã tồn tại, bỏ qua
        }
        
        echo "   ✓ Đã tạo bảng TotalMoneyUser\n";
    } else {
        echo "   ✓ Bảng TotalMoneyUser đã tồn tại\n";
    }
    echo "\n";
    
    // 4. Kiểm tra và tạo bảng GiftCodeItem
    echo "4. Kiểm tra bảng GiftCodeItem...\n";
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'GiftCodeItem'
    ");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] == 0) {
        echo "   → Tạo bảng GiftCodeItem...\n";
        $db->exec("
            CREATE TABLE [dbo].[GiftCodeItem] (
                [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
                [CodeItem] NVARCHAR(50) NOT NULL,
                [NameItem] NVARCHAR(200),
                [quanlity] INT DEFAULT 1,
                [CreatedDate] DATETIME DEFAULT GETDATE(),
                [IsDelete] BIT DEFAULT 0
            )
        ");
        
        // Tạo index
        try {
            $db->exec("CREATE NONCLUSTERED INDEX [IX_GiftCodeItem_CodeItem] ON [dbo].[GiftCodeItem] ([CodeItem])");
            echo "   ✓ Đã tạo index\n";
        } catch (Exception $e) {
            // Index có thể đã tồn tại, bỏ qua
        }
        
        echo "   ✓ Đã tạo bảng GiftCodeItem\n";
    } else {
        echo "   ✓ Bảng GiftCodeItem đã tồn tại\n";
    }
    echo "\n";
    
    // 5. Kiểm tra và tạo bảng TaiLieuDinhKem
    echo "5. Kiểm tra bảng TaiLieuDinhKem...\n";
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'TaiLieuDinhKem'
    ");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] == 0) {
        echo "   → Tạo bảng TaiLieuDinhKem...\n";
        $db->exec("
            CREATE TABLE [dbo].[TaiLieuDinhKem] (
                [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
                [Item_ID] UNIQUEIDENTIFIER,
                [LoaiTaiLieu] NVARCHAR(50),
                [DuongDanFile] NVARCHAR(500),
                [NgayPhatHanh] DATETIME,
                [CreatedDate] DATETIME DEFAULT GETDATE()
            )
        ");
        
        // Tạo indexes
        try {
            $db->exec("CREATE NONCLUSTERED INDEX [IX_TaiLieuDinhKem_Item_ID] ON [dbo].[TaiLieuDinhKem] ([Item_ID])");
            $db->exec("CREATE NONCLUSTERED INDEX [IX_TaiLieuDinhKem_LoaiTaiLieu] ON [dbo].[TaiLieuDinhKem] ([LoaiTaiLieu])");
            echo "   ✓ Đã tạo indexes\n";
        } catch (Exception $e) {
            // Index có thể đã tồn tại, bỏ qua
        }
        
        echo "   ✓ Đã tạo bảng TaiLieuDinhKem\n";
    } else {
        echo "   ✓ Bảng TaiLieuDinhKem đã tồn tại\n";
    }
    echo "\n";
    
    // 6. Kiểm tra và tạo bảng TichNapConfig
    echo "6. Kiểm tra bảng TichNapConfig...\n";
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'TichNapConfig'
    ");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exists['count'] == 0) {
        echo "   → Tạo bảng TichNapConfig...\n";
        $db->exec("
            CREATE TABLE [dbo].[TichNapConfig] (
                [Id] INT PRIMARY KEY IDENTITY(1,1),
                [FeatureEnabled] BIT DEFAULT 1,
                [UpdatedDate] DATETIME DEFAULT GETDATE(),
                [UpdatedBy] INT
            )
        ");
        
        // Insert default config
        $db->exec("
            INSERT INTO [dbo].[TichNapConfig] (FeatureEnabled, UpdatedDate)
            VALUES (1, GETDATE())
        ");
        
        echo "   ✓ Đã tạo bảng TichNapConfig và insert config mặc định\n";
    } else {
        echo "   ✓ Bảng TichNapConfig đã tồn tại\n";
        
        // Kiểm tra xem có config nào chưa, nếu chưa thì insert
        $stmt = $db->query("SELECT COUNT(*) as count FROM [dbo].[TichNapConfig]");
        $hasConfig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($hasConfig['count'] == 0) {
            echo "   → Insert config mặc định...\n";
            $db->exec("
                INSERT INTO [dbo].[TichNapConfig] (FeatureEnabled, UpdatedDate)
                VALUES (1, GETDATE())
            ");
            echo "   ✓ Đã insert config mặc định\n";
        }
    }
    echo "\n";
    
    // Summary
    echo "=============================================\n";
    echo "Migration hoàn tất!\n";
    echo "=============================================\n\n";
    
    // Hiển thị thống kê
    echo "Thống kê bảng:\n";
    echo str_repeat('-', 50) . "\n";
    
    $tables = ['SilkTichNap', 'LogTichNap', 'TotalMoneyUser', 'GiftCodeItem', 'TaiLieuDinhKem', 'TichNapConfig'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM [dbo].[$table]");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'] ?? 0;
            echo sprintf("  %-20s: %d records\n", $table, $count);
        } catch (Exception $e) {
            echo sprintf("  %-20s: Error\n", $table);
        }
    }
    
    echo "\n";
    echo "✓ Tất cả bảng đã được kiểm tra và tạo (nếu cần)\n";
    echo "✓ Không có dữ liệu nào bị xóa\n";
    echo "✓ Migration an toàn hoàn tất!\n";
    
} catch (Exception $e) {
    echo "\n";
    echo "✗✗✗ LỖI MIGRATION ✗✗✗\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}

