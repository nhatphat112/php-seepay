# Tài Liệu Workflow: Tính Năng Nạp Tích Lũy (Accumulated Deposit)

## 📋 Mục Lục
1. [Tổng Quan](#tổng-quan)
2. [Kiến Trúc Hệ Thống](#kiến-trúc-hệ-thống)
3. [Database Schema](#database-schema)
4. [Workflow Chi Tiết](#workflow-chi-tiết)
5. [API Endpoints](#api-endpoints)
6. [Helper Functions](#helper-functions)
7. [Admin Interface](#admin-interface)

---

## 🎯 Tổng Quan

Tính năng **Nạp Tích Lũy** cho phép người chơi nhận phần thưởng khi đạt các mốc nạp tiền nhất định. Hệ thống tự động theo dõi tổng số tiền người chơi đã nạp và trao phần thưởng khi đạt mốc.

### Luồng Hoạt Động Chính:
1. **User nạp tiền** → Đơn hàng được tạo trong `TB_Order`
2. **Payment callback** → Cập nhật `TB_Order.Status = 'completed'`
3. **User xem mốc nạp** → Hệ thống tính tổng tiền từ `TB_Order`
4. **User claim reward** → Hệ thống thêm items vào game qua `_InstantItemDelivery`

---

## 🏗️ Kiến Trúc Hệ Thống

### Databases Sử Dụng:
- **SRO_VT_ACCOUNT**: Lưu trữ cấu hình, mốc nạp, logs
- **SRO_VT_SHARD**: Lưu thông tin nhân vật (`_Char`)
- **SRO_VT_FILTER**: Thêm items vào game (`_InstantItemDelivery`)

### Các Thành Phần Chính:
```
Frontend (User)
    ↓
API Layer (api/tichnap/)
    ↓
Helper Functions (includes/tichnap_helper.php)
    ↓
Database Layer (ConnectionManager)
    ↓
SQL Server Databases
```

---

## 🗄️ Database Schema

### 1. Bảng `TichNapConfig` (Cấu hình tính năng)
**Database:** `SRO_VT_ACCOUNT`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| `Id` | INT | Primary Key, Identity |
| `FeatureEnabled` | BIT | Bật/tắt tính năng (1 = bật, 0 = tắt) |
| `EventStartDate` | DATETIME | Ngày bắt đầu sự kiện (NULL = ngay lập tức) |
| `EventEndDate` | DATETIME | Ngày kết thúc sự kiện (NULL = không giới hạn) |
| `UpdatedDate` | DATETIME | Ngày cập nhật |
| `UpdatedBy` | INT | JID của admin cập nhật |

**Query Location:**
- `api/tichnap/get_config.php` - Lấy cấu hình
- `api/tichnap/update_config.php` - Cập nhật cấu hình

---

### 2. Bảng `SilkTichNap` (Mốc nạp tích lũy)
**Database:** `SRO_VT_ACCOUNT`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| `Id` | UNIQUEIDENTIFIER | Primary Key |
| `Rank` | INT | Mốc tiền (VND) - ví dụ: 100000 |
| `DsItem` | NVARCHAR(MAX) | Danh sách GUID items (cách cũ, tương thích ngược) |
| `ItemsJson` | NVARCHAR(MAX) | JSON chứa items (cách mới) |
| `Description` | NVARCHAR(MAX) | Mô tả mốc nạp |
| `IsActive` | BIT | Chỉ 1 mốc active tại một thời điểm |
| `CreatedDate` | DATETIME | Ngày tạo |
| `CreatedId` | UNIQUEIDENTIFIER | ID người tạo |
| `UpdatedDate` | DATETIME | Ngày cập nhật |
| `UpdatedId` | UNIQUEIDENTIFIER | ID người cập nhật |
| `IsDelete` | BIT | Đánh dấu xóa (soft delete) |

**Format ItemsJson (Cách mới):**
```json
[
  {
    "name": "Quiver",
    "codeItem": "ITEM_MALL_QUIVER",
    "quantity": 1
  },
  {
    "name": "Potion",
    "codeItem": "ITEM_MALL_POTION",
    "quantity": 10
  }
]
```

**Query Locations:**
- `api/tichnap/create_milestone.php` - Tạo mốc mới
- `api/tichnap/get_ranks.php` - Lấy danh sách mốc active
- `api/tichnap/get_all_milestones.php` - Lấy tất cả mốc (admin)
- `api/tichnap/claim_reward.php` - Lấy thông tin mốc khi claim
- `api/tichnap/activate_milestone.php` - Kích hoạt mốc
- `api/tichnap/delete_milestone.php` - Xóa mốc

---

### 3. Bảng `LogTichNap` (Lịch sử nhận thưởng)
**Database:** `SRO_VT_ACCOUNT`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| `Id` | UNIQUEIDENTIFIER | Primary Key |
| `CharName` | NVARCHAR(50) | Tên nhân vật (username) |
| `IdTichNap` | UNIQUEIDENTIFIER | ID mốc nạp đã nhận |
| `Status` | BIT | Trạng thái (1 = đã nhận) |
| `MaxPrice` | BIGINT | Mốc tiền tương ứng |
| `CreatedDate` | DATETIME | Ngày nhận |
| `CreatedId` | UNIQUEIDENTIFIER | ID người tạo |
| `IsDelete` | BIT | Đánh dấu xóa |

**Query Locations:**
- `api/tichnap/claim_reward.php` - Kiểm tra đã nhận chưa, ghi log khi nhận
- `api/tichnap/get_claimed_status.php` - Lấy danh sách mốc đã nhận

---

### 4. Bảng `TB_Order` (Đơn hàng thanh toán - ĐÃ TỒN TẠI)
**Database:** `SRO_VT_ACCOUNT`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| `JID` | INT | ID người chơi |
| `Amount` | BIGINT | Số tiền nạp (VND) |
| `Status` | NVARCHAR | Trạng thái: `pending`, `processing`, `completed`, `failed`, `expired` |

**Query Location:**
- `includes/tichnap_helper.php::getTotalMoneyFromOrders()` - Tính tổng tiền
- `api/tichnap/get_total_money.php` - API lấy tổng tiền
- `api/tichnap/claim_reward.php` - Kiểm tra tổng tiền khi claim

**Cập nhật từ:**
- `includes/sepay_service.php` - Payment callback từ Sepay gateway
- `payment_callback.php` - Webhook callback

---

### 5. Bảng `_Char` (Nhân vật - ĐÃ TỒN TẠI)
**Database:** `SRO_VT_SHARD`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| `CharID` | INT | ID nhân vật |
| `CharName16` | NVARCHAR(64) | Tên nhân vật |

**Query Location:**
- `includes/tichnap_helper.php::getCharIDFromName()` - Lấy CharID từ CharName
- `includes/tichnap_helper.php::checkCharacterExists()` - Kiểm tra nhân vật tồn tại
- `api/tichnap/claim_reward.php` - Kiểm tra nhân vật trước khi claim

---

### 6. Bảng `_InstantItemDelivery` (Thêm items vào game - ĐÃ TỒN TẠI)
**Database:** `SRO_VT_FILTER`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| `CharID` | INT | ID nhân vật |
| `StorageType` | INT | Loại storage (0 = inventory) |
| `CodeName` | NVARCHAR | Mã item (ví dụ: `ITEM_MALL_QUIVER`) |
| `Count` | INT | Số lượng |
| `Plus` | INT | Plus level (0) |
| `AddMagParams` | VARBINARY | NULL |
| `MagParams` | VARBINARY | NULL |
| `VarianceRand` | VARBINARY | NULL |

**Query Location:**
- `includes/tichnap_helper.php::addItemToCharacterViaInstantDelivery()` - Thêm 1 item
- `includes/tichnap_helper.php::addMultipleItemsToCharacter()` - Thêm nhiều items
- `api/tichnap/claim_reward.php` - Thêm items khi claim reward

---

### 7. Bảng `GiftCodeItem` (Vật phẩm phần thưởng - Optional)
**Database:** `SRO_VT_ACCOUNT`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| `Id` | UNIQUEIDENTIFIER | Primary Key |
| `CodeItem` | NVARCHAR(50) | Mã item trong game |
| `NameItem` | NVARCHAR(200) | Tên item |
| `quanlity` | INT | Số lượng mặc định |

**Query Location:**
- `api/tichnap/get_ranks.php` - Lấy hình ảnh items (fallback cho cách cũ)
- `api/tichnap/search_items.php` - Tìm kiếm items (admin)

---

### 8. Bảng `TaiLieuDinhKem` (Hình ảnh item - Optional)
**Database:** `SRO_VT_ACCOUNT`

| Cột | Kiểu | Mô Tả |
|-----|------|-------|
| `Item_ID` | UNIQUEIDENTIFIER | ID GiftCodeItem |
| `LoaiTaiLieu` | NVARCHAR(50) | Loại tài liệu (`IconVP`) |
| `DuongDanFile` | NVARCHAR(500) | Đường dẫn hình ảnh |

**Query Location:**
- `api/tichnap/get_ranks.php` - Lấy hình ảnh để hiển thị

---

## 🔄 Workflow Chi Tiết

### 1. Workflow: User Nạp Tiền và Tính Tổng Tiền

```
User Nạp Tiền
    ↓
Payment Gateway (Sepay)
    ↓
payment_callback.php / includes/sepay_service.php
    ↓
UPDATE TB_Order SET Status = 'completed' WHERE OrderCode = ?
    ↓
User Xem Tổng Tiền
    ↓
GET /api/tichnap/get_total_money.php
    ↓
includes/tichnap_helper.php::getTotalMoneyFromOrders()
    ↓
SELECT SUM(Amount) FROM TB_Order 
WHERE JID = ? AND Status = 'completed'
    ↓
Return: Tổng tiền đã nạp (VND)
```

**Files liên quan:**
- `api/tichnap/get_total_money.php` - API endpoint
- `includes/tichnap_helper.php::getTotalMoneyFromOrders()` - Helper function
- `includes/sepay_service.php` - Payment callback handler
- `payment_callback.php` - Webhook callback

**Tables queried:**
- `TB_Order` (SELECT SUM(Amount))

---

### 2. Workflow: Admin Tạo Mốc Nạp

```
Admin Interface (admin/tichnap/index.php)
    ↓
Form nhập: Mốc tiền, Mô tả, Items (tên, CodeItem, số lượng)
    ↓
POST /api/tichnap/create_milestone.php
    ↓
Validation: rank > 0, items không rỗng
    ↓
Kiểm tra mốc đã tồn tại chưa
SELECT COUNT(*) FROM SilkTichNap WHERE Rank = ? AND IsDelete = 0
    ↓
Disable tất cả mốc khác
UPDATE SilkTichNap SET IsActive = 0 WHERE IsDelete = 0 AND IsActive = 1
    ↓
Tạo mốc mới với IsActive = 1
INSERT INTO SilkTichNap (Id, Rank, ItemsJson, Description, IsActive, ...)
VALUES (NEWID(), ?, ?, ?, 1, ...)
    ↓
Return: Success
```

**Files liên quan:**
- `admin/tichnap/index.php` - Admin UI
- `api/tichnap/create_milestone.php` - API endpoint

**Tables queried:**
- `SilkTichNap` (SELECT, UPDATE, INSERT)

---

### 3. Workflow: User Xem Danh Sách Mốc Nạp

```
User Mở Trang Nạp Tích Lũy
    ↓
GET /api/tichnap/get_ranks.php
    ↓
SELECT Id, Rank, DsItem, ItemsJson, Description
FROM SilkTichNap
WHERE IsDelete = 0 AND IsActive = 1
ORDER BY Rank ASC
    ↓
Với mỗi mốc:
    - Đọc ItemsJson (cách mới) hoặc DsItem (cách cũ)
    - Nếu có ItemsJson: Parse JSON
    - Nếu có DsItem: Query GiftCodeItem để lấy thông tin
    - Query TaiLieuDinhKem để lấy hình ảnh
    ↓
Return: Danh sách mốc với items
```

**Files liên quan:**
- `api/tichnap/get_ranks.php` - API endpoint
- `includes/tichnap_helper.php::parseItemIds()` - Parse DsItem

**Tables queried:**
- `SilkTichNap` (SELECT)
- `GiftCodeItem` (SELECT - fallback)
- `TaiLieuDinhKem` (SELECT - lấy hình ảnh)

---

### 4. Workflow: User Kiểm Tra Mốc Đã Nhận

```
User Xem Trạng Thái Claimed
    ↓
GET /api/tichnap/get_claimed_status.php?username={username}
    ↓
SELECT IdTichNap, MaxPrice
FROM LogTichNap
WHERE CharName = ? AND Status = 1 AND IsDelete = 0
    ↓
Return: Danh sách mốc đã nhận
```

**Files liên quan:**
- `api/tichnap/get_claimed_status.php` - API endpoint

**Tables queried:**
- `LogTichNap` (SELECT)

---

### 5. Workflow: User Claim Reward (QUAN TRỌNG NHẤT)

```
User Click "Nhận Thưởng"
    ↓
POST /api/tichnap/claim_reward.php
Body: {
    "itemTichNap": "guid-milestone-id",
    "charNames": "CharacterName",
    "userJID": 12345
}
    ↓
[Validation]
- Kiểm tra user đã login
- Validate GUID format
- Kiểm tra user ownership
    ↓
[Step 0] Kiểm tra tính năng có bật không
SELECT TOP 1 FeatureEnabled FROM TichNapConfig ORDER BY UpdatedDate DESC
    ↓
[Step 1] Lấy thông tin mốc nạp
SELECT Id, Rank, DsItem, ItemsJson, Description
FROM SilkTichNap
WHERE Id = ? AND IsDelete = 0 AND IsActive = 1
    ↓
[Step 2] Tính tổng tiền đã nạp
includes/tichnap_helper.php::getTotalMoneyFromOrders()
SELECT SUM(Amount) FROM TB_Order WHERE JID = ? AND Status = 'completed'
    ↓
[Step 3] Kiểm tra đã đạt mốc chưa
if (totalMoney < milestone['Rank']) → Error
    ↓
[Step 4] Kiểm tra đã nhận chưa
SELECT COUNT(*) FROM LogTichNap
WHERE CharName = ? AND IdTichNap = ? AND Status = 1 AND IsDelete = 0
if (count > 0) → Error: Đã nhận rồi
    ↓
[Step 5] Kiểm tra nhân vật tồn tại
includes/tichnap_helper.php::checkCharacterExists()
SELECT COUNT(*) FROM _Char WHERE CharName16 = ?
    ↓
[Step 6] Lấy danh sách items cần trao
- Ưu tiên: Parse ItemsJson (cách mới)
- Fallback: Query GiftCodeItem từ DsItem (cách cũ)
    ↓
[Step 7] Bắt đầu Transaction
BEGIN TRANSACTION
    ↓
[Step 8] Thêm items vào game
includes/tichnap_helper.php::addMultipleItemsToCharacter()
    ├─ Lấy CharID từ CharName
    │  SELECT TOP 1 CharID FROM _Char WHERE CharName16 = ?
    │
    └─ Insert items vào _InstantItemDelivery
       INSERT INTO [SRO_VT_FILTER].[dbo].[_InstantItemDelivery]
       (CharID, StorageType, CodeName, Count, Plus, ...)
       VALUES (?, 0, ?, ?, 0, NULL, NULL, NULL)
       (Lặp cho mỗi item)
    ↓
[Step 9] Ghi log đã nhận
INSERT INTO LogTichNap (Id, CharName, IdTichNap, MaxPrice, Status, CreatedDate)
VALUES (NEWID(), ?, ?, ?, 1, GETDATE())
    ↓
[Step 10] Commit Transaction
COMMIT TRANSACTION
    ↓
Return: Success với danh sách items đã thêm
```

**Files liên quan:**
- `api/tichnap/claim_reward.php` - API endpoint chính
- `includes/tichnap_helper.php::getTotalMoneyFromOrders()` - Tính tổng tiền
- `includes/tichnap_helper.php::checkCharacterExists()` - Kiểm tra nhân vật
- `includes/tichnap_helper.php::getCharIDFromName()` - Lấy CharID
- `includes/tichnap_helper.php::addMultipleItemsToCharacter()` - Thêm items

**Tables queried:**
- `TichNapConfig` (SELECT)
- `SilkTichNap` (SELECT)
- `TB_Order` (SELECT SUM)
- `LogTichNap` (SELECT, INSERT)
- `_Char` (SELECT) - Database: SRO_VT_SHARD
- `_InstantItemDelivery` (INSERT) - Database: SRO_VT_FILTER
- `GiftCodeItem` (SELECT - fallback)

---

### 6. Workflow: Admin Quản Lý Cấu Hình

```
Admin Mở Tab "Cấu Hình"
    ↓
GET /api/tichnap/get_config.php
    ↓
SELECT TOP 1 FeatureEnabled, EventStartDate, EventEndDate
FROM TichNapConfig
ORDER BY UpdatedDate DESC
    ↓
Hiển thị form với giá trị hiện tại
    ↓
Admin Thay Đổi và Lưu
    ↓
POST /api/tichnap/update_config.php
Body: {
    "featureEnabled": true,
    "eventStartDate": "2025-01-10T00:00:00",
    "eventEndDate": "2025-01-20T23:59:59"
}
    ↓
UPDATE TichNapConfig
SET FeatureEnabled = ?, EventStartDate = ?, EventEndDate = ?, ...
WHERE Id = (SELECT TOP 1 Id FROM TichNapConfig ORDER BY UpdatedDate DESC)
    ↓
Nếu không có record: INSERT INTO TichNapConfig (...)
    ↓
Return: Success
```

**Files liên quan:**
- `admin/tichnap/index.php` - Admin UI
- `api/tichnap/get_config.php` - Lấy cấu hình
- `api/tichnap/update_config.php` - Cập nhật cấu hình

**Tables queried:**
- `TichNapConfig` (SELECT, UPDATE, INSERT)

---

## 📡 API Endpoints

### User APIs

| Endpoint | Method | Mô Tả | Tables Queried |
|----------|--------|-------|----------------|
| `/api/tichnap/get_total_money.php` | GET | Lấy tổng tiền đã nạp | `TB_Order` |
| `/api/tichnap/get_ranks.php` | GET | Lấy danh sách mốc nạp active | `SilkTichNap`, `GiftCodeItem`, `TaiLieuDinhKem` |
| `/api/tichnap/get_claimed_status.php` | GET | Lấy danh sách mốc đã nhận | `LogTichNap` |
| `/api/tichnap/claim_reward.php` | POST | Nhận phần thưởng mốc nạp | `TichNapConfig`, `SilkTichNap`, `TB_Order`, `LogTichNap`, `_Char`, `_InstantItemDelivery` |

### Admin APIs

| Endpoint | Method | Mô Tả | Tables Queried |
|----------|--------|-------|----------------|
| `/api/tichnap/get_config.php` | GET | Lấy cấu hình tính năng | `TichNapConfig` |
| `/api/tichnap/update_config.php` | POST | Cập nhật cấu hình | `TichNapConfig` |
| `/api/tichnap/create_milestone.php` | POST | Tạo mốc nạp mới | `SilkTichNap` |
| `/api/tichnap/get_all_milestones.php` | GET | Lấy tất cả mốc (bao gồm inactive) | `SilkTichNap`, `GiftCodeItem`, `TaiLieuDinhKem` |
| `/api/tichnap/activate_milestone.php` | POST | Kích hoạt mốc | `SilkTichNap` |
| `/api/tichnap/delete_milestone.php` | POST | Xóa mốc (soft delete) | `SilkTichNap` |
| `/api/tichnap/search_items.php` | GET | Tìm kiếm items (admin) | `GiftCodeItem`, `TaiLieuDinhKem` |

---

## 🔧 Helper Functions

### File: `includes/tichnap_helper.php`

| Function | Mô Tả | Tables/Databases Queried |
|----------|-------|--------------------------|
| `formatVND($amount)` | Format số tiền thành "100.000 VND" | - |
| `parseGuid($guidString)` | Parse GUID format | - |
| `getTotalMoneyFromOrders($userJID, $db)` | Tính tổng tiền từ TB_Order | `TB_Order` (SELECT SUM) |
| `getTotalMoneyFromTotalMoneyUser($userJID, $db)` | Tính tổng tiền từ TotalMoneyUser | `TotalMoneyUser` (SELECT SUM) |
| `checkCharacterExists($charName, $shardDb)` | Kiểm tra nhân vật tồn tại | `_Char` (SELECT COUNT) - SRO_VT_SHARD |
| `getCharIDFromName($charName, $shardDb)` | Lấy CharID từ CharName | `_Char` (SELECT) - SRO_VT_SHARD |
| `addItemToCharacterViaInstantDelivery(...)` | Thêm 1 item qua _InstantItemDelivery | `_Char` (SELECT), `_InstantItemDelivery` (INSERT) - SRO_VT_FILTER |
| `addMultipleItemsToCharacter(...)` | Thêm nhiều items cùng lúc | `_Char` (SELECT), `_InstantItemDelivery` (INSERT) - SRO_VT_FILTER |
| `addItemToCharacter(...)` | Thêm item qua stored procedure (cách cũ) | Stored procedure `_AddItemByName` |
| `isValidGuid($guid)` | Validate GUID format | - |
| `parseItemIds($dsItem)` | Parse danh sách item IDs từ string | - |

---

## 🎨 Admin Interface

### File: `admin/tichnap/index.php`

**Tabs:**
1. **Danh Sách Mốc**: Hiển thị tất cả mốc nạp, có thể kích hoạt/xóa
2. **Tạo Mốc Mới**: Form nhập mốc tiền, mô tả, và items (tên, CodeItem, số lượng)
3. **Cấu Hình**: Bật/tắt tính năng, thiết lập thời gian sự kiện

**Workflow Admin:**
```
Admin Login
    ↓
admin/tichnap/index.php
    ↓
Tab "Danh Sách Mốc"
    GET /api/tichnap/get_all_milestones.php
    ↓
Tab "Tạo Mốc Mới"
    Form nhập → POST /api/tichnap/create_milestone.php
    ↓
Tab "Cấu Hình"
    GET /api/tichnap/get_config.php
    Form cập nhật → POST /api/tichnap/update_config.php
```

---

## 🔐 Security & Validation

### Authentication & Authorization:
- **User APIs**: Yêu cầu `$_SESSION['user_id']` và `$_SESSION['username']`
- **Admin APIs**: Yêu cầu `isAdmin()` check
- **User Ownership**: Kiểm tra `userJID == $_SESSION['user_id']`

### Validation:
- **GUID Format**: Validate bằng `isValidGuid()`
- **Input Validation**: Kiểm tra required fields, data types
- **Business Logic**: 
  - Kiểm tra tính năng có bật không
  - Kiểm tra đã đạt mốc chưa
  - Kiểm tra đã nhận chưa
  - Kiểm tra nhân vật tồn tại

### Transaction Safety:
- Sử dụng `BEGIN TRANSACTION` / `COMMIT` / `ROLLBACK`
- Đảm bảo tính nhất quán khi thêm items và ghi log

---

## 📊 Data Flow Diagram

```
┌─────────────┐
│ User Nạp Tiền│
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ Payment Gateway │
└──────┬──────────┘
       │
       ▼
┌──────────────────┐      ┌──────────────┐
│ TB_Order.Status  │─────▶│ Calculate    │
│ = 'completed'    │      │ Total Money  │
└──────────────────┘      └──────┬───────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │ Check Milestones│
                         └──────┬──────────┘
                                │
                                ▼
                         ┌─────────────────┐
                         │ User Claim      │
                         │ Reward          │
                         └──────┬──────────┘
                                │
                                ▼
                    ┌───────────────────────┐
                    │ Get CharID from       │
                    │ SRO_VT_SHARD._Char    │
                    └──────┬────────────────┘
                           │
                           ▼
                    ┌───────────────────────┐
                    │ Insert Items into     │
                    │ SRO_VT_FILTER.       │
                    │ _InstantItemDelivery  │
                    └──────┬────────────────┘
                           │
                           ▼
                    ┌───────────────────────┐
                    │ Log to LogTichNap     │
                    └───────────────────────┘
```

---

## 🚀 Migration & Setup

### 1. Chạy Migration Script:
```bash
php sql_scripts/migrate_tichnap.php
```

Script này sẽ:
- Tạo các bảng: `SilkTichNap`, `LogTichNap`, `TotalMoneyUser`, `GiftCodeItem`, `TaiLieuDinhKem`, `TichNapConfig`
- Thêm các cột mới nếu bảng đã tồn tại: `IsActive`, `ItemsJson`, `EventStartDate`, `EventEndDate`
- Tạo indexes để tối ưu query
- **KHÔNG XÓA** bất kỳ dữ liệu nào

### 2. Cấu Hình Ban Đầu:
- Vào Admin → Tab "Cấu Hình"
- Bật tính năng: `FeatureEnabled = true`
- Thiết lập thời gian sự kiện (tùy chọn)

### 3. Tạo Mốc Nạp:
- Vào Admin → Tab "Tạo Mốc Mới"
- Nhập mốc tiền, mô tả, và items

---

## 📝 Notes

### Tương Thích Ngược:
- Hệ thống hỗ trợ cả `ItemsJson` (cách mới) và `DsItem` (cách cũ)
- Ưu tiên đọc từ `ItemsJson`, fallback về `DsItem` nếu không có

### Performance:
- Sử dụng indexes trên `LogTichNap.CharName`, `LogTichNap.IdTichNap`
- Sử dụng indexes trên `TotalMoneyUser.UserJID`
- Tính tổng tiền trực tiếp từ `TB_Order` (không cần bảng cache)

### Error Handling:
- Tất cả APIs đều có try-catch và trả về error messages rõ ràng
- Transaction rollback khi có lỗi
- Log errors vào error_log

---

## 📞 Support & Maintenance

### Debugging:
- Check `error_log` để xem lỗi chi tiết
- Kiểm tra `TichNapConfig.FeatureEnabled` nếu tính năng không hoạt động
- Kiểm tra `SilkTichNap.IsActive` để đảm bảo có mốc active

### Common Issues:
1. **User không thấy mốc nạp**: Kiểm tra `IsActive = 1` và `IsDelete = 0`
2. **Không claim được**: Kiểm tra tổng tiền từ `TB_Order`, đã nhận chưa từ `LogTichNap`
3. **Items không vào game**: Kiểm tra `_InstantItemDelivery` trong `SRO_VT_FILTER`, kiểm tra CharID đúng chưa

---

**Tài liệu này được cập nhật lần cuối:** 2025-01-XX
**Version:** 1.0

