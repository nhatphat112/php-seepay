# Hướng Dẫn Kiểm Tra Vật Phẩm Đã Nhận Từ Nạp Tích Lũy

## 📋 Tổng Quan

Khi user claim reward từ tính năng Nạp Tích Lũy, hệ thống lưu thông tin ở **2 nơi**:

1. **`LogTichNap`** (Database: `SRO_VT_ACCOUNT`) - Lưu log đã nhận mốc nào
2. **`_InstantItemDelivery`** (Database: `SRO_VT_FILTER`) - Lưu items đã được thêm vào game

---

## 🔍 Cách Kiểm Tra

### 1. Kiểm Tra Trong LogTichNap (Đã Nhận Mốc Nào)

#### 1.1. Kiểm tra user cụ thể đã nhận mốc nào:

```sql
USE SRO_VT_ACCOUNT;
GO

DECLARE @Username NVARCHAR(50) = 'username_here'; -- Thay bằng username

SELECT 
    lt.CharName AS Username,
    lt.IdTichNap AS MilestoneId,
    lt.MaxPrice AS MilestoneAmount,
    lt.CreatedDate AS ClaimedDate,
    st.Rank AS MilestoneRank,
    st.Description AS MilestoneDescription,
    st.ItemsJson AS RewardItems
FROM LogTichNap lt
INNER JOIN SilkTichNap st ON lt.IdTichNap = st.Id
WHERE lt.CharName = @Username 
    AND lt.Status = 1 
    AND lt.IsDelete = 0
ORDER BY lt.CreatedDate DESC;
```

#### 1.2. Kiểm tra user đã nhận mốc cụ thể chưa:

```sql
USE SRO_VT_ACCOUNT;
GO

DECLARE @Username NVARCHAR(50) = 'username_here';
DECLARE @MilestoneId UNIQUEIDENTIFIER = 'milestone-guid-here';

SELECT 
    CASE 
        WHEN COUNT(*) > 0 THEN 'Đã nhận'
        ELSE 'Chưa nhận'
    END AS ClaimStatus,
    COUNT(*) AS ClaimCount,
    MAX(CreatedDate) AS LastClaimedDate
FROM LogTichNap
WHERE CharName = @Username 
    AND IdTichNap = @MilestoneId
    AND Status = 1 
    AND IsDelete = 0;
```

---

### 2. Kiểm Tra Trong _InstantItemDelivery (Items Đã Vào Game)

#### 2.1. Kiểm tra nhân vật đã nhận items nào:

```sql
USE SRO_VT_FILTER;
GO

DECLARE @CharName NVARCHAR(64) = 'CharacterName'; -- Tên nhân vật

-- Lấy CharID từ CharName
DECLARE @CharID INT;
SELECT TOP 1 @CharID = CharID
FROM SRO_VT_SHARD.dbo._Char
WHERE CharName16 = @CharName;

-- Kiểm tra items đã nhận
SELECT 
    iid.CodeName AS ItemCode,
    iid.Count AS Quantity,
    iid.CreatedDate AS ItemReceivedDate
FROM _InstantItemDelivery iid
WHERE iid.CharID = @CharID
ORDER BY iid.CreatedDate DESC;
```

#### 2.2. Kiểm tra nhân vật đã nhận item cụ thể chưa:

```sql
USE SRO_VT_FILTER;
GO

DECLARE @CharName NVARCHAR(64) = 'CharacterName';
DECLARE @ItemCode NVARCHAR(100) = 'ITEM_MALL_QUIVER';

DECLARE @CharID INT;
SELECT TOP 1 @CharID = CharID
FROM SRO_VT_SHARD.dbo._Char
WHERE CharName16 = @CharName;

SELECT 
    COUNT(*) AS ReceivedCount,
    SUM(Count) AS TotalQuantity,
    MIN(CreatedDate) AS FirstReceivedDate,
    MAX(CreatedDate) AS LastReceivedDate
FROM _InstantItemDelivery
WHERE CharID = @CharID AND CodeName = @ItemCode;
```

---

### 3. Kiểm Tra Tổng Hợp (Cả 2 Bảng)

#### 3.1. Xem user đã nhận mốc nào và items gì:

```sql
USE SRO_VT_ACCOUNT;
GO

DECLARE @Username NVARCHAR(50) = 'username_here';

-- Lấy danh sách mốc đã nhận và items
SELECT 
    'Milestone Claimed' AS Type,
    st.Rank AS Amount,
    st.Description,
    lt.CreatedDate AS ClaimedDate,
    JSON_VALUE(value, '$.codeItem') AS ItemCode,
    JSON_VALUE(value, '$.quantity') AS ItemQuantity
FROM LogTichNap lt
INNER JOIN SilkTichNap st ON lt.IdTichNap = st.Id
CROSS APPLY OPENJSON(st.ItemsJson)
WHERE lt.CharName = @Username 
    AND lt.Status = 1 
    AND lt.IsDelete = 0
ORDER BY ClaimedDate DESC;
```

---

### 4. So Sánh: Kiểm Tra Items Đã Vào Game Chưa

#### 4.1. Kiểm tra items từ mốc cụ thể đã vào game chưa:

