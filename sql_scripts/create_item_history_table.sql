-- ==========================================
-- Lucky Wheel Item History Table
-- Lưu lịch sử nhận vật phẩm từ vòng quay may mắn
-- ==========================================

-- Table: LuckyWheelItemHistory
-- Lưu lịch sử nhận vật phẩm: tên vật phẩm, số lượng, nguồn, thời gian nhận
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[LuckyWheelItemHistory]') AND type in (N'U'))
BEGIN
    CREATE TABLE LuckyWheelItemHistory (
        Id INT PRIMARY KEY IDENTITY(1,1),
        UserJID INT NOT NULL,
        Username NVARCHAR(50) NOT NULL,
        ItemName NVARCHAR(200) NOT NULL,
        ItemCode NVARCHAR(100) NOT NULL,
        Quantity INT NOT NULL,
        Source NVARCHAR(50) NOT NULL, -- 'lucky_wheel' hoặc 'accumulated_reward'
        CharName NVARCHAR(64) NULL, -- Tên nhân vật nhận vật phẩm
        RewardId INT NULL, -- ID từ bảng LuckyWheelRewards (nếu từ vòng quay thường)
        AccumulatedLogId INT NULL, -- ID từ bảng LuckyWheelAccumulatedLog (nếu từ tích lũy)
        ReceivedDate DATETIME DEFAULT GETDATE(),
        CONSTRAINT FK_ItemHistory_Reward FOREIGN KEY (RewardId) REFERENCES LuckyWheelRewards(Id) ON DELETE SET NULL,
        CONSTRAINT FK_ItemHistory_AccumulatedLog FOREIGN KEY (AccumulatedLogId) REFERENCES LuckyWheelAccumulatedLog(Id) ON DELETE SET NULL
    );
    
    CREATE INDEX IX_ItemHistory_UserJID ON LuckyWheelItemHistory(UserJID);
    CREATE INDEX IX_ItemHistory_Username ON LuckyWheelItemHistory(Username);
    CREATE INDEX IX_ItemHistory_Source ON LuckyWheelItemHistory(Source);
    CREATE INDEX IX_ItemHistory_ReceivedDate ON LuckyWheelItemHistory(ReceivedDate DESC);
    CREATE INDEX IX_ItemHistory_RewardId ON LuckyWheelItemHistory(RewardId);
    CREATE INDEX IX_ItemHistory_AccumulatedLogId ON LuckyWheelItemHistory(AccumulatedLogId);
    
    PRINT 'Table LuckyWheelItemHistory created successfully';
END
ELSE
BEGIN
    PRINT 'Table LuckyWheelItemHistory already exists';
END
GO
