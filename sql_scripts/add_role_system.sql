-- =============================================
-- Migration Script: Thêm hệ thống phân quyền
-- Description: Thêm cột role và tạo tài khoản admin mặc định
-- =============================================

USE [SRO_VT_ACCOUNT]
GO

-- 1. Thêm cột role vào bảng TB_User (nếu chưa có)
IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'TB_User' 
    AND COLUMN_NAME = 'role'
)
BEGIN
    ALTER TABLE [dbo].[TB_User]
    ADD [role] [varchar](20) NULL DEFAULT 'user'
    
    PRINT 'Đã thêm cột role vào bảng TB_User'
END
ELSE
BEGIN
    PRINT 'Cột role đã tồn tại trong bảng TB_User'
END
GO

-- 2. Cập nhật tất cả user hiện tại thành 'user' (nếu role là NULL)
UPDATE [dbo].[TB_User]
SET [role] = 'user'
WHERE [role] IS NULL
GO

PRINT 'Đã cập nhật role cho tất cả user hiện tại'

-- 3. Tạo tài khoản admin mặc định (nếu chưa tồn tại)
IF NOT EXISTS (SELECT * FROM [dbo].[TB_User] WHERE [StrUserID] = 'adminsonglong')
BEGIN
    -- Tạo tài khoản admin
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
        'adminsonglong',
        'f6fdffe48c908deb0f4c3bd36c032e72', -- MD5 hash của 'adminadmin'
        'admin@songlong.com',
        'admin',
        GETDATE(),
        0,
        3,
        3,
        0,
        0,
        0
    )
    
    PRINT 'Đã tạo tài khoản admin: adminsonglong'
    
    -- Lấy JID của admin vừa tạo
    DECLARE @AdminJID INT
    SELECT @AdminJID = [JID] FROM [dbo].[TB_User] WHERE [StrUserID] = 'adminsonglong'
    
    -- Tạo bản ghi SK_Silk cho admin
    IF NOT EXISTS (SELECT * FROM [dbo].[SK_Silk] WHERE [JID] = @AdminJID)
    BEGIN
        INSERT INTO [dbo].[SK_Silk] ([JID], [silk_own], [silk_gift], [silk_point])
        VALUES (@AdminJID, 0, 0, 0)
        
        PRINT 'Đã tạo bản ghi SK_Silk cho admin'
    END
    
    -- Tạo bản ghi _AccountJID cho admin (nếu cần)
    -- Lưu ý: Cần kiểm tra xem bảng này có tồn tại trong DB SHARD không
    -- Nếu có, cần chạy script riêng cho DB SHARD
    
END
ELSE
BEGIN
    -- Nếu tài khoản đã tồn tại, cập nhật role thành admin
    UPDATE [dbo].[TB_User]
    SET [role] = 'admin'
    WHERE [StrUserID] = 'adminsonglong'
    
    PRINT 'Đã cập nhật role admin cho tài khoản adminsonglong'
END
GO

-- 4. Hash password cho admin (MD5)
-- Lưu ý: Password "adminadmin" sẽ được hash bằng MD5 trong ứng dụng
-- Nhưng để đảm bảo, có thể update trực tiếp ở đây nếu cần
-- UPDATE [dbo].[TB_User] 
-- SET [password] = '21232f297a57a5a743894a0e4a801fc3' -- MD5 của 'admin'
-- WHERE [StrUserID] = 'adminsonglong'

-- Password "adminadmin" MD5 hash: f6fdffe48c908deb0f4c3bd36c032e72
UPDATE [dbo].[TB_User] 
SET [password] = 'f6fdffe48c908deb0f4c3bd36c032e72'
WHERE [StrUserID] = 'adminsonglong'
GO

PRINT 'Đã cập nhật password (MD5) cho tài khoản admin'

-- 5. Tạo index cho cột role (tùy chọn, để tối ưu query)
IF NOT EXISTS (
    SELECT * FROM sys.indexes 
    WHERE name = 'IX_TB_User_role' 
    AND object_id = OBJECT_ID('dbo.TB_User')
)
BEGIN
    CREATE NONCLUSTERED INDEX [IX_TB_User_role] ON [dbo].[TB_User]
    (
        [role] ASC
    )
    
    PRINT 'Đã tạo index IX_TB_User_role'
END
GO

-- 6. Hiển thị thông tin tài khoản admin
SELECT 
    [JID],
    [StrUserID] AS Username,
    [Email],
    [role] AS Role,
    [regtime] AS CreatedDate,
    [Status]
FROM [dbo].[TB_User]
WHERE [StrUserID] = 'adminsonglong'
GO

PRINT '============================================='
PRINT 'Migration hoàn tất!'
PRINT 'Tài khoản admin:'
PRINT '  Username: adminsonglong'
PRINT '  Password: adminadmin'
PRINT '  Role: admin'
PRINT '============================================='

