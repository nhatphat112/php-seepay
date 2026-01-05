<?php
/**
 * Run Migration Script: Thêm hệ thống phân quyền
 * Chạy script SQL migration bằng PHP
 */

require_once __DIR__ . '/../connection_manager.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=============================================\n";
echo "Migration Script: Thêm hệ thống phân quyền\n";
echo "=============================================\n\n";

try {
    $db = ConnectionManager::getAccountDB();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Thêm cột role vào bảng TB_User (nếu chưa có)
    echo "1. Kiểm tra và thêm cột role vào bảng TB_User...\n";
    
    $stmt = $db->query("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'TB_User' 
        AND COLUMN_NAME = 'role'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        $db->exec("ALTER TABLE [dbo].[TB_User] ADD [role] [varchar](20) NULL DEFAULT 'user'");
        echo "   ✓ Đã thêm cột role vào bảng TB_User\n";
    } else {
        echo "   ✓ Cột role đã tồn tại trong bảng TB_User\n";
    }
    
    // 2. Cập nhật tất cả user hiện tại thành 'user' (nếu role là NULL)
    echo "\n2. Cập nhật role cho tất cả user hiện tại...\n";
    
    $stmt = $db->prepare("UPDATE [dbo].[TB_User] SET [role] = 'user' WHERE [role] IS NULL");
    $stmt->execute();
    $affected = $stmt->rowCount();
    echo "   ✓ Đã cập nhật role cho $affected user\n";
    
    // 3. Tạo tài khoản admin mặc định (nếu chưa tồn tại)
    echo "\n3. Kiểm tra và tạo tài khoản admin...\n";
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM [dbo].[TB_User] WHERE [StrUserID] = ?");
    $stmt->execute(['adminsonglong']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Tạo tài khoản admin
        $stmt = $db->prepare("
            INSERT INTO [dbo].[TB_User] (
                [StrUserID], 
                [password], 
                [Email], 
                [role],
                [regtime],
                [Status],
                [sec_primary],
                [sec_content],
                [AccPlayTime],
                [LatestUpdateTime_ToPlayTime],
                [Play123Time]
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                GETDATE(),
                0,
                3,
                3,
                0,
                0,
                0
            )
        ");
        $stmt->execute([
            'adminsonglong',
            'f6fdffe48c908deb0f4c3bd36c032e72', // MD5 hash của 'adminadmin'
            'admin@songlong.com',
            'admin'
        ]);
        
        echo "   ✓ Đã tạo tài khoản admin: adminsonglong\n";
        
        // Lấy JID của admin vừa tạo
        $stmt = $db->prepare("SELECT [JID] FROM [dbo].[TB_User] WHERE [StrUserID] = ?");
        $stmt->execute(['adminsonglong']);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        $adminJID = $admin['JID'];
        
        // Tạo bản ghi SK_Silk cho admin
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM [dbo].[SK_Silk] WHERE [JID] = ?");
        $stmt->execute([$adminJID]);
        $silkCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($silkCheck['count'] == 0) {
            $stmt = $db->prepare("
                INSERT INTO [dbo].[SK_Silk] ([JID], [silk_own], [silk_gift], [silk_point])
                VALUES (?, 0, 0, 0)
            ");
            $stmt->execute([$adminJID]);
            echo "   ✓ Đã tạo bản ghi SK_Silk cho admin\n";
        }
    } else {
        // Nếu tài khoản đã tồn tại, cập nhật role thành admin
        $stmt = $db->prepare("UPDATE [dbo].[TB_User] SET [role] = 'admin' WHERE [StrUserID] = ?");
        $stmt->execute(['adminsonglong']);
        echo "   ✓ Đã cập nhật role admin cho tài khoản adminsonglong\n";
    }
    
    // 4. Hash password cho admin (MD5)
    echo "\n4. Cập nhật password cho tài khoản admin...\n";
    
    $stmt = $db->prepare("
        UPDATE [dbo].[TB_User] 
        SET [password] = ? 
        WHERE [StrUserID] = 'adminsonglong'
    ");
    $stmt->execute(['f6fdffe48c908deb0f4c3bd36c032e72']); // MD5 hash của 'adminadmin'
    echo "   ✓ Đã cập nhật password (MD5) cho tài khoản admin\n";
    
    // 5. Tạo index cho cột role (tùy chọn, để tối ưu query)
    echo "\n5. Kiểm tra và tạo index cho cột role...\n";
    
    try {
        $stmt = $db->query("
            SELECT COUNT(*) as count 
            FROM sys.indexes 
            WHERE name = 'IX_TB_User_role' 
            AND object_id = OBJECT_ID('dbo.TB_User')
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] == 0) {
            $db->exec("
                CREATE NONCLUSTERED INDEX [IX_TB_User_role] ON [dbo].[TB_User]
                ([role] ASC)
            ");
            echo "   ✓ Đã tạo index IX_TB_User_role\n";
        } else {
            echo "   ✓ Index IX_TB_User_role đã tồn tại\n";
        }
    } catch (Exception $e) {
        echo "   ⚠ Không thể tạo index (có thể đã tồn tại): " . $e->getMessage() . "\n";
    }
    
    // 6. Hiển thị thông tin tài khoản admin
    echo "\n6. Thông tin tài khoản admin:\n";
    
    $stmt = $db->prepare("
        SELECT 
            [JID],
            [StrUserID] AS Username,
            [Email],
            [role] AS Role,
            [regtime] AS CreatedDate,
            [Status]
        FROM [dbo].[TB_User]
        WHERE [StrUserID] = 'adminsonglong'
    ");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "   JID: " . $admin['JID'] . "\n";
        echo "   Username: " . $admin['Username'] . "\n";
        echo "   Email: " . $admin['Email'] . "\n";
        echo "   Role: " . $admin['Role'] . "\n";
        echo "   Created Date: " . $admin['CreatedDate'] . "\n";
        echo "   Status: " . $admin['Status'] . "\n";
    }
    
    echo "\n=============================================\n";
    echo "Migration hoàn tất!\n";
    echo "Tài khoản admin:\n";
    echo "  Username: adminsonglong\n";
    echo "  Password: adminadmin\n";
    echo "  Role: admin\n";
    echo "=============================================\n";
    
} catch (PDOException $e) {
    echo "\n❌ Lỗi PDO: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}

