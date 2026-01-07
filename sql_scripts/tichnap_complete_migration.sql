-- ============================================================
-- Migration Script: Hoàn chỉnh cho chức năng Nạp Tích Lũy
-- Description: Tạo tất cả bảng mới và thêm cột vào bảng có sẵn
-- 
-- NGUYÊN TẮC AN TOÀN:
-- - CHỈ BỔ SUNG: Tạo bảng mới, thêm cột mới, tạo index mới
-- - KHÔNG XÓA: Không DROP TABLE, DROP COLUMN, DELETE, TRUNCATE
-- - KHÔNG SỬA: Không ALTER COLUMN, không thay đổi kiểu dữ liệu
-- - IDEMPOTENT: Có thể chạy nhiều lần mà không gây lỗi
-- - TƯƠNG THÍCH: Không làm lỗi các câu query cũ
-- ============================================================

USE SRO_VT_ACCOUNT;
GO

-- ============================================================
-- 1. BẢNG MỚI: SilkTichNap (Mốc nạp tích lũy)
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[SilkTichNap]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[SilkTichNap] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [Rank] INT NOT NULL,                          -- Mốc tiền (VND)
        [DsItem] NVARCHAR(MAX),                       -- Danh sách item (deprecated, dùng ItemsJson)
        [ItemsJson] NVARCHAR(MAX) NULL,               -- JSON chứa danh sách item phần thưởng
        [Description] NVARCHAR(MAX),                  -- Mô tả mốc nạp
        [IsActive] BIT DEFAULT 0,                    -- Trạng thái active/inactive
        [CreatedDate] DATETIME DEFAULT GETDATE(),
        [CreatedId] UNIQUEIDENTIFIER,
        [UpdatedDate] DATETIME,
        [UpdatedId] UNIQUEIDENTIFIER,
        [IsDelete] BIT DEFAULT 0
    );
    
    PRINT 'Đã tạo bảng SilkTichNap';
END
ELSE
BEGIN
    PRINT 'Bảng SilkTichNap đã tồn tại';
    
    -- Thêm cột IsActive nếu chưa có
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('[dbo].[SilkTichNap]') AND name = 'IsActive')
    BEGIN
        ALTER TABLE [dbo].[SilkTichNap] ADD [IsActive] BIT DEFAULT 0;
        PRINT 'Đã thêm cột IsActive vào SilkTichNap';
    END
    
    -- Thêm cột ItemsJson nếu chưa có
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('[dbo].[SilkTichNap]') AND name = 'ItemsJson')
    BEGIN
        ALTER TABLE [dbo].[SilkTichNap] ADD [ItemsJson] NVARCHAR(MAX) NULL;
        PRINT 'Đã thêm cột ItemsJson vào SilkTichNap';
    END
END
GO

-- ============================================================
-- 2. BẢNG MỚI: LogTichNap (Log nhận phần thưởng)
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[LogTichNap]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[LogTichNap] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [CharName] NVARCHAR(50) NOT NULL,              -- Tên nhân vật nhận phần thưởng
        [IdTichNap] UNIQUEIDENTIFIER NOT NULL,         -- ID mốc nạp (FK to SilkTichNap)
        [Status] BIT DEFAULT 1,                       -- Trạng thái đã nhận
        [MaxPrice] BIGINT,                            -- Mốc tiền tại thời điểm nhận
        [CreatedDate] DATETIME DEFAULT GETDATE(),
        [CreatedId] UNIQUEIDENTIFIER,
        [IsDelete] BIT DEFAULT 0
    );
    
    -- Tạo indexes
    IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_LogTichNap_CharName' AND object_id = OBJECT_ID('[dbo].[LogTichNap]'))
    BEGIN
        CREATE NONCLUSTERED INDEX [IX_LogTichNap_CharName] ON [dbo].[LogTichNap] ([CharName]);
    END
    
    IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_LogTichNap_IdTichNap' AND object_id = OBJECT_ID('[dbo].[LogTichNap]'))
    BEGIN
        CREATE NONCLUSTERED INDEX [IX_LogTichNap_IdTichNap] ON [dbo].[LogTichNap] ([IdTichNap]);
    END
    
    PRINT 'Đã tạo bảng LogTichNap';
