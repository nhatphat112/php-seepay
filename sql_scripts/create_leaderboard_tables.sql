-- ==========================================
-- Lucky Wheel Leaderboard Tables
-- ==========================================

-- Table: LuckyWheelSeasons
-- Lưu thông tin các mùa giải
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[LuckyWheelSeasons]') AND type in (N'U'))
BEGIN
    CREATE TABLE LuckyWheelSeasons (
        Id INT PRIMARY KEY IDENTITY(1,1),
        SeasonName NVARCHAR(100) NOT NULL,
        SeasonType NVARCHAR(20) NOT NULL, -- 'WEEK' hoặc 'DAY'
        StartDate DATETIME NOT NULL,
        EndDate DATETIME NULL,
        IsActive BIT DEFAULT 0, -- 0 = sắp bắt đầu hoặc đã kết thúc, 1 = đang active
        Status NVARCHAR(20) DEFAULT 'PENDING', -- 'PENDING', 'ACTIVE', 'ENDED'
        CreatedDate DATETIME DEFAULT GETDATE(),
        UpdatedDate DATETIME DEFAULT GETDATE(),
        CONSTRAINT UQ_SeasonName UNIQUE(SeasonName)
    );
    
    CREATE INDEX IX_Seasons_IsActive ON LuckyWheelSeasons(IsActive);
    CREATE INDEX IX_Seasons_StartDate ON LuckyWheelSeasons(StartDate);
    CREATE INDEX IX_Seasons_Status ON LuckyWheelSeasons(Status);
    
    PRINT 'Table LuckyWheelSeasons created successfully';
END
ELSE
BEGIN
    PRINT 'Table LuckyWheelSeasons already exists';
END
GO

-- Table: LuckyWheelSeasonLog
-- Lưu BXH từng mùa (user + số lượt quay)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[LuckyWheelSeasonLog]') AND type in (N'U'))
BEGIN
    CREATE TABLE LuckyWheelSeasonLog (
        Id INT PRIMARY KEY IDENTITY(1,1),
        SeasonId INT NOT NULL,
        UserJID INT NOT NULL,
        Username NVARCHAR(50) NOT NULL, -- Lưu username tại thời điểm quay
        TotalSpins INT DEFAULT 0,
        LastSpinDate DATETIME NULL,
        Rank INT NULL, -- Rank trong mùa (1-5), tính toán khi query
        CreatedDate DATETIME DEFAULT GETDATE(),
        UpdatedDate DATETIME DEFAULT GETDATE(),
        CONSTRAINT FK_SeasonLog_Season FOREIGN KEY (SeasonId) REFERENCES LuckyWheelSeasons(Id) ON DELETE CASCADE,
        CONSTRAINT UQ_SeasonLog_User UNIQUE(SeasonId, UserJID)
    );
    
    CREATE INDEX IX_SeasonLog_SeasonId_TotalSpins ON LuckyWheelSeasonLog(SeasonId, TotalSpins DESC);
    CREATE INDEX IX_SeasonLog_UserJID ON LuckyWheelSeasonLog(UserJID);
    
    PRINT 'Table LuckyWheelSeasonLog created successfully';
END
ELSE
BEGIN
    PRINT 'Table LuckyWheelSeasonLog already exists';
END
GO

-- Update LuckyWheelLog: Thêm SeasonId
IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[LuckyWheelLog]') AND name = 'SeasonId')
BEGIN
    ALTER TABLE LuckyWheelLog 
    ADD SeasonId INT NULL;
    
    ALTER TABLE LuckyWheelLog
    ADD CONSTRAINT FK_LuckyWheelLog_Season FOREIGN KEY (SeasonId) REFERENCES LuckyWheelSeasons(Id);
    
    CREATE INDEX IX_LuckyWheelLog_SeasonId ON LuckyWheelLog(SeasonId);
    
    PRINT 'Column SeasonId added to LuckyWheelLog';
END
ELSE
BEGIN
    PRINT 'Column SeasonId already exists in LuckyWheelLog';
END
GO

PRINT 'All leaderboard tables created/updated successfully';
