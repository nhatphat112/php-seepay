-- =============================================
-- COMPLETE DATABASE SETUP FOR SEPAY PAYMENT SYSTEM
-- Database: SRO_VT_ACCOUNT
-- Date: 2026-01-01
-- Description: Complete database schema for Sepay payment integration
-- =============================================

USE [SRO_VT_ACCOUNT]
GO

PRINT '========================================'
PRINT 'Starting Complete Database Setup...'
PRINT '========================================'
PRINT ''

-- =============================================
-- STEP 1: DROP EXISTING TABLES (if exists)
-- =============================================
PRINT 'Step 1: Dropping existing tables (if any)...'

-- Drop tables in reverse dependency order
IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TB_Order]') AND type in (N'U'))
BEGIN
    DROP TABLE [dbo].[TB_Order]
    PRINT '  ✓ Dropped TB_Order'
END

IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[SK_Silk]') AND type in (N'U'))
BEGIN
    DROP TABLE [dbo].[SK_Silk]
    PRINT '  ✓ Dropped SK_Silk'
END

IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TB_User]') AND type in (N'U'))
BEGIN
    DROP TABLE [dbo].[TB_User]
    PRINT '  ✓ Dropped TB_User'
END

PRINT ''

-- =============================================
-- STEP 2: CREATE TB_User TABLE
-- =============================================
PRINT 'Step 2: Creating TB_User table...'

SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[TB_User](
	[JID] [int] IDENTITY(1,1) NOT NULL,
	[StrUserID] [varchar](25) NOT NULL,
	[password] [varchar](50) NOT NULL,
	[SecretAnswer] [nvarchar](255) NULL,
	[Status] [tinyint] NULL,
	[GMrank] [tinyint] NULL,
	[Name] [nvarchar](50) NULL,
	[Email] [varchar](50) NULL,
	[sex] [char](2) NULL,
	[certificate_num] [varchar](30) NULL,
	[address] [nvarchar](100) NULL,
	[postcode] [varchar](10) NULL,
	[phone] [varchar](20) NULL,
	[mobile] [varchar](20) NULL,
	[regtime] [datetime] NULL,
	[reg_ip] [varchar](25) NULL,
	[Time_log] [datetime] NULL,
	[freetime] [int] NULL,
	[sec_primary] [tinyint] NOT NULL,
	[sec_content] [tinyint] NOT NULL,
	[AccPlayTime] [int] NOT NULL,
	[LatestUpdateTime_ToPlayTime] [int] NOT NULL,
	[Play123Time] [int] NOT NULL,
 CONSTRAINT [PK_TB_User] PRIMARY KEY CLUSTERED 
(
	[JID] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, FILLFACTOR = 90, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

-- Add Default Constraints for TB_User
ALTER TABLE [dbo].[TB_User] ADD  CONSTRAINT [DF_TB_User_sec_primary]  DEFAULT ((3)) FOR [sec_primary]
GO

ALTER TABLE [dbo].[TB_User] ADD  CONSTRAINT [DF_TB_User_sec_content]  DEFAULT ((3)) FOR [sec_content]
GO

ALTER TABLE [dbo].[TB_User] ADD  CONSTRAINT [DF__TB_User__AccPlay__3BFFE745]  DEFAULT ((0)) FOR [AccPlayTime]
GO

ALTER TABLE [dbo].[TB_User] ADD  CONSTRAINT [DF__TB_User__LatestU__3CF40B7E]  DEFAULT ((0)) FOR [LatestUpdateTime_ToPlayTime]
GO

ALTER TABLE [dbo].[TB_User] ADD  CONSTRAINT [DF_TB_User_Play123Time]  DEFAULT ((0)) FOR [Play123Time]
GO

-- Create Indexes for TB_User
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_TB_User_StrUserID' AND object_id = OBJECT_ID('TB_User'))
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX [IX_TB_User_StrUserID] ON [dbo].[TB_User]
    (
        [StrUserID] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    PRINT '  ✓ Created index IX_TB_User_StrUserID'
END
GO

IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_TB_User_Email' AND object_id = OBJECT_ID('TB_User'))
BEGIN
    CREATE NONCLUSTERED INDEX [IX_TB_User_Email] ON [dbo].[TB_User]
    (
        [Email] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    PRINT '  ✓ Created index IX_TB_User_Email'
END
GO

PRINT '  ✓ TB_User table created successfully'
PRINT ''

-- =============================================
-- STEP 3: CREATE SK_Silk TABLE
-- =============================================
PRINT 'Step 3: Creating SK_Silk table...'

SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[SK_Silk](
	[JID] [int] NOT NULL,
	[silk_own] [int] NOT NULL,
	[silk_gift] [int] NOT NULL,
	[silk_point] [int] NOT NULL
) ON [PRIMARY]
GO

-- Add Default Constraints for SK_Silk
ALTER TABLE [dbo].[SK_Silk] ADD  CONSTRAINT [DF_SK_Silk_silk_own]  DEFAULT ((0)) FOR [silk_own]
GO

ALTER TABLE [dbo].[SK_Silk] ADD  CONSTRAINT [DF_SK_Silk_silk_gift]  DEFAULT ((0)) FOR [silk_gift]
GO

ALTER TABLE [dbo].[SK_Silk] ADD  CONSTRAINT [DF_SK_Silk_silk_point]  DEFAULT ((0)) FOR [silk_point]
GO

-- Create Primary Key for SK_Silk
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'PK_SK_Silk' AND object_id = OBJECT_ID('SK_Silk'))
BEGIN
    ALTER TABLE [dbo].[SK_Silk] ADD CONSTRAINT [PK_SK_Silk] PRIMARY KEY CLUSTERED 
    (
        [JID] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, FILLFACTOR = 90, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
    PRINT '  ✓ Created primary key PK_SK_Silk'
END
GO

-- Create Foreign Key to TB_User
IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_SK_Silk_TB_User')
BEGIN
    ALTER TABLE [dbo].[SK_Silk]  WITH CHECK ADD  CONSTRAINT [FK_SK_Silk_TB_User] FOREIGN KEY([JID])
    REFERENCES [dbo].[TB_User] ([JID])
    ON DELETE CASCADE
    ON UPDATE CASCADE
    PRINT '  ✓ Created foreign key FK_SK_Silk_TB_User'
END
GO

ALTER TABLE [dbo].[SK_Silk] CHECK CONSTRAINT [FK_SK_Silk_TB_User]
GO

PRINT '  ✓ SK_Silk table created successfully'
PRINT ''

-- =============================================
-- STEP 4: CREATE TB_Order TABLE (Sepay Orders)
-- =============================================
PRINT 'Step 4: Creating TB_Order table (Sepay orders)...'

SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[TB_Order](
	[OrderID] [bigint] IDENTITY(1,1) NOT NULL,
	[JID] [int] NOT NULL,
	[OrderCode] [varchar](100) NOT NULL,
	[Amount] [decimal](18, 2) NOT NULL,
	[SilkAmount] [int] NOT NULL,
	[PaymentMethod] [varchar](50) NULL,
	[Status] [varchar](20) NOT NULL DEFAULT 'pending',
	[SepayTransactionID] [varchar](100) NULL,
	[QRCode] [nvarchar](500) NULL,
	[BankAccount] [nvarchar](100) NULL,
	[BankName] [nvarchar](100) NULL,
	[AccountName] [nvarchar](100) NULL,
	[Content] [nvarchar](200) NULL,
	[ExpiredAt] [datetime] NULL,
	[IPAddress] [varchar](45) NULL,
	[UserAgent] [nvarchar](500) NULL,
	[CreatedDate] [datetime] NOT NULL DEFAULT GETDATE(),
	[UpdatedDate] [datetime] NULL,
	[CompletedDate] [datetime] NULL,
	[Notes] [nvarchar](1000) NULL,
 CONSTRAINT [PK_TB_Order] PRIMARY KEY CLUSTERED 
(
	[OrderID] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, FILLFACTOR = 90, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

-- Create Unique Index on OrderCode
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Order_OrderCode' AND object_id = OBJECT_ID('TB_Order'))
BEGIN
    CREATE UNIQUE NONCLUSTERED INDEX [IX_Order_OrderCode] ON [dbo].[TB_Order]
    (
        [OrderCode] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    PRINT '  ✓ Created unique index IX_Order_OrderCode'
END
GO

-- Create Index on JID
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Order_JID' AND object_id = OBJECT_ID('TB_Order'))
BEGIN
    CREATE NONCLUSTERED INDEX [IX_Order_JID] ON [dbo].[TB_Order]
    (
        [JID] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    PRINT '  ✓ Created index IX_Order_JID'
END
GO

-- Create Index on Status and CreatedDate
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Order_Status' AND object_id = OBJECT_ID('TB_Order'))
BEGIN
    CREATE NONCLUSTERED INDEX [IX_Order_Status] ON [dbo].[TB_Order]
    (
        [Status] ASC,
        [CreatedDate] DESC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    PRINT '  ✓ Created index IX_Order_Status'
END
GO

-- Create Index on SepayTransactionID
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = 'IX_Order_SepayTransactionID' AND object_id = OBJECT_ID('TB_Order'))
BEGIN
    CREATE NONCLUSTERED INDEX [IX_Order_SepayTransactionID] ON [dbo].[TB_Order]
    (
        [SepayTransactionID] ASC
    )WHERE [SepayTransactionID] IS NOT NULL
    WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    PRINT '  ✓ Created index IX_Order_SepayTransactionID'
END
GO

-- Create Foreign Key to TB_User
IF NOT EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_Order_TB_User')
BEGIN
    ALTER TABLE [dbo].[TB_Order]  WITH CHECK ADD  CONSTRAINT [FK_Order_TB_User] FOREIGN KEY([JID])
    REFERENCES [dbo].[TB_User] ([JID])
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
    PRINT '  ✓ Created foreign key FK_Order_TB_User'
END
GO

ALTER TABLE [dbo].[TB_Order] CHECK CONSTRAINT [FK_Order_TB_User]
GO

PRINT '  ✓ TB_Order table created successfully'
PRINT ''

-- =============================================
-- STEP 5: CREATE Sk_SilkLog TABLE (Optional - for transaction logging)
-- =============================================
PRINT 'Step 5: Creating Sk_SilkLog table (transaction log)...'

IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[Sk_SilkLog]') AND type in (N'U'))
BEGIN
    CREATE TABLE [dbo].[Sk_SilkLog](
        [ID] [bigint] IDENTITY(1,1) NOT NULL,
        [UserName] [varchar](25) NULL,
        [Silk_nap] [int] NOT NULL DEFAULT 0,
        [Text] [nvarchar](500) NULL,
        [Time_log] [datetime] NOT NULL DEFAULT GETDATE(),
     CONSTRAINT [PK_Sk_SilkLog] PRIMARY KEY CLUSTERED 
    (
        [ID] ASC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, FILLFACTOR = 90, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
    ) ON [PRIMARY]
    
    -- Create Index on UserName
    CREATE NONCLUSTERED INDEX [IX_SilkLog_UserName] ON [dbo].[Sk_SilkLog]
    (
        [UserName] ASC,
        [Time_log] DESC
    )WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]
    
    PRINT '  ✓ Sk_SilkLog table created successfully'
END
ELSE
BEGIN
    PRINT '  ✓ Sk_SilkLog table already exists'
END
GO

PRINT ''

-- =============================================
-- STEP 6: VERIFICATION
-- =============================================
PRINT 'Step 6: Verifying database setup...'
PRINT ''

-- Check all tables
DECLARE @TableCount INT = 0

IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TB_User]') AND type in (N'U'))
BEGIN
    SET @TableCount = @TableCount + 1
    PRINT '  ✓ TB_User table exists'
    
    DECLARE @UserCount INT
    SELECT @UserCount = COUNT(*) FROM [dbo].[TB_User]
    PRINT '     Row count: ' + CAST(@UserCount AS VARCHAR(10))
END
ELSE
    PRINT '  ✗ TB_User table NOT found'

IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[SK_Silk]') AND type in (N'U'))
BEGIN
    SET @TableCount = @TableCount + 1
    PRINT '  ✓ SK_Silk table exists'
    
    DECLARE @SilkCount INT
    SELECT @SilkCount = COUNT(*) FROM [dbo].[SK_Silk]
    PRINT '     Row count: ' + CAST(@SilkCount AS VARCHAR(10))
END
ELSE
    PRINT '  ✗ SK_Silk table NOT found'

IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[TB_Order]') AND type in (N'U'))
BEGIN
    SET @TableCount = @TableCount + 1
    PRINT '  ✓ TB_Order table exists'
    
    DECLARE @OrderCount INT
    SELECT @OrderCount = COUNT(*) FROM [dbo].[TB_Order]
    PRINT '     Row count: ' + CAST(@OrderCount AS VARCHAR(10))
END
ELSE
    PRINT '  ✗ TB_Order table NOT found'

IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[Sk_SilkLog]') AND type in (N'U'))
BEGIN
    SET @TableCount = @TableCount + 1
    PRINT '  ✓ Sk_SilkLog table exists'
    
    DECLARE @LogCount INT
    SELECT @LogCount = COUNT(*) FROM [dbo].[Sk_SilkLog]
    PRINT '     Row count: ' + CAST(@LogCount AS VARCHAR(10))
END
ELSE
    PRINT '  ✗ Sk_SilkLog table NOT found'

PRINT ''
PRINT '========================================'
PRINT 'Database Setup Summary:'
PRINT '========================================'
PRINT 'Total tables created: ' + CAST(@TableCount AS VARCHAR(10))
PRINT ''
PRINT 'Tables:'
PRINT '  1. TB_User - User accounts'
PRINT '  2. SK_Silk - Silk currency management'
PRINT '  3. TB_Order - Sepay payment orders'
PRINT '  4. Sk_SilkLog - Transaction log'
PRINT ''
PRINT 'Relationships:'
PRINT '  - SK_Silk.JID → TB_User.JID (CASCADE)'
PRINT '  - TB_Order.JID → TB_User.JID (NO ACTION)'
PRINT ''
PRINT '========================================'
PRINT 'Database setup completed successfully!'
PRINT '========================================'
GO

