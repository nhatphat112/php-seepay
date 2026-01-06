# PROMPT CHI TIẾT: PORT CHỨC NĂNG NẠP TÍCH LŨY TỪ NODEJS/C# SANG PHP

## 📋 TỔNG QUAN CHỨC NĂNG

Chức năng **Nạp Tích Lũy** (Accumulated Deposit) cho phép người chơi nhận phần thưởng khi đạt các mốc nạp tiền nhất định. Hệ thống theo dõi tổng số tiền người chơi đã nạp và tự động trao phần thưởng khi đạt mốc.

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

### 3. Bảng `TotalMoneyUser` (Tổng tiền đã nạp của user)
```sql
CREATE TABLE TotalMoneyUser (
    Id UNIQUEIDENTIFIER PRIMARY KEY,
    UserId UNIQUEIDENTIFIER NOT NULL,     -- ID người dùng
    TotalMoney BIGINT NOT NULL,           -- Số tiền nạp
    CreateDate DATETIME NOT NULL,        -- Ngày nạp
    CreatedDate DATETIME,
    CreatedId UNIQUEIDENTIFIER,
    IsDelete BIT DEFAULT 0
);
```

### 4. Bảng `Transaction` (Giao dịch thanh toán)
```sql
CREATE TABLE Transaction (
    Id UNIQUEIDENTIFIER PRIMARY KEY,
    TransactionDate DATETIME NOT NULL,
    AccountNumber NVARCHAR(50),
    SubAccount NVARCHAR(50),
    AmountIn DECIMAL(18,2),              -- Tiền vào
    AmountOut DECIMAL(18,2),              -- Tiền ra
    Accumulated DECIMAL(18,2),            -- Tổng tích lũy
    Code NVARCHAR(50),
    Content NVARCHAR(MAX),                -- Nội dung giao dịch
    ReferenceNumber NVARCHAR(100),
    Gateway NVARCHAR(50),                 -- VNPay, MoMo, ZaloPay
    TransferType NVARCHAR(10),            -- "in" hoặc "out"
    transferAmount BIGINT,
    CreatedDate DATETIME,
    IsDelete BIT DEFAULT 0
);
```

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

---

## 🔄 WORKFLOW CHI TIẾT

### **Bước 1: Người chơi nạp tiền**

1. **Frontend (React/TypeScript):**
   - User vào trang `/payment`
   - Chọn số tiền nạp và phương thức thanh toán (VNPay/MoMo/ZaloPay)
   - Tạo đơn hàng và redirect đến gateway thanh toán

2. **Payment Gateway Callback:**
   - Gateway gọi webhook: `POST /api/hooks/sepay-payment`
   - Dữ liệu gửi về:
   ```json
   {
     "TransactionDate": "2024-01-01T10:00:00",
     "AccountNumber": "1234567890",
     "AmountIn": 100000,
     "Accumulated": 500000,
     "Content": "DH{32-char-guid}",
     "Gateway": "VNPay",
     "TransferType": "in",
     "referenceCode": "REF123456"
   }
   ```

