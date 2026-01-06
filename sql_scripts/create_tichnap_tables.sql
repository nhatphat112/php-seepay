-- =============================================
-- Migration Script: Tạo bảng cho chức năng Nạp Tích Lũy
-- Description: Tạo các bảng SilkTichNap, LogTichNap, TotalMoneyUser, GiftCodeItem, TaiLieuDinhKem
-- =============================================

USE [SRO_VT_ACCOUNT]
GO

-- 1. Bảng SilkTichNap (Cấu hình mốc nạp)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[SilkTichNap]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[SilkTichNap] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [Rank] INT NOT NULL,                    -- Mốc tiền (VND)
        [DsItem] NVARCHAR(MAX),                 -- Danh sách ID item (phân cách bằng dấu phẩy)
        [Description] NVARCHAR(MAX),            -- Mô tả
        [IsActive] BIT DEFAULT 0,               -- Chỉ 1 mốc active tại một thời điểm
        [CreatedDate] DATETIME DEFAULT GETDATE(),
        [CreatedId] UNIQUEIDENTIFIER,
        [UpdatedDate] DATETIME,
        [UpdatedId] UNIQUEIDENTIFIER,
        [IsDelete] BIT DEFAULT 0
    );
    
    PRINT 'Đã tạo bảng SilkTichNap'
END
ELSE
BEGIN
    -- Thêm cột IsActive nếu bảng đã tồn tại
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'SilkTichNap' AND COLUMN_NAME = 'IsActive')
    BEGIN
        ALTER TABLE [dbo].[SilkTichNap]
        ADD [IsActive] BIT DEFAULT 0;
        PRINT 'Đã thêm cột IsActive vào bảng SilkTichNap'
    END
    PRINT 'Bảng SilkTichNap đã tồn tại'
END
GO

-- 2. Bảng TichNapConfig (Cấu hình tính năng)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TichNapConfig]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[TichNapConfig] (
        [Id] INT PRIMARY KEY IDENTITY(1,1),
        [FeatureEnabled] BIT DEFAULT 1,        -- Bật/tắt tính năng
        [UpdatedDate] DATETIME DEFAULT GETDATE(),
        [UpdatedBy] INT                         -- JID của admin cập nhật
    );
    
    -- Insert default config
    INSERT INTO [dbo].[TichNapConfig] (FeatureEnabled, UpdatedDate)
    VALUES (1, GETDATE());
    
    PRINT 'Đã tạo bảng TichNapConfig'
END
ELSE
BEGIN
    PRINT 'Bảng TichNapConfig đã tồn tại'
END
GO

-- 2. Bảng LogTichNap (Lịch sử nhận thưởng)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[LogTichNap]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[LogTichNap] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [CharName] NVARCHAR(50) NOT NULL,       -- Tên nhân vật
        [IdTichNap] UNIQUEIDENTIFIER NOT NULL,  -- ID mốc nạp đã nhận
        [Status] BIT DEFAULT 1,                 -- Trạng thái (đã nhận)
        [MaxPrice] BIGINT,                      -- Mốc tiền tương ứng
        [CreatedDate] DATETIME DEFAULT GETDATE(),
        [CreatedId] UNIQUEIDENTIFIER,
        [IsDelete] BIT DEFAULT 0
    );
    
    -- Tạo index cho CharName và IdTichNap để tối ưu query
    CREATE NONCLUSTERED INDEX [IX_LogTichNap_CharName] ON [dbo].[LogTichNap] ([CharName]);
    CREATE NONCLUSTERED INDEX [IX_LogTichNap_IdTichNap] ON [dbo].[LogTichNap] ([IdTichNap]);
    
    PRINT 'Đã tạo bảng LogTichNap'
END
ELSE
BEGIN
    PRINT 'Bảng LogTichNap đã tồn tại'
END
GO

-- 3. Bảng TotalMoneyUser (Tổng tiền đã nạp của user - Optional)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TotalMoneyUser]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[TotalMoneyUser] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [UserJID] INT NOT NULL,                 -- JID từ TB_User (INT)
        [TotalMoney] BIGINT NOT NULL,           -- Số tiền nạp
        [CreateDate] DATETIME NOT NULL DEFAULT GETDATE(), -- Ngày nạp
        [CreatedDate] DATETIME DEFAULT GETDATE(),
        [CreatedId] UNIQUEIDENTIFIER,
        [IsDelete] BIT DEFAULT 0
    );
    
    -- Tạo index cho UserJID để tối ưu query
    CREATE NONCLUSTERED INDEX [IX_TotalMoneyUser_UserJID] ON [dbo].[TotalMoneyUser] ([UserJID]);
    
    PRINT 'Đã tạo bảng TotalMoneyUser'
END
ELSE
BEGIN
    PRINT 'Bảng TotalMoneyUser đã tồn tại'
END
GO

-- 4. Bảng GiftCodeItem (Vật phẩm phần thưởng)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[GiftCodeItem]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[GiftCodeItem] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [CodeItem] NVARCHAR(50) NOT NULL,      -- Mã item trong game
        [NameItem] NVARCHAR(200),               -- Tên item
        [quanlity] INT DEFAULT 1,               -- Số lượng
        [CreatedDate] DATETIME DEFAULT GETDATE(),
        [IsDelete] BIT DEFAULT 0
    );
    
    -- Tạo index cho CodeItem
    CREATE NONCLUSTERED INDEX [IX_GiftCodeItem_CodeItem] ON [dbo].[GiftCodeItem] ([CodeItem]);
    
    PRINT 'Đã tạo bảng GiftCodeItem'
END
ELSE
BEGIN
    PRINT 'Bảng GiftCodeItem đã tồn tại'
END
GO

-- 5. Bảng TaiLieuDinhKem (Hình ảnh item)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TaiLieuDinhKem]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[TaiLieuDinhKem] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [Item_ID] UNIQUEIDENTIFIER,             -- ID GiftCodeItem
        [LoaiTaiLieu] NVARCHAR(50),             -- "IconVP"
        [DuongDanFile] NVARCHAR(500),           -- Đường dẫn hình ảnh
        [NgayPhatHanh] DATETIME,
        [CreatedDate] DATETIME DEFAULT GETDATE()
    );
    
    -- Tạo index cho Item_ID và LoaiTaiLieu
    CREATE NONCLUSTERED INDEX [IX_TaiLieuDinhKem_Item_ID] ON [dbo].[TaiLieuDinhKem] ([Item_ID]);
    CREATE NONCLUSTERED INDEX [IX_TaiLieuDinhKem_LoaiTaiLieu] ON [dbo].[TaiLieuDinhKem] ([LoaiTaiLieu]);
    
    PRINT 'Đã tạo bảng TaiLieuDinhKem'
END
ELSE
BEGIN
    PRINT 'Bảng TaiLieuDinhKem đã tồn tại'
END
GO

-- 6. Thêm dữ liệu mẫu (Optional - có thể xóa sau)
-- INSERT INTO [dbo].[SilkTichNap] (Rank, DsItem, Description)
-- VALUES 
--     (100000, 'guid1,guid2', 'Phần thưởng mốc 100k'),
--     (500000, 'guid3,guid4', 'Phần thưởng mốc 500k'),
--     (1000000, 'guid5,guid6', 'Phần thưởng mốc 1 triệu');

PRINT '============================================='
PRINT 'Migration hoàn tất!'
PRINT 'Đã tạo các bảng:'
PRINT '  - SilkTichNap'
PRINT '  - LogTichNap'
PRINT '  - TotalMoneyUser'
PRINT '  - GiftCodeItem'
PRINT '  - TaiLieuDinhKem'
PRINT '============================================='

