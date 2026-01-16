# 🎰 Vòng Quay May Mắn - Prompt & Checklist

## 📋 Tổng Quan Tính Năng

Tính năng **Vòng Quay May Mắn** cho phép user quay vòng để nhận vật phẩm ngẫu nhiên. Admin có thể quản lý vật phẩm, tỉ lệ quay, và bật/tắt tính năng.

---

## ✅ BƯỚC 1: ADMIN & LOGIC XỬ LÝ, API (ĐÃ HOÀN THÀNH)

### 1.1 Database Migration ✅
- [x] **`sql_scripts/migrate_lucky_wheel.php`**
  - [x] Tạo bảng `LuckyWheelConfig` (cấu hình tính năng)
  - [x] Tạo bảng `LuckyWheelItems` (vật phẩm trong vòng quay)
  - [x] Tạo bảng `LuckyWheelLog` (log quay vòng)
  - [x] Tạo bảng `LuckyWheelRewards` (vật phẩm đã trúng, chờ nhận)
  - [x] Tạo indexes cho performance
  - [x] Idempotent (có thể chạy nhiều lần an toàn)

### 1.2 Helper Functions ✅
- [x] **`includes/lucky_wheel_helper.php`**
  - [x] `getLuckyWheelItems()` - Lấy danh sách vật phẩm (active/inactive)
  - [x] `getLuckyWheelConfig()` - Lấy cấu hình (enabled, spin cost)
  - [x] `calculateSpinResult()` - Tính kết quả quay (weighted random)
  - [x] `processSpin()` - Xử lý quay (trừ silk, tạo log/reward)
  - [x] `getUserPendingRewards()` - Lấy phần thưởng chờ nhận
  - [x] `getRecentRareWins()` - Lấy danh sách user trúng vật phẩm hiếm
  - [x] `claimLuckyWheelReward()` - Nhận phần thưởng (tích hợp tichnap workflow)

### 1.3 Admin APIs ✅
- [x] **`api/cms/lucky_wheel/get_items.php`**
  - [x] Lấy danh sách vật phẩm (có option include_inactive)
  - [x] Admin authentication required
  
- [x] **`api/cms/lucky_wheel/add_item.php`**
  - [x] Thêm vật phẩm mới
  - [x] Validation: tên, mã, số lượng, tỉ lệ
  - [x] Check duplicate item code
  
- [x] **`api/cms/lucky_wheel/update_item.php`**
  - [x] Cập nhật vật phẩm
  - [x] Validation đầy đủ
  - [x] Check duplicate item code (trừ chính nó)
  
- [x] **`api/cms/lucky_wheel/delete_item.php`**
  - [x] Xóa vật phẩm
  - [x] Soft delete nếu có reward pending
  - [x] Hard delete nếu không có reward pending
  
- [x] **`api/cms/lucky_wheel/toggle_feature.php`**
  - [x] Bật/tắt tính năng
  - [x] Cập nhật giá quay (spin cost)
  
- [x] **`api/cms/lucky_wheel/get_config.php`**
  - [x] Lấy cấu hình hiện tại

### 1.4 User APIs ✅
- [x] **`api/lucky_wheel/spin.php`**
  - [x] Quay vòng quay (1-20 lần)
  - [x] Validation spin count
  - [x] Check feature enabled
  - [x] Check silk balance
  - [x] Trừ silk và tạo log/reward
  
- [x] **`api/lucky_wheel/get_rewards.php`**
  - [x] Lấy danh sách phần thưởng (pending/claimed/all)
  - [x] Filter theo status
  
- [x] **`api/lucky_wheel/claim_reward.php`**
  - [x] Nhận phần thưởng
  - [x] Tích hợp với tichnap workflow (`addMultipleItemsToCharacter`)
  - [x] Auto-detect character nếu không có char_name
  - [x] Mark reward as claimed
  
- [x] **`api/lucky_wheel/get_items.php`**
  - [x] Lấy danh sách vật phẩm (public, chỉ active items)
  
- [x] **`api/lucky_wheel/get_recent_rare_wins.php`**
  - [x] Lấy danh sách user trúng vật phẩm hiếm (cho ticker trang chủ)
  - [x] Limit configurable (1-100)
  
- [x] **`api/lucky_wheel/get_config.php`**
  - [x] Lấy cấu hình (public)