3. **Backend xử lý callback (C#):**
   - Lưu transaction vào bảng `Transaction`
   - Parse `Content` để lấy OrderId (regex: `DH([a-fA-F0-9]{32})`)
   - Cập nhật trạng thái đơn hàng: `PaymentStatus = Paid`
   - **Gọi `TotalMoneyUserService.TriggerCreate()`** để lưu tổng tiền nạp:
     ```csharp
     await _totalMoneyUserService.TriggerCreate((long)order.Total, order.CreatedId.Value);
     ```
   - Cộng Silk vào tài khoản game (nếu là nạp Silk)

### **Bước 2: Tính tổng tiền đã nạp**

**API Endpoint:** `GET /api/TotalMoneyUser/GetTotalMoney?UserId={guid}`

**Logic:**
```csharp
var totalMoney = _TotalMoneyUserService.GetQueryable()
    .Where(x => x.UserId == UserId)
    .Sum(t => t.TotalMoney);
```

**Response:**
```json
{
  "status": true,
  "data": 500000,
  "message": "Success"
}
```

### **Bước 3: Lấy danh sách mốc nạp**

**API Endpoint:** `GET /api/SilkTichNap/GetRank`

**Logic (C#):**
1. Query tất cả `SilkTichNap` từ database
2. Với mỗi mốc:
   - Parse `DsItem` (danh sách ID item, phân cách bằng dấu phẩy)
   - Query `GiftCodeItem` theo danh sách ID
   - Query `TaiLieuDinhKem` để lấy hình ảnh (LoaiTaiLieu = "IconVP")
   - Format giá tiền: `StringUtilities.formatVND(rank.Rank)`
3. Sắp xếp theo `Rank` tăng dần

**Response:**
```json
{
  "status": true,
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

**API Endpoint:** `GET /api/LogTichNap/GetStatusTichNap?userName={username}`

**Logic:**
```csharp
var query = from q in GetQueryable()
            .Where(x => x.CharName == username)
            select new LogTịchNapCreate
            {
                IdItem = q.IdTichNap,
                IsActive = true,
                MaxPrice = q.MaxPrice
            };
```

**Response:**
```json
{
  "status": true,
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

**API Endpoint:** `POST /api/SilkTichNap/AddItemForReach`

**Request:**
```json
{
  "itemTichNap": "guid-1",
  "charNames": "CharacterName",
  "userName": "username123"
}
```

**Logic xử lý (C#):**

1. **Kiểm tra và lấy thông tin mốc nạp:**
   ```csharp
   var giftCode = _silkTichNapRepository.GetQueryable()
       .FirstOrDefault(q => q.Id.Equals(model.ItemTichNap));
   if (giftCode == null) return error;
   ```

2. **Lấy danh sách item cần trao:**
   ```csharp
   var lstCode = _giftCodeItemRepository.GetQueryable()
       .Where(t => giftCode.DsItem.Contains(t.Id.ToString()))
       .Select(t => new GiftCodeItemDto
       {
           CodeItem = t.CodeItem,
           quanlity = t.quanlity
       }).ToList();
   ```

3. **Kiểm tra nhân vật tồn tại:**
   ```sql
   SELECT COUNT(*) FROM _Char WHERE CharName16 = @CharName
   ```

4. **Thêm item vào game (Stored Procedure):**
   ```sql
   EXEC [dbo].[_AddItemByName]
       @CharName = 'CharacterName',
       @CodeName = 'ITEM_CODE',
       @Amount = 10,
       @OptLevel = 0
   ```
   - Gọi procedure cho **từng item** trong danh sách

5. **Ghi log đã nhận:**
   ```csharp
   var logTichNap = new LogTichNap
   {
       CharName = model.UserName,
       IdTichNap = model.ItemTichNap,
       MaxPrice = totalMoneyThat?.Rank ?? 0,
       Status = true
   };
   await _logTichNapService.CreateAsync(logTichNap);
   ```

**Response:**
```json
{
  "status": true,
  "data": [
    {
      "codeItem": "ITEM_CODE_1",
      "quanlity": 10
    }
  ]
}
```

---

## 🎨 FRONTEND IMPLEMENTATION (React/TypeScript)

### Component: `PaymentMilestones.tsx`

**State Management:**
```typescript
const [totalPayment, setTotalPayment] = useState<number>(0)        // Tổng tiền đã nạp
const [claimedRows, setClaimedRows] = useState<Record<string, boolean>>({})  // Mốc đã nhận
const [milestoneData, setmilestoneData] = useState<milestoneDataType[]>([])  // Danh sách mốc
const [charNames, setCharName] = useState<string>("")              // Tên nhân vật
```

**Functions:**

1. **Lấy tổng tiền đã nạp:**
   ```typescript
   const handleGetPayMent = async () => {
     const user = userInfo?.id ?? "";
     const response = await totalMoneyUserService.GetTotalMoney(user);
     if (response.status) {
       setTotalPayment(response.data);
     }
   }
   ```

2. **Lấy danh sách mốc nạp:**
   ```typescript
   const handleGetmilestoneData = async () => {
     const data = await qlSilkTichNapService.GetRank();
     if (data.status) {
       setmilestoneData(data.data);
     }
   }
   ```

3. **Lấy log đã nhận:**
   ```typescript
   const handleLogTichNap = async () => {
     const user = userInfo?.userName ?? "";
     const response = await qlLogTicNapService.GetLogByUser(user);
     if (response.status) {
       const mapped: Record<string, boolean> = {};
       response.data.forEach((item: { idItem: string; isActive: boolean }) => {
         mapped[item.idItem] = item.isActive;
       });
       setClaimedRows(mapped);
     }
   }
   ```

4. **Nhận phần thưởng:**
   ```typescript
   const handleClaim = async (price: string, id: string) => {
     if (charNames == "") {
       toast.error("Yêu cầu nhập đúng tên nhân vật để nhận vật phẩm");
       return;
     }
     const res = await qlSilkTichNapService.AddItemForReach({
       itemTichNap: id,
       charNames: charNames,
       userName: userInfo?.userName ?? ""
     });
     if (res) {
       toast.success(`Đã nhận phần thưởng mốc nạp: ${price}`);
       handleLogTichNap(); // Refresh log
     }
   }
   ```

**UI Logic:**
- Hiển thị tổng tiền đã nạp
- Hiển thị progress đến mốc tiếp theo
- Danh sách các mốc nạp:
  - Mốc chưa đạt: Disabled, không cho nhận
  - Mốc đã đạt nhưng chưa nhận: Enabled, có nút "Nhận thưởng"
  - Mốc đã nhận: Disabled, hiển thị "Đã nhận"

---

## 📝 YÊU CẦU IMPLEMENTATION PHP

### **1. Database Tables**

Tạo các bảng tương tự trong SQL Server:
- `SilkTichNap`
- `LogTichNap`
- `TotalMoneyUser`
- `Transaction`
- `GiftCodeItem`
- `TaiLieuDinhKem`

### **2. API Endpoints cần implement**

#### **2.1. GET /api/TotalMoneyUser/GetTotalMoney**
```php
// Input: ?UserId={guid}
// Output: { "status": true, "data": 500000 }
// Logic: SUM(TotalMoney) WHERE UserId = {guid}
```

#### **2.2. GET /api/SilkTichNap/GetRank**
```php
// Output: Danh sách mốc nạp với items và hình ảnh
// Logic:
// 1. SELECT * FROM SilkTichNap ORDER BY Rank ASC
// 2. Với mỗi mốc:
//    - Parse DsItem (explode by comma)
//    - SELECT * FROM GiftCodeItem WHERE Id IN (...)
//    - SELECT * FROM TaiLieuDinhKem WHERE Item_ID IN (...) AND LoaiTaiLieu = 'IconVP'
// 3. Format price: number_format($rank, 0, ',', '.') . ' VND'
```

#### **2.3. GET /api/LogTichNap/GetStatusTichNap**
```php
// Input: ?userName={username}
// Output: Danh sách mốc đã nhận
// Logic: SELECT IdTichNap, MaxPrice FROM LogTichNap WHERE CharName = {username}
```

#### **2.4. POST /api/SilkTichNap/AddItemForReach**
```php
// Input: { "itemTichNap": "guid", "charNames": "CharName", "userName": "username" }
// Logic:
// 1. SELECT * FROM SilkTichNap WHERE Id = {itemTichNap}
// 2. Parse DsItem và SELECT GiftCodeItem
// 3. Kiểm tra nhân vật: SELECT COUNT(*) FROM _Char WHERE CharName16 = {charNames}
// 4. Với mỗi item, gọi stored procedure:
//    EXEC [dbo].[_AddItemByName] @CharName, @CodeName, @Amount, @OptLevel = 0
// 5. INSERT INTO LogTichNap (CharName, IdTichNap, MaxPrice, Status)
// Output: { "status": true, "data": [...] }
```

### **3. Payment Callback Integration**

Trong `payment_callback.php`, sau khi xử lý thanh toán thành công:

```php
// Sau khi cập nhật đơn hàng thành công
// Lưu vào TotalMoneyUser
$stmt = $conn->prepare("
    INSERT INTO TotalMoneyUser (Id, UserId, TotalMoney, CreateDate, CreatedDate)
    VALUES (NEWID(), ?, ?, GETDATE(), GETDATE())
");
$stmt->execute([$userId, $amount]);
```

### **4. Stored Procedure**

Đảm bảo stored procedure `[dbo].[_AddItemByName]` tồn tại:
```sql
CREATE PROCEDURE [dbo].[_AddItemByName]
    @CharName NVARCHAR(50),
    @CodeName NVARCHAR(50),
    @Amount INT,
    @OptLevel INT = 0
AS
BEGIN
    -- Logic thêm item vào game
    -- (Implementation tùy theo cấu trúc game)
END
```

### **5. Helper Functions**

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

### **6. Error Handling**

- Validate input: GUID format, username, charname
- Transaction rollback nếu có lỗi khi thêm item
- Log errors để debug
- Return error messages rõ ràng

### **7. Security Considerations**

- Validate user authentication
- Check user ownership (chỉ user đó mới nhận được)
- SQL injection prevention (use prepared statements)
- Validate character name exists
- Prevent duplicate claims (check LogTichNap trước khi thêm)

---

## 🔍 TESTING CHECKLIST

- [ ] Test tính tổng tiền đã nạp
- [ ] Test lấy danh sách mốc nạp
- [ ] Test kiểm tra mốc đã nhận
- [ ] Test nhận phần thưởng thành công
- [ ] Test nhận phần thưởng khi chưa đạt mốc (phải fail)
- [ ] Test nhận phần thưởng khi đã nhận rồi (phải fail)
- [ ] Test với nhân vật không tồn tại (phải fail)
- [ ] Test với nhiều item trong một mốc
- [ ] Test payment callback tạo TotalMoneyUser
- [ ] Test format VND đúng định dạng

---

## 📚 FILES THAM KHẢO

### Backend C#:
- `yousro_server/Hinet.Api/Controllers/SilkTichNapController.cs`
- `yousro_server/Hinet.Service/SilkTichNapService/SilkTichNapService.cs`
- `yousro_server/Hinet.Api/Controllers/TotalMoneyUserController.cs`
- `yousro_server/Hinet.Service/TotalMoneyUserService/TotalMoneyUserService.cs`
- `yousro_server/Hinet.Api/Controllers/LogTichNapController.cs`
- `yousro_server/Hinet.Api/Controllers/hooksController.cs` (payment callback)

### Frontend TypeScript:
- `your_sro/src/components/tichnap-components/payment-milestones.tsx`
- `your_sro/src/services/SilkTichNap/SilkTichNap.service.ts`
- `your_sro/src/services/TotalMoneyUser/TotalMoneyUser.service.ts`
- `your_sro/src/services/LogTichNap/LogTichNap.service.ts`

### PHP hiện tại:
- `Web/payment_callback.php`
- `Web/database.php`
- `Web/payment_manager.php`

---

## ✅ KẾT LUẬN

Chức năng nạp tích lũy bao gồm:
1. **Theo dõi tổng tiền nạp** qua bảng `TotalMoneyUser`
2. **Cấu hình mốc nạp** trong bảng `SilkTichNap`
3. **Trao phần thưởng** khi đạt mốc và ghi log vào `LogTichNap`
4. **Tích hợp với payment callback** để tự động cập nhật tổng tiền

Khi port sang PHP, cần đảm bảo:
- Tất cả API endpoints hoạt động tương tự
- Logic nghiệp vụ giống hệt (validation, error handling)
- Database structure tương thích
- Stored procedure `_AddItemByName` hoạt động đúng