END
ELSE
BEGIN
    PRINT 'Bảng LogTichNap đã tồn tại';
END
GO

-- ============================================================
-- 3. BẢNG MỚI: TotalMoneyUser (Backup - Optional)
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TotalMoneyUser]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[TotalMoneyUser] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [UserJID] INT NOT NULL,                       -- JID từ TB_User
        [TotalMoney] BIGINT NOT NULL,                  -- Số tiền nạp
        [CreateDate] DATETIME NOT NULL DEFAULT GETDATE(),
        [CreatedDate] DATETIME DEFAULT GETDATE(),
        [CreatedId] UNIQUEIDENTIFIER,
        [IsDelete] BIT DEFAULT 0
    );
    
    -- Tạo index
    IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_TotalMoneyUser_UserJID' AND object_id = OBJECT_ID('[dbo].[TotalMoneyUser]'))
    BEGIN
        CREATE NONCLUSTERED INDEX [IX_TotalMoneyUser_UserJID] ON [dbo].[TotalMoneyUser] ([UserJID]);
    END
    
    PRINT 'Đã tạo bảng TotalMoneyUser';
END
ELSE
BEGIN
    PRINT 'Bảng TotalMoneyUser đã tồn tại';
END
GO

-- ============================================================
-- 4. BẢNG MỚI: GiftCodeItem (Vật phẩm phần thưởng)
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[GiftCodeItem]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[GiftCodeItem] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [CodeItem] NVARCHAR(50) NOT NULL,              -- Mã item (ví dụ: ITEM_MALL_QUIVER)
        [NameItem] NVARCHAR(200),                      -- Tên item
        [quanlity] INT DEFAULT 1,                      -- Số lượng
        [CreatedDate] DATETIME DEFAULT GETDATE(),
        [IsDelete] BIT DEFAULT 0
    );
    
    -- Tạo index
    IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_GiftCodeItem_CodeItem' AND object_id = OBJECT_ID('[dbo].[GiftCodeItem]'))
    BEGIN
        CREATE NONCLUSTERED INDEX [IX_GiftCodeItem_CodeItem] ON [dbo].[GiftCodeItem] ([CodeItem]);
    END
    
    PRINT 'Đã tạo bảng GiftCodeItem';
END
ELSE
BEGIN
    PRINT 'Bảng GiftCodeItem đã tồn tại';
END
GO

-- ============================================================
-- 5. BẢNG MỚI: TaiLieuDinhKem (Tài liệu đính kèm)
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TaiLieuDinhKem]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[TaiLieuDinhKem] (
        [Id] UNIQUEIDENTIFIER PRIMARY KEY DEFAULT NEWID(),
        [Item_ID] UNIQUEIDENTIFIER,                    -- ID item liên quan
        [LoaiTaiLieu] NVARCHAR(50),                    -- Loại tài liệu
        [DuongDanFile] NVARCHAR(500),                  -- Đường dẫn file
        [NgayPhatHanh] DATETIME,                       -- Ngày phát hành
        [CreatedDate] DATETIME DEFAULT GETDATE()
    );
    
    -- Tạo indexes
    IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_TaiLieuDinhKem_Item_ID' AND object_id = OBJECT_ID('[dbo].[TaiLieuDinhKem]'))
    BEGIN
        CREATE NONCLUSTERED INDEX [IX_TaiLieuDinhKem_Item_ID] ON [dbo].[TaiLieuDinhKem] ([Item_ID]);
    END
    
    IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_TaiLieuDinhKem_LoaiTaiLieu' AND object_id = OBJECT_ID('[dbo].[TaiLieuDinhKem]'))
    BEGIN
        CREATE NONCLUSTERED INDEX [IX_TaiLieuDinhKem_LoaiTaiLieu] ON [dbo].[TaiLieuDinhKem] ([LoaiTaiLieu]);
    END
    
    PRINT 'Đã tạo bảng TaiLieuDinhKem';
END
ELSE
BEGIN
    PRINT 'Bảng TaiLieuDinhKem đã tồn tại';
END
GO

