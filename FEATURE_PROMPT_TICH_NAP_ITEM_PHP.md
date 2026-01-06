# PROMPT CHI TIẾT: CHỨC NĂNG NẠP TÍCH LŨY CHO PHP-SEEPAY

## 📋 TỔNG QUAN CHỨC NĂNG

Chức năng **Nạp Tích Lũy** (Accumulated Deposit) cho phép người chơi nhận phần thưởng khi đạt các mốc nạp tiền nhất định. Hệ thống theo dõi tổng số tiền người chơi đã nạp (từ bảng `TB_Order`) và tự động trao phần thưởng khi đạt mốc.

---

## 🗄️ CẤU TRÚC DATABASE

### 1. Bảng `SilkTichNap` (Cấu hình mốc nạp)
```sql
CREATE TABLE SilkTichNap (
    Id UNIQUEIDENTIFIER PRIMARY KEY,
    Rank INT NOT NULL,                    -- Mốc tiền (VND)
    DsItem NVARCHAR(MAX),                 -- Danh sách ID item (phân cách bằng dấu phẩy)
    Description NVARCHAR(MAX),            -- Mô tả
    CreatedDate DATETIME,
    CreatedId UNIQUEIDENTIFIER,
    UpdatedDate DATETIME,
    UpdatedId UNIQUEIDENTIFIER,
    IsDelete BIT DEFAULT 0
);
```

**Ví dụ dữ liệu:**
- Rank: 100000 (100k VND)
- DsItem: "guid1,guid2,guid3" (các ID của GiftCodeItem)
- Description: "Phần thưởng mốc 100k"

### 2. Bảng `LogTichNap` (Lịch sử nhận thưởng)
```sql
CREATE TABLE LogTichNap (
    Id UNIQUEIDENTIFIER PRIMARY KEY,
    CharName NVARCHAR(50) NOT NULL,       -- Tên nhân vật
    IdTichNap UNIQUEIDENTIFIER NOT NULL,  -- ID mốc nạp đã nhận
    Status BIT DEFAULT 1,                 -- Trạng thái (đã nhận)
    MaxPrice BIGINT,                      -- Mốc tiền tương ứng
    CreatedDate DATETIME,
    CreatedId UNIQUEIDENTIFIER,
    IsDelete BIT DEFAULT 0
);
```

### 3. Bảng `TotalMoneyUser` (Tổng tiền đã nạp của user - Optional)
```sql
CREATE TABLE TotalMoneyUser (
    Id UNIQUEIDENTIFIER PRIMARY KEY,
    UserJID INT NOT NULL,                 -- JID từ TB_User (INT, không phải UNIQUEIDENTIFIER)
    TotalMoney BIGINT NOT NULL,           -- Số tiền nạp
    CreateDate DATETIME NOT NULL,         -- Ngày nạp
    CreatedDate DATETIME,
    CreatedId UNIQUEIDENTIFIER,
    IsDelete BIT DEFAULT 0
);
```

**Lưu ý:** Có thể tính tổng tiền trực tiếp từ `TB_Order` mà không cần bảng này, nhưng bảng này giúp tối ưu performance và lưu lịch sử.

### 4. Bảng `TB_Order` (Đơn hàng thanh toán - ĐÃ TỒN TẠI)
```sql
-- Bảng này đã tồn tại trong hệ thống
-- Các cột quan trọng:
-- OrderID (INT PRIMARY KEY)
-- OrderCode (NVARCHAR) - Mã đơn hàng
-- JID (INT) - ID người dùng (FK đến TB_User.JID)
-- Amount (DECIMAL) - Số tiền
-- SilkAmount (INT) - Số Silk
-- Status (NVARCHAR) - Trạng thái: pending, processing, completed, failed, expired
-- PaymentMethod (NVARCHAR) - Phương thức thanh toán
-- CreatedDate (DATETIME) - Ngày tạo
-- CompletedDate (DATETIME) - Ngày hoàn thành
```