### 1.5 Admin Page ✅
- [x] **`admin/lucky_wheel.php`**
  - [x] Giao diện quản lý vật phẩm
  - [x] Toggle bật/tắt tính năng
  - [x] Cập nhật giá quay
  - [x] Thêm/sửa/xóa vật phẩm
  - [x] Hiển thị danh sách vật phẩm với:
    - Tên, mã, số lượng
    - Vật phẩm hiếm (badge)
    - Tỉ lệ quay ra (%)
    - Thứ tự hiển thị
    - Trạng thái (active/inactive)
  - [x] Modal form thêm/sửa
  - [x] Validation client-side
  - [x] Alert messages

### 1.6 Menu Integration ✅
- [x] Cập nhật sidebar menu ở tất cả trang admin:
  - [x] `admin/cms/index.php`
  - [x] `admin/orders.php`
  - [x] `admin/slider.php`
  - [x] `admin/news.php`
  - [x] `admin/weekly_events.php`
  - [x] `admin/social.php`
  - [x] `admin/server_info.php`
  - [x] `admin/qrcode.php`
  - [x] `admin/users.php`
  - [x] `admin/tichnap/index.php`

---

## 🚧 BƯỚC 2: GIAO DIỆN SPIN CHO USER (CẦN LÀM)

### 2.1 Trang Vòng Quay May Mắn (User)
- [ ] **`lucky_wheel.php`** (hoặc `lucky_spin.php`)
  - [ ] Layout theo design `demo.png` và `demo-1.png`
  - [ ] Header: "VÒNG QUAY MAY MẮN"
  - [ ] Buttons: "QUAY 1 LẦN", "QUAY 20 LẦN" (có thể thêm 5, 10, 15)
  - [ ] Vòng quay trực quan:
    - [ ] Hiển thị các segment với vật phẩm
    - [ ] Animation xoay khi quay
    - [ ] Pointer ở trên cùng
    - [ ] Highlight segment đã trúng
  - [ ] Status message: "Đang quay lượt X/Y"
  - [ ] Danh sách vật phẩm đã trúng (bên trái):
    - [ ] Hiển thị danh sách real-time
    - [ ] Format: "Vật phẩm - Thời gian"
  - [ ] Top người chơi (bên phải):
    - [ ] Leaderboard người trúng vật phẩm hiếm
    - [ ] Format: "Rank - Username - Vật phẩm - Số lần"
  - [ ] Hiển thị giá quay (10 Silk/lần)
  - [ ] Check silk balance trước khi quay
  - [ ] Loading states
  - [ ] Error handling

### 2.2 Logic Quay Vòng
- [ ] **Quay 1 lần:**
  - [ ] Gọi API `api/lucky_wheel/spin.php` với `spin_count: 1`
  - [ ] Animation xoay vòng quay
  - [ ] Dừng ở segment đã trúng
  - [ ] Hiển thị kết quả (popup hoặc notification)
  - [ ] Cập nhật danh sách vật phẩm đã trúng
  - [ ] Cập nhật silk balance

- [ ] **Quay nhiều lần (5, 10, 15, 20):**
  - [ ] Gọi API `api/lucky_wheel/spin.php` với `spin_count: N`
  - [ ] Animation xoay tượng trưng (không cần dừng chính xác)
  - [ ] Hiển thị progress: "Đang quay lượt X/Y"
  - [ ] Sau khi quay xong, hiển thị popup "KẾT QUẢ":
    - [ ] Danh sách tất cả vật phẩm đã trúng
    - [ ] Scrollable list
    - [ ] Màu sắc theo loại vật phẩm
    - [ ] Button "ĐÓNG"
  - [ ] Cập nhật danh sách vật phẩm đã trúng
  - [ ] Cập nhật silk balance

### 2.3 Danh Sách Vật Phẩm Trong Vòng Quay
- [ ] Hiển thị danh sách vật phẩm có thể quay được
- [ ] Lấy từ API `api/lucky_wheel/get_items.php`
- [ ] Hiển thị:
  - [ ] Tên vật phẩm
  - [ ] Số lượng
  - [ ] Badge "Vật phẩm hiếm" nếu có
  - [ ] Tỉ lệ quay ra (%)

### 2.4 Danh Sách Vật Phẩm Đã Trúng
- [ ] Hiển thị danh sách vật phẩm đã trúng (chưa nhận)
- [ ] Lấy từ API `api/lucky_wheel/get_rewards.php?status=pending`
- [ ] Format: "Vật phẩm - Thời gian" (ví dụ: "iPad Mini - 00:20")
- [ ] Button "Nhận" cho mỗi vật phẩm
- [ ] Khi click "Nhận":
  - [ ] Gọi API `api/lucky_wheel/claim_reward.php`
  - [ ] Hiển thị loading
  - [ ] Success message
  - [ ] Remove khỏi danh sách pending
  - [ ] Error handling (nếu không có character)