-- ============================================================
-- 6. BẢNG MỚI: TichNapConfig (Cấu hình tính năng)
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TichNapConfig]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[TichNapConfig] (
        [Id] INT PRIMARY KEY IDENTITY(1,1),
        [FeatureEnabled] BIT DEFAULT 1,                 -- Bật/tắt tính năng
        [EventStartDate] DATETIME NULL,                -- Thời gian bắt đầu sự kiện
        [EventEndDate] DATETIME NULL,                  -- Thời gian kết thúc sự kiện
        [UpdatedDate] DATETIME DEFAULT GETDATE(),
        [UpdatedBy] INT                                -- User ID cập nhật
    );
    
    -- Insert config mặc định
    INSERT INTO [dbo].[TichNapConfig] (FeatureEnabled, UpdatedDate)
    VALUES (1, GETDATE());
    
    PRINT 'Đã tạo bảng TichNapConfig và insert config mặc định';
END
ELSE
BEGIN
    PRINT 'Bảng TichNapConfig đã tồn tại';
    
    -- Thêm cột EventStartDate nếu chưa có
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('[dbo].[TichNapConfig]') AND name = 'EventStartDate')
    BEGIN
        ALTER TABLE [dbo].[TichNapConfig] ADD [EventStartDate] DATETIME NULL;
        PRINT 'Đã thêm cột EventStartDate vào TichNapConfig';
    END
    
    -- Thêm cột EventEndDate nếu chưa có
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('[dbo].[TichNapConfig]') AND name = 'EventEndDate')
    BEGIN
        ALTER TABLE [dbo].[TichNapConfig] ADD [EventEndDate] DATETIME NULL;
        PRINT 'Đã thêm cột EventEndDate vào TichNapConfig';
    END
    
    -- Kiểm tra xem có config nào chưa, nếu chưa thì insert
    IF NOT EXISTS (SELECT * FROM [dbo].[TichNapConfig])
    BEGIN
        INSERT INTO [dbo].[TichNapConfig] (FeatureEnabled, UpdatedDate)
        VALUES (1, GETDATE());
        PRINT 'Đã insert config mặc định vào TichNapConfig';
    END
END
GO

-- ============================================================
-- 7. THÊM CỘT VÀO BẢNG CÓ SẴN: TB_User
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID('[dbo].[TB_User]') AND name = 'AccumulatedDeposit')
BEGIN
    ALTER TABLE [dbo].[TB_User]
    ADD [AccumulatedDeposit] BIGINT NOT NULL DEFAULT 0;
    
    PRINT 'Đã thêm column AccumulatedDeposit vào bảng TB_User';
    
    -- Cập nhật giá trị ban đầu từ TB_Order (nếu AccumulatedDeposit = 0)
    UPDATE u
    SET u.AccumulatedDeposit = ISNULL((
        SELECT SUM(CAST(o.Amount AS BIGINT))
        FROM TB_Order o
        WHERE o.JID = u.JID 
        AND o.Status = 'completed'
    ), 0)
    FROM TB_User u
    WHERE u.AccumulatedDeposit = 0;
    
    PRINT 'Đã cập nhật giá trị ban đầu cho AccumulatedDeposit từ TB_Order';
END
ELSE
BEGIN
    PRINT 'Column AccumulatedDeposit đã tồn tại trong bảng TB_User';
END
GO

-- ============================================================
-- SUMMARY
-- ============================================================
PRINT '';
PRINT '=============================================';
PRINT 'Migration hoàn tất!';
PRINT '=============================================';
PRINT '';
PRINT 'Các bảng đã được kiểm tra và tạo (nếu cần):';
PRINT '  - SilkTichNap (Mốc nạp tích lũy)';
PRINT '  - LogTichNap (Log nhận phần thưởng)';
PRINT '  - TotalMoneyUser (Backup - Optional)';
PRINT '  - GiftCodeItem (Vật phẩm phần thưởng)';
PRINT '  - TaiLieuDinhKem (Tài liệu đính kèm)';
PRINT '  - TichNapConfig (Cấu hình tính năng)';
PRINT '';
PRINT 'Các cột đã được thêm vào bảng có sẵn:';
PRINT '  - TB_User.AccumulatedDeposit (Lưu trữ tích lũy nạp)';
PRINT '';
PRINT '✓ Không có dữ liệu nào bị xóa';
PRINT '✓ Migration an toàn hoàn tất!';
GO