**Logic tính tổng tiền nạp:**
- Chỉ tính các đơn hàng có `Status = 'completed'`
- SUM(Amount) WHERE JID = {userJID} AND Status = 'completed'

### 5. Bảng `GiftCodeItem` (Vật phẩm phần thưởng)
```sql
CREATE TABLE GiftCodeItem (
    Id UNIQUEIDENTIFIER PRIMARY KEY,
    CodeItem NVARCHAR(50) NOT NULL,      -- Mã item trong game
    NameItem NVARCHAR(200),               -- Tên item
    quanlity INT DEFAULT 1,               -- Số lượng
    CreatedDate DATETIME,
    IsDelete BIT DEFAULT 0
);
```

### 6. Bảng `TaiLieuDinhKem` (Hình ảnh item)
```sql
CREATE TABLE TaiLieuDinhKem (
    Id UNIQUEIDENTIFIER PRIMARY KEY,
    Item_ID UNIQUEIDENTIFIER,             -- ID GiftCodeItem
    LoaiTaiLieu NVARCHAR(50),             -- "IconVP"
    DuongDanFile NVARCHAR(500),           -- Đường dẫn hình ảnh
    NgayPhatHanh DATETIME,
    CreatedDate DATETIME
);
```

### 7. Bảng `TB_User` (ĐÃ TỒN TẠI)
```sql
-- Bảng này đã tồn tại
-- JID (INT PRIMARY KEY) - ID người dùng
-- StrUserID (NVARCHAR) - Username
-- Email (NVARCHAR)
-- password (NVARCHAR) - MD5 hash
-- role (VARCHAR) - admin/user
```

---

## 🔄 WORKFLOW CHI TIẾT

### **Bước 1: Người chơi nạp tiền**

1. **Frontend:**
   - User vào trang `/payment.php`
   - Chọn số tiền nạp và phương thức thanh toán (VNPay/MoMo/ZaloPay)
   - Tạo đơn hàng trong `TB_Order` với `Status = 'pending'`
   - Redirect đến gateway thanh toán

2. **Payment Gateway Callback:**
   - Gateway gọi webhook: `POST /api/hooks/sepay-payment` hoặc tương tự
   - Dữ liệu gửi về chứa `OrderCode` hoặc `referenceCode`

3. **Backend xử lý callback (PHP):**
   - File: `includes/sepay_service.php` - Method `processPaymentCallback()`
   - Lấy đơn hàng từ `TB_Order` theo `OrderCode`
   - Cập nhật trạng thái: `Status = 'completed'`
   - Cộng Silk vào tài khoản game (SK_Silk)
   - **Tạo bản ghi trong `TotalMoneyUser`** (nếu dùng bảng này):
     ```php
     $stmt = $db->prepare("
         INSERT INTO TotalMoneyUser (Id, UserJID, TotalMoney, CreateDate, CreatedDate)
         VALUES (NEWID(), ?, ?, GETDATE(), GETDATE())
     ");
     $stmt->execute([$order['JID'], $order['Amount']]);
     ```

### **Bước 2: Tính tổng tiền đã nạp**

**API Endpoint:** `GET /api/tichnap/get_total_money.php?userJID={int}`

**Logic (2 cách):**

**Cách 1: Tính trực tiếp từ TB_Order (Khuyến nghị):**
```php
$stmt = $db->prepare("
    SELECT SUM(Amount) as total 
    FROM TB_Order 
    WHERE JID = ? AND Status = 'completed'
");
$stmt->execute([$userJID]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalMoney = (int)($result['total'] ?? 0);
```

**Cách 2: Tính từ TotalMoneyUser (Nếu dùng bảng này):**
```php
$stmt = $db->prepare("
    SELECT SUM(TotalMoney) as total 
    FROM TotalMoneyUser 
    WHERE UserJID = ? AND IsDelete = 0
");
$stmt->execute([$userJID]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalMoney = (int)($result['total'] ?? 0);
```