```sql
-- Bước 1: Lấy thông tin mốc đã claim
USE SRO_VT_ACCOUNT;
GO

DECLARE @Username NVARCHAR(50) = 'username_here';
DECLARE @MilestoneId UNIQUEIDENTIFIER = 'milestone-guid-here';

SELECT 
    st.ItemsJson AS ExpectedItems,
    lt.CreatedDate AS ClaimedDate
FROM LogTichNap lt
INNER JOIN SilkTichNap st ON lt.IdTichNap = st.Id
WHERE lt.CharName = @Username 
    AND lt.IdTichNap = @MilestoneId
    AND lt.Status = 1 
    AND lt.IsDelete = 0;

-- Bước 2: Kiểm tra trong _InstantItemDelivery
USE SRO_VT_FILTER;
GO

DECLARE @CharName NVARCHAR(64) = 'CharacterName'; -- Tên nhân vật
DECLARE @ItemCode NVARCHAR(100) = 'ITEM_MALL_QUIVER'; -- Mã item từ ItemsJson

DECLARE @CharID INT;
SELECT TOP 1 @CharID = CharID
FROM SRO_VT_SHARD.dbo._Char
WHERE CharName16 = @CharName;

SELECT 
    @ItemCode AS ExpectedItem,
    ISNULL(SUM(Count), 0) AS ReceivedQuantity,
    CASE 
        WHEN ISNULL(SUM(Count), 0) > 0 THEN 'Đã nhận'
        ELSE 'Chưa nhận'
    END AS Status
FROM _InstantItemDelivery
WHERE CharID = @CharID AND CodeName = @ItemCode;
```

---

## 📊 Các Query Hữu Ích

### Query 1: Thống kê user đã nhận bao nhiêu mốc

```sql
USE SRO_VT_ACCOUNT;
GO

SELECT 
    CharName AS Username,
    COUNT(*) AS TotalClaimedMilestones,
    SUM(MaxPrice) AS TotalMilestoneAmount
FROM LogTichNap
WHERE Status = 1 AND IsDelete = 0
GROUP BY CharName
ORDER BY TotalClaimedMilestones DESC;
```

### Query 2: Xem items đã được thêm gần đây (24h)

```sql
USE SRO_VT_FILTER;
GO

SELECT TOP 100
    c.CharName16 AS CharacterName,
    iid.CodeName AS ItemCode,
    iid.Count AS Quantity,
    iid.CreatedDate AS DeliveryDate
FROM _InstantItemDelivery iid
INNER JOIN SRO_VT_SHARD.dbo._Char c ON iid.CharID = c.CharID
WHERE iid.CreatedDate >= DATEADD(DAY, -1, GETDATE())
ORDER BY iid.CreatedDate DESC;
```

### Query 3: Kiểm tra mốc nào được nhận nhiều nhất

```sql
USE SRO_VT_ACCOUNT;
GO

SELECT 
    st.Rank AS MilestoneAmount,
    st.Description,
    COUNT(*) AS ClaimCount,
    COUNT(DISTINCT lt.CharName) AS UniqueUsers
FROM LogTichNap lt
INNER JOIN SilkTichNap st ON lt.IdTichNap = st.Id
WHERE lt.Status = 1 AND lt.IsDelete = 0
GROUP BY st.Rank, st.Description
ORDER BY ClaimCount DESC;
```

---

## 🔧 Troubleshooting

### Vấn đề: User claim nhưng không thấy items trong game

**Kiểm tra theo thứ tự:**

1. **Kiểm tra LogTichNap có record không:**
```sql
SELECT * FROM LogTichNap 
WHERE CharName = 'username' 
AND IdTichNap = 'milestone-id'
AND Status = 1;
```

2. **Kiểm tra _InstantItemDelivery có items không:**
```sql
-- Lấy CharID
SELECT CharID FROM SRO_VT_SHARD.dbo._Char WHERE CharName16 = 'CharacterName';

-- Kiểm tra items
SELECT * FROM SRO_VT_FILTER.dbo._InstantItemDelivery 
WHERE CharID = @CharID 
AND CodeName = 'ITEM_MALL_QUIVER';
```

3. **Nếu LogTichNap có nhưng _InstantItemDelivery không có:**
   - Có thể lỗi khi insert vào _InstantItemDelivery
   - Kiểm tra error log
   - Có thể cần thêm lại items thủ công

### Vấn đề: Kiểm tra items đã vào game nhưng user không thấy

**Có thể do:**
- Items đã được thêm nhưng game chưa sync
- User cần relog hoặc restart game
- Kiểm tra StorageType (0 = inventory)

---

## 📝 Lưu Ý

1. **LogTichNap** lưu theo `CharName` (username), không phải tên nhân vật
2. **_InstantItemDelivery** lưu theo `CharID` (ID nhân vật), cần convert từ CharName
3. Một user có thể có nhiều nhân vật, cần kiểm tra đúng nhân vật
4. Items được thêm vào `StorageType = 0` (inventory)
5. Có thể có nhiều records trong _InstantItemDelivery cho cùng một item (nếu nhận nhiều lần)

---

## 🚀 Quick Check Script

File SQL script đầy đủ: `sql_scripts/check_tichnap_rewards.sql`

Chạy script:
```bash
# Sử dụng sqlcmd hoặc SQL Server Management Studio
sqlcmd -S server_name -d SRO_VT_ACCOUNT -i sql_scripts/check_tichnap_rewards.sql
```

---

**Tài liệu này được cập nhật lần cuối:** 2025-01-XX