### 2.5 Top Người Chơi
- [ ] Hiển thị leaderboard người trúng vật phẩm hiếm
- [ ] Lấy từ API `api/lucky_wheel/get_recent_rare_wins.php`
- [ ] Format:
  - [ ] Rank (1, 2, 3...)
  - [ ] Username
  - [ ] Vật phẩm đã trúng
  - [ ] Số lần (nếu có nhiều lần)
- [ ] Auto-refresh định kỳ

### 2.6 Ticker Trang Chủ
- [ ] **Trang chủ (`index.php` hoặc `home.php`):**
  - [ ] Dòng chữ động (marquee/ticker)
  - [ ] Hiển thị: "Username đã trúng [Vật phẩm hiếm]"
  - [ ] Lấy từ API `api/lucky_wheel/get_recent_rare_wins.php`
  - [ ] Sắp xếp: mới nhất đến cũ nhất
  - [ ] Chỉ hiển thị vật phẩm hiếm
  - [ ] Format: "Tan_Sat đã trúng iPhone 16 Pro"
  - [ ] Auto-scroll
  - [ ] Auto-refresh định kỳ (mỗi 30s-1 phút)

---

## 📝 CHI TIẾT KỸ THUẬT

### Database Schema
```sql
-- LuckyWheelConfig
FeatureEnabled BIT, SpinCost INT

-- LuckyWheelItems
Id INT, ItemName NVARCHAR(100), ItemCode NVARCHAR(50), 
Quantity INT, IsRare BIT, WinRate DECIMAL(5,2), 
DisplayOrder INT, IsActive BIT

-- LuckyWheelLog
Id INT, UserJID INT, ItemId INT, ItemName NVARCHAR(100),
ItemCode NVARCHAR(50), Quantity INT, IsRare BIT, SpinDate DATETIME

-- LuckyWheelRewards
Id INT, UserJID INT, LogId INT, ItemId INT, ItemName NVARCHAR(100),
ItemCode NVARCHAR(50), Quantity INT, IsRare BIT, 
Status VARCHAR(20), WonDate DATETIME, ClaimedDate DATETIME
```

### API Endpoints Summary

#### Admin APIs (require admin auth):
- `GET /api/cms/lucky_wheel/get_items.php?include_inactive=1`
- `POST /api/cms/lucky_wheel/add_item.php`
- `POST /api/cms/lucky_wheel/update_item.php`
- `POST /api/cms/lucky_wheel/delete_item.php`
- `POST /api/cms/lucky_wheel/toggle_feature.php`
- `GET /api/cms/lucky_wheel/get_config.php`

#### User/Public APIs:
- `POST /api/lucky_wheel/spin.php` (require login)
- `GET /api/lucky_wheel/get_rewards.php?status=pending` (require login)
- `POST /api/lucky_wheel/claim_reward.php` (require login)
- `GET /api/lucky_wheel/get_items.php` (public)
- `GET /api/lucky_wheel/get_recent_rare_wins.php?limit=20` (public)
- `GET /api/lucky_wheel/get_config.php` (public)

### Design References
- `demo.png` - Giao diện vòng quay chính
- `demo-1.png` - Popup kết quả quay nhiều lần

### Key Features
- ✅ Weighted random selection (dựa trên WinRate)
- ✅ Silk deduction khi quay
- ✅ Logging đầy đủ
- ✅ Reward system tích hợp với tichnap workflow
- ✅ Rare item tracking cho ticker
- ✅ Admin management đầy đủ

---

## 🎯 NEXT STEPS

1. **Tạo trang `lucky_wheel.php` cho user**
   - Implement UI theo design
   - Integrate với các APIs đã có
   - Animation vòng quay

2. **Tạo ticker trên trang chủ**
   - Marquee/ticker component
   - Auto-refresh từ API

3. **Testing**
   - Test quay 1 lần
   - Test quay nhiều lần
   - Test nhận phần thưởng
   - Test admin functions

4. **Polish**
   - Responsive design
   - Error handling
   - Loading states
   - Animations

---

## 📌 NOTES

- Giá quay mặc định: **10 Silk/lần**
- Tỉ lệ quay ra: **0.01% - 100%** (tổng tỉ lệ có thể > 100%, hệ thống sẽ normalize)
- Vật phẩm hiếm: Đánh dấu `IsRare = 1` để hiển thị trên ticker
- Workflow nhận vật phẩm: Sử dụng `_InstantItemDelivery` (giống tichnap)
- Character auto-detect: Nếu không có `char_name`, tự động lấy character đầu tiên của user

---

**Last Updated:** 2026-01-14
**Status:** Bước 1 ✅ Hoàn thành | Bước 2 🚧 Đang chờ