**Response:**
```json
{
  "success": true,
  "data": 500000,
  "message": "Success"
}
```

### **Bước 3: Lấy danh sách mốc nạp**

**API Endpoint:** `GET /api/tichnap/get_ranks.php`

**Logic:**
1. Query tất cả `SilkTichNap` từ database (WHERE IsDelete = 0)
2. Với mỗi mốc:
   - Parse `DsItem` (danh sách ID item, phân cách bằng dấu phẩy)
   - Query `GiftCodeItem` theo danh sách ID
   - Query `TaiLieuDinhKem` để lấy hình ảnh (LoaiTaiLieu = "IconVP")
   - Format giá tiền: `number_format($rank, 0, ',', '.') . ' VND'`
3. Sắp xếp theo `Rank` tăng dần

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "guid-1",
      "price": "100.000 VND",
      "priceValue": 100000,
      "items": [
        {
          "key": "ITEM_CODE_1",
          "name": "Item Name x (10)",
          "image": "https://example.com/item1.png"
        }
      ]
    },
    {
      "id": "guid-2",
      "price": "500.000 VND",
      "priceValue": 500000,
      "items": [...]
    }
  ]
}
```

### **Bước 4: Kiểm tra mốc đã nhận**

**API Endpoint:** `GET /api/tichnap/get_claimed_status.php?username={username}`

**Logic:**
```php
$stmt = $db->prepare("
    SELECT IdTichNap, MaxPrice 
    FROM LogTichNap 
    WHERE CharName = ? AND Status = 1 AND IsDelete = 0
");
$stmt->execute([$username]);
$claimed = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = array_map(function($item) {
    return [
        'idItem' => $item['IdTichNap'],
        'isActive' => true,
        'maxPrice' => (int)$item['MaxPrice']
    ];
}, $claimed);
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "idItem": "guid-1",
      "isActive": true,
      "maxPrice": 100000
    }
  ]
}
```

### **Bước 5: Người chơi nhận phần thưởng**

**API Endpoint:** `POST /api/tichnap/claim_reward.php`

**Request:**
```json
{
  "itemTichNap": "guid-1",
  "charNames": "CharacterName",
  "userJID": 12345
}
```

**Logic xử lý (PHP):**

1. **Kiểm tra authentication:**
   ```php
   session_start();
   if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $userJID) {
       return error('Unauthorized');
   }
   ```

2. **Kiểm tra và lấy thông tin mốc nạp:**
   ```php
   $stmt = $db->prepare("
       SELECT * FROM SilkTichNap 
       WHERE Id = ? AND IsDelete = 0
   ");
   $stmt->execute([$itemTichNap]);
   $milestone = $stmt->fetch(PDO::FETCH_ASSOC);
   
   if (!$milestone) {
       return error('Milestone not found');
   }
   ```

3. **Kiểm tra tổng tiền đã nạp:**
   ```php
   // Tính tổng tiền từ TB_Order
   $stmt = $db->prepare("
       SELECT SUM(Amount) as total 
       FROM TB_Order 
       WHERE JID = ? AND Status = 'completed'
   ");
   $stmt->execute([$userJID]);
   $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
   $totalMoney = (int)($totalResult['total'] ?? 0);
   
   // Kiểm tra đã đạt mốc chưa
   if ($totalMoney < $milestone['Rank']) {
       return error('Chưa đạt mốc nạp này');
   }
   ```

4. **Kiểm tra đã nhận chưa:**
   ```php
   $stmt = $db->prepare("
       SELECT COUNT(*) as count 
       FROM LogTichNap 
       WHERE CharName = ? AND IdTichNap = ? AND Status = 1 AND IsDelete = 0
   ");
   $stmt->execute([$username, $itemTichNap]);
   $claimed = $stmt->fetch(PDO::FETCH_ASSOC);
   
   if ($claimed['count'] > 0) {
       return error('Đã nhận phần thưởng này rồi');
   }
   ```

5. **Lấy danh sách item cần trao:**
   ```php
   $itemIds = explode(',', $milestone['DsItem']);
   $itemIds = array_map('trim', $itemIds);
   $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
   
   $stmt = $db->prepare("
       SELECT CodeItem, quanlity, NameItem 
       FROM GiftCodeItem 
       WHERE Id IN ($placeholders) AND IsDelete = 0
   ");
   $stmt->execute($itemIds);
   $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
   ```

6. **Kiểm tra nhân vật tồn tại:**
   ```php
   $shardDb = ConnectionManager::getShardDB();
   $stmt = $shardDb->prepare("
       SELECT COUNT(*) as count 
       FROM _Char 
       WHERE CharName16 = ?
   ");
   $stmt->execute([$charNames]);
   $charResult = $stmt->fetch(PDO::FETCH_ASSOC);
   
   if ($charResult['count'] == 0) {
       return error('Nhân vật không tồn tại');
   }
   ```

7. **Thêm item vào game (Stored Procedure):**
   ```php
   $shardDb = ConnectionManager::getShardDB();
   
   foreach ($items as $item) {
       $stmt = $shardDb->prepare("
           EXEC [dbo].[_AddItemByName]
               @CharName = ?,
               @CodeName = ?,
               @Amount = ?,
               @OptLevel = 0
       ");
       $stmt->execute([
           $charNames,
           $item['CodeItem'],
           $item['quanlity']
       ]);
   }
   ```

8. **Ghi log đã nhận:**
   ```php
   $stmt = $db->prepare("
       INSERT INTO LogTichNap (
           Id, CharName, IdTichNap, MaxPrice, Status, CreatedDate
       ) VALUES (
           NEWID(), ?, ?, ?, 1, GETDATE()
       )
   ");
   $stmt->execute([
       $username,
       $itemTichNap,
       $milestone['Rank']
   ]);
   ```

**Response:**
```json
{
  "success": true,
  "message": "Đã nhận phần thưởng thành công",
  "data": [
    {
      "codeItem": "ITEM_CODE_1",
      "quanlity": 10
    }
  ]
}
```

---

## 📝 YÊU CẦU IMPLEMENTATION PHP

### **1. Database Tables**

Tạo các bảng mới trong SQL Server (database `SRO_VT_ACCOUNT`):
- `SilkTichNap`
- `LogTichNap`
- `TotalMoneyUser` (Optional - có thể tính trực tiếp từ TB_Order)
- `GiftCodeItem`
- `TaiLieuDinhKem`

**Lưu ý:** 
- `TB_Order` và `TB_User` đã tồn tại
- Sử dụng `ConnectionManager` để kết nối database
- `UserJID` là INT (không phải UNIQUEIDENTIFIER)

### **2. API Endpoints cần implement**

#### **2.1. GET /api/tichnap/get_total_money.php**
```php
// Input: ?userJID={int} (từ session hoặc query param)
// Output: { "success": true, "data": 500000 }
// Logic: 
//   SELECT SUM(Amount) FROM TB_Order 
//   WHERE JID = ? AND Status = 'completed'
// Security: Kiểm tra session user_id == userJID
```

#### **2.2. GET /api/tichnap/get_ranks.php**
```php
// Output: Danh sách mốc nạp với items và hình ảnh
// Logic:
// 1. SELECT * FROM SilkTichNap WHERE IsDelete = 0 ORDER BY Rank ASC
// 2. Với mỗi mốc:
//    - Parse DsItem (explode by comma)
//    - SELECT * FROM GiftCodeItem WHERE Id IN (...) AND IsDelete = 0
//    - SELECT * FROM TaiLieuDinhKem WHERE Item_ID IN (...) AND LoaiTaiLieu = 'IconVP'
// 3. Format price: number_format($rank, 0, ',', '.') . ' VND'
```

#### **2.3. GET /api/tichnap/get_claimed_status.php**
```php
// Input: ?username={username} (từ session)
// Output: Danh sách mốc đã nhận
// Logic: 
//   SELECT IdTichNap, MaxPrice 
//   FROM LogTichNap 
//   WHERE CharName = ? AND Status = 1 AND IsDelete = 0
// Security: Kiểm tra session username == username
```

#### **2.4. POST /api/tichnap/claim_reward.php**
```php
// Input: { "itemTichNap": "guid", "charNames": "CharName", "userJID": 123 }
// Logic:
// 1. Kiểm tra authentication (session)
// 2. SELECT * FROM SilkTichNap WHERE Id = ? AND IsDelete = 0
// 3. Tính tổng tiền: SELECT SUM(Amount) FROM TB_Order WHERE JID = ? AND Status = 'completed'
// 4. Kiểm tra đã đạt mốc: totalMoney >= milestone.Rank
// 5. Kiểm tra đã nhận: SELECT COUNT(*) FROM LogTichNap WHERE CharName = ? AND IdTichNap = ?
// 6. Parse DsItem và SELECT GiftCodeItem
// 7. Kiểm tra nhân vật: SELECT COUNT(*) FROM _Char WHERE CharName16 = ? (từ ShardDB)
// 8. Với mỗi item, gọi stored procedure:
//    EXEC [dbo].[_AddItemByName] @CharName, @CodeName, @Amount, @OptLevel = 0
// 9. INSERT INTO LogTichNap
// Output: { "success": true, "message": "...", "data": [...] }
```

### **3. Payment Callback Integration**

Trong `includes/sepay_service.php`, method `processPaymentCallback()`, sau khi cập nhật đơn hàng thành công:

```php
// Sau khi cập nhật đơn hàng thành công
// Status = 'completed'

// Option 1: Lưu vào TotalMoneyUser (nếu dùng bảng này)
$stmt = $db->prepare("
    INSERT INTO TotalMoneyUser (Id, UserJID, TotalMoney, CreateDate, CreatedDate)
    VALUES (NEWID(), ?, ?, GETDATE(), GETDATE())
");
$stmt->execute([$order['JID'], $order['Amount']]);

// Option 2: Không cần lưu, tính trực tiếp từ TB_Order khi cần
// (Khuyến nghị - đơn giản hơn)
```

### **4. Stored Procedure**

Đảm bảo stored procedure `[dbo].[_AddItemByName]` tồn tại trong database SHARD:
```sql
CREATE PROCEDURE [dbo].[_AddItemByName]
    @CharName NVARCHAR(50),
    @CodeName NVARCHAR(50),
    @Amount INT,
    @OptLevel INT = 0
AS
BEGIN
    -- Logic thêm item vào game
    -- (Implementation tùy theo cấu trúc game Silkroad)
END
```

### **5. Helper Functions**

Tạo file `includes/tichnap_helper.php`:

#### **Format VND:**
```php
function formatVND($amount) {
    return number_format($amount, 0, ',', '.') . ' VND';
}
```

#### **Parse GUID từ string:**
```php
function parseGuid($guidString) {
    // Remove dashes và format lại
    $guid = str_replace('-', '', $guidString);
    if (strlen($guid) == 32) {
        return substr($guid, 0, 8) . '-' . 
               substr($guid, 8, 4) . '-' . 
               substr($guid, 12, 4) . '-' . 
               substr($guid, 16, 4) . '-' . 
               substr($guid, 20, 12);
    }
    return $guidString;
}
```

#### **Get Total Money từ TB_Order:**
```php
function getTotalMoneyFromOrders($userJID, $db) {
    $stmt = $db->prepare("
        SELECT SUM(Amount) as total 
        FROM TB_Order 
        WHERE JID = ? AND Status = 'completed'
    ");
    $stmt->execute([$userJID]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($result['total'] ?? 0);
}
```

### **6. Error Handling**

- Validate input: GUID format, username, charname, userJID
- Transaction rollback nếu có lỗi khi thêm item
- Log errors để debug
- Return error messages rõ ràng bằng tiếng Việt
- Sử dụng try-catch cho tất cả database operations

### **7. Security Considerations**

- Validate user authentication (session)
- Check user ownership (chỉ user đó mới nhận được - kiểm tra session user_id)
- SQL injection prevention (use prepared statements - PDO)
- Validate character name exists (kiểm tra trong ShardDB)
- Prevent duplicate claims (check LogTichNap trước khi thêm)
- Validate milestone exists và chưa bị xóa (IsDelete = 0)
- Validate đã đạt mốc trước khi cho nhận

### **8. File Structure**

```
/api/tichnap/
    ├── get_total_money.php
    ├── get_ranks.php
    ├── get_claimed_status.php
    └── claim_reward.php

/includes/
    └── tichnap_helper.php

/sql_scripts/
    └── create_tichnap_tables.sql
```

---

## 🔍 TESTING CHECKLIST

- [ ] Test tính tổng tiền đã nạp từ TB_Order
- [ ] Test lấy danh sách mốc nạp
- [ ] Test kiểm tra mốc đã nhận
- [ ] Test nhận phần thưởng thành công
- [ ] Test nhận phần thưởng khi chưa đạt mốc (phải fail)
- [ ] Test nhận phần thưởng khi đã nhận rồi (phải fail)
- [ ] Test với nhân vật không tồn tại (phải fail)
- [ ] Test với user khác cố gắng nhận thưởng của user khác (phải fail)
- [ ] Test với nhiều item trong một mốc
- [ ] Test payment callback tạo TotalMoneyUser (nếu dùng)
- [ ] Test format VND đúng định dạng
- [ ] Test với mốc nạp bị xóa (IsDelete = 1) - không hiển thị

---

## 📚 FILES THAM KHẢO

### PHP hiện tại:
- `includes/sepay_service.php` - Payment callback handler
- `connection_manager.php` - Database connection manager
- `api/sepay/get_user_orders.php` - API lấy đơn hàng user
- `dashboard.php` - User dashboard
- `admin/cms/orders.php` - Admin orders management

### Database:
- `TB_Order` - Bảng đơn hàng (đã tồn tại)
- `TB_User` - Bảng user (đã tồn tại)
- `SK_Silk` - Bảng Silk (đã tồn tại)
- `_Char` - Bảng nhân vật trong ShardDB (đã tồn tại)

---

## ✅ KẾT LUẬN

Chức năng nạp tích lũy bao gồm:
1. **Theo dõi tổng tiền nạp** từ bảng `TB_Order` (Status = 'completed')
2. **Cấu hình mốc nạp** trong bảng `SilkTichNap`
3. **Trao phần thưởng** khi đạt mốc và ghi log vào `LogTichNap`
4. **Tích hợp với payment callback** để tự động theo dõi tổng tiền

**Điểm khác biệt so với hệ thống C#:**
- Sử dụng `TB_Order` thay vì bảng `Transaction`
- Sử dụng `JID` (INT) thay vì `UserId` (UNIQUEIDENTIFIER)
- Tính tổng tiền trực tiếp từ `TB_Order` (có thể không cần `TotalMoneyUser`)
- Sử dụng `ConnectionManager` để quản lý kết nối database
- Sử dụng PDO với prepared statements

Khi implement, cần đảm bảo:
- Tất cả API endpoints hoạt động tương tự
- Logic nghiệp vụ giống hệt (validation, error handling)
- Database structure tương thích
- Stored procedure `_AddItemByName` hoạt động đúng
- Security: Kiểm tra authentication và authorization đầy đủ

