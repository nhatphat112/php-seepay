# API Nạp Tích Lũy (Accumulated Deposit)

## 📋 Tổng quan

Chức năng nạp tích lũy cho phép người chơi nhận phần thưởng khi đạt các mốc nạp tiền nhất định. Hệ thống theo dõi tổng số tiền người chơi đã nạp (từ bảng `TB_Order`) và tự động trao phần thưởng khi đạt mốc.

## 🗄️ Database Setup

Trước khi sử dụng, cần chạy SQL script để tạo các bảng:

```bash
# Chạy script SQL
sqlcmd -S server -d SRO_VT_ACCOUNT -i sql_scripts/create_tichnap_tables.sql
```

Hoặc chạy file SQL trực tiếp trong SQL Server Management Studio.

## 📡 API Endpoints

### 1. GET /api/tichnap/get_total_money.php

Lấy tổng tiền đã nạp của user hiện tại.

**Request:**
```
GET /api/tichnap/get_total_money.php
```

**Response:**
```json
{
  "success": true,
  "data": 500000,
  "message": "Success"
}
```

**Authentication:** Required (Session)

---

### 2. GET /api/tichnap/get_ranks.php

Lấy danh sách tất cả mốc nạp với thông tin phần thưởng.

**Request:**
```
GET /api/tichnap/get_ranks.php
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "guid-1",
      "price": "100.000 VND",
      "priceValue": 100000,
      "description": "Phần thưởng mốc 100k",
      "items": [
        {
          "key": "ITEM_CODE_1",
          "name": "Item Name x (10)",
          "image": "https://example.com/item1.png"
        }
      ]
    }
  ]
}
```

**Authentication:** Not required (Public)

---

### 3. GET /api/tichnap/get_claimed_status.php

Lấy danh sách mốc đã nhận của user hiện tại.

**Request:**
```
GET /api/tichnap/get_claimed_status.php?username={username}
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

**Authentication:** Required (Session)

---

### 4. POST /api/tichnap/claim_reward.php

Nhận phần thưởng mốc nạp.

**Request:**
```json
{
  "itemTichNap": "guid-1",
  "charNames": "CharacterName",
  "userJID": 12345
}
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

**Authentication:** Required (Session)

**Validation:**
- Kiểm tra user đã đạt mốc nạp chưa
- Kiểm tra đã nhận phần thưởng chưa
- Kiểm tra nhân vật tồn tại
- Kiểm tra user ownership

---

## 🔧 Helper Functions

File `includes/tichnap_helper.php` chứa các helper functions:

- `formatVND($amount)` - Format số tiền thành "100.000 VND"
- `getTotalMoneyFromOrders($userJID, $db)` - Tính tổng tiền từ TB_Order
- `checkCharacterExists($charName, $shardDb)` - Kiểm tra nhân vật tồn tại
- `addItemToCharacter($charName, $codeItem, $amount, $shardDb)` - Thêm item vào game
- `isValidGuid($guid)` - Validate GUID format
- `parseItemIds($dsItem)` - Parse danh sách item IDs

---

## 🔄 Payment Callback Integration

Khi đơn hàng được thanh toán thành công, hệ thống tự động:
1. Cập nhật `TB_Order.Status = 'completed'`
2. Lưu vào `TotalMoneyUser` (optional - để tối ưu performance)
3. Cộng Silk vào tài khoản

File: `includes/sepay_service.php` - Method `processPaymentCallback()`

---

## 📝 Cấu hình Mốc Nạp

Để thêm mốc nạp mới, insert vào bảng `SilkTichNap`:

```sql
INSERT INTO SilkTichNap (Id, Rank, DsItem, Description, CreatedDate)
VALUES (
    NEWID(),
    100000,  -- Mốc 100k VND
    'guid1,guid2,guid3',  -- Danh sách ID GiftCodeItem (phân cách bằng dấu phẩy)
    'Phần thưởng mốc 100k',
    GETDATE()
);
```

**Lưu ý:**
- `Rank`: Mốc tiền (VND)
- `DsItem`: Danh sách ID của `GiftCodeItem`, phân cách bằng dấu phẩy
- `IsDelete = 0`: Mốc đang active

---

## 🎁 Cấu hình Vật Phẩm Phần Thưởng

### 1. Thêm GiftCodeItem

```sql
INSERT INTO GiftCodeItem (Id, CodeItem, NameItem, quanlity, CreatedDate)
VALUES (
    NEWID(),
    'ITEM_CODE_1',  -- Mã item trong game
    'Tên Item',     -- Tên item
    10,             -- Số lượng
    GETDATE()
);
```

### 2. Thêm Hình Ảnh Item

```sql
INSERT INTO TaiLieuDinhKem (Id, Item_ID, LoaiTaiLieu, DuongDanFile, CreatedDate)
VALUES (
    NEWID(),
    'guid-of-giftcodeitem',  -- ID của GiftCodeItem
    'IconVP',                 -- Loại tài liệu (IconVP cho icon vật phẩm)
    'https://example.com/item.png',  -- Đường dẫn hình ảnh
    GETDATE()
);
```

---

## 🔒 Security

- Tất cả API đều kiểm tra authentication (session)
- Kiểm tra user ownership (chỉ user đó mới nhận được)
- SQL injection prevention (PDO prepared statements)
- Validate input (GUID format, username, charname)
- Prevent duplicate claims (check LogTichNap trước khi thêm)

---

## 🧪 Testing

### Test Case 1: Lấy tổng tiền đã nạp
```bash
curl -X GET "http://localhost/api/tichnap/get_total_money.php" \
  -H "Cookie: PHPSESSID=..."
```

### Test Case 2: Lấy danh sách mốc nạp
```bash
curl -X GET "http://localhost/api/tichnap/get_ranks.php"
```

### Test Case 3: Nhận phần thưởng
```bash
curl -X POST "http://localhost/api/tichnap/claim_reward.php" \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=..." \
  -d '{
    "itemTichNap": "guid-1",
    "charNames": "CharacterName",
    "userJID": 12345
  }'
```

---

## ⚠️ Lưu ý

1. **Stored Procedure:** Đảm bảo stored procedure `[dbo].[_AddItemByName]` tồn tại trong database SHARD
2. **Database Connection:** Sử dụng `ConnectionManager` để kết nối database
3. **Transaction:** Sử dụng transaction để đảm bảo tính nhất quán khi thêm item
4. **Error Handling:** Tất cả errors đều được log và trả về message rõ ràng

---

## 📚 Files

- `sql_scripts/create_tichnap_tables.sql` - SQL script tạo bảng
- `includes/tichnap_helper.php` - Helper functions
- `api/tichnap/get_total_money.php` - API lấy tổng tiền
- `api/tichnap/get_ranks.php` - API lấy danh sách mốc
- `api/tichnap/get_claimed_status.php` - API lấy mốc đã nhận
- `api/tichnap/claim_reward.php` - API nhận phần thưởng
- `includes/sepay_service.php` - Payment callback integration

