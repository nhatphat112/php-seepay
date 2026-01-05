# Feature Prompt: Tích Nạp Nhận Item

## 📋 Tổng Quan

Tính năng **Tích Nạp Nhận Item** cho phép user nhận vật phẩm game dựa trên tổng số tiền nạp tích lũy. Admin có thể quản lý các mốc tích lũy và phần thưởng, user có thể xem tiến độ và nhận phần thưởng.

---

## 🎯 Yêu Cầu Chức Năng

### **ADMIN (CMS Panel)**

#### 1. Quản Lý Tổng Quan
- **Trang quản lý**: `/admin/accumulation.php` (thêm vào CMS menu)
- **Bật/Tắt tính năng**: Toggle switch để enable/disable feature
- **Quản lý thời gian sự kiện**:
  - Start Date/Time (ngày giờ bắt đầu)
  - End Date/Time (ngày giờ kết thúc)
  - Có thể chỉnh sửa và cập nhật

#### 2. Quản Lý Tích Lũy User
- **Reset tích lũy**: Cho phép reset tích lũy của 1 user hoặc tất cả user
- **Edit tích lũy**: Cho phép admin chỉnh sửa số tiền tích lũy của user (thủ công)
- **Xem danh sách user tích lũy**: 
  - Hiển thị top user tích lũy
  - Tìm kiếm user theo username
  - Xem chi tiết tích lũy và phần thưởng đã nhận

#### 3. Quản Lý Mốc Phần Thưởng
- **Tạo mốc tích lũy**: 
  - Số tiền tích lũy (VND)
  - Danh sách vật phẩm (ItemID, số lượng)
  - Thứ tự hiển thị (Display Order)
  - Trạng thái (Active/Inactive)
- **Edit mốc**: Chỉnh sửa thông tin mốc tích lũy
- **Xóa mốc**: Xóa mốc tích lũy (có confirm)
- **Sắp xếp**: Drag & drop hoặc input để sắp xếp thứ tự mốc

---

### **USER (Dashboard)**

#### 1. Hiển Thị Tích Lũy
- **Section mới**: Thêm section "Tích Lũy Nạp" vào dashboard
- **Thông tin hiển thị**:
  - Tổng số tiền đã tích lũy (VND)
  - Thời gian sự kiện còn lại (countdown timer)
  - Progress bar tổng quan
  - Danh sách các mốc phần thưởng

#### 2. Trạng Thái Phần Thưởng
- **Chưa đạt**: Hiển thị mốc cần đạt, số tiền còn thiếu
- **Đã đạt, chưa nhận**: Hiển thị nút "Nhận Phần Thưởng" (màu vàng/xanh)
- **Đã nhận**: Hiển thị badge "Đã Nhận" (màu xám), không có nút

#### 3. Xử Lý Khi Feature Tắt
- Nếu feature đang tắt → Hiển thị message: "Hiện không có sự kiện tích lũy"
- Ẩn tất cả thông tin tích lũy và phần thưởng

---

## 🗄️ Database Schema

### 1. Bảng TB_AccumulationConfig (Cấu hình)

```sql
CREATE TABLE [dbo].[TB_AccumulationConfig](
    [ConfigID] [int] IDENTITY(1,1) NOT NULL,
    [IsEnabled] [bit] NOT NULL DEFAULT 0,
    [StartDate] [datetime] NULL,
    [EndDate] [datetime] NULL,
    [CreatedDate] [datetime] NOT NULL DEFAULT GETDATE(),
    [UpdatedDate] [datetime] NULL,
    CONSTRAINT [PK_TB_AccumulationConfig] PRIMARY KEY ([ConfigID])
)
```

### 2. Bảng TB_AccumulationMilestone (Mốc phần thưởng)

```sql
CREATE TABLE [dbo].[TB_AccumulationMilestone](
    [MilestoneID] [int] IDENTITY(1,1) NOT NULL,
    [Amount] [decimal](18, 2) NOT NULL, -- Số tiền tích lũy (VND)
    [DisplayOrder] [int] NOT NULL DEFAULT 0,
    [IsActive] [bit] NOT NULL DEFAULT 1,
    [CreatedDate] [datetime] NOT NULL DEFAULT GETDATE(),
    [UpdatedDate] [datetime] NULL,
    CONSTRAINT [PK_TB_AccumulationMilestone] PRIMARY KEY ([MilestoneID])
)
```

### 3. Bảng TB_AccumulationMilestoneItems (Vật phẩm của mốc)

```sql
CREATE TABLE [dbo].[TB_AccumulationMilestoneItems](
    [ItemID] [int] IDENTITY(1,1) NOT NULL,
    [MilestoneID] [int] NOT NULL,
    [ItemCode] [int] NOT NULL, -- ItemCode trong game
    [Quantity] [int] NOT NULL DEFAULT 1,
    [CreatedDate] [datetime] NOT NULL DEFAULT GETDATE(),
    CONSTRAINT [PK_TB_AccumulationMilestoneItems] PRIMARY KEY ([ItemID]),
    CONSTRAINT [FK_MilestoneItems_Milestone] FOREIGN KEY ([MilestoneID])
        REFERENCES [dbo].[TB_AccumulationMilestone] ([MilestoneID]) ON DELETE CASCADE
)
```

### 4. Thêm cột vào TB_User

```sql
ALTER TABLE [dbo].[TB_User]
ADD [AccumulationAmount] [decimal](18, 2) NOT NULL DEFAULT 0
```

### 5. Bảng TB_AccumulationRewards (Lịch sử nhận phần thưởng)

```sql
CREATE TABLE [dbo].[TB_AccumulationRewards](
    [RewardID] [bigint] IDENTITY(1,1) NOT NULL,
    [JID] [int] NOT NULL,
    [MilestoneID] [int] NOT NULL,
    [Amount] [decimal](18, 2) NOT NULL, -- Số tiền tích lũy tại thời điểm nhận
    [ReceivedDate] [datetime] NOT NULL DEFAULT GETDATE(),
    CONSTRAINT [PK_TB_AccumulationRewards] PRIMARY KEY ([RewardID]),
    CONSTRAINT [FK_Rewards_User] FOREIGN KEY ([JID])
        REFERENCES [dbo].[TB_User] ([JID]) ON DELETE CASCADE,
    CONSTRAINT [FK_Rewards_Milestone] FOREIGN KEY ([MilestoneID])
        REFERENCES [dbo].[TB_AccumulationMilestone] ([MilestoneID])
)

CREATE UNIQUE INDEX [IX_Rewards_User_Milestone] ON [dbo].[TB_AccumulationRewards]
    ([JID], [MilestoneID])
```

---

## 🔧 Technical Implementation

### 1. Function: Cộng Item vào Inventory

**File**: `includes/game_item_handler.php`

```php
<?php
/**
 * Game Item Handler
 * Xử lý cộng vật phẩm vào inventory của user
 * 
 * TODO: Cần nghiên cứu cách Silkroad Online lưu trữ item trong database
 * Tham khảo từ dev game để implement function này
 */

/**
 * Cộng item vào inventory của user
 * 
 * @param int $itemCode ItemCode trong game (tham khảo từ dev game)
 * @param int $userId User JID
 * @param int $quantity Số lượng item
 * @return array ['success' => bool, 'message' => string, 'data' => array]
 */
function addItemToUser($itemCode, $userId, $quantity = 1) {
    // TODO: Implement logic cộng item vào database
    // 
    // Cần nghiên cứu:
    // 1. Bảng nào lưu trữ item? (có thể là _Item, _Inventory, _CharItem trong DB SHARD)
    // 2. Cấu trúc bảng item như thế nào? (ItemID, ItemCode, ItemSerial, CharID, etc.)
    // 3. Cần thêm item vào character nào? (character đầu tiên? character active? hay account warehouse?)
    // 4. Cách generate ItemSerial nếu cần
    // 5. Cách handle item stackable vs non-stackable
    // 
    // Ví dụ structure có thể:
    // - _Item table trong SRO_VT_SHARD database
    // - Columns: ItemID, CharID, ItemCode, ItemSerial, Quantity, etc.
    // 
    // Return structure:
    return [
        'success' => false,
        'message' => 'Function chưa được implement - TODO',
        'data' => []
    ];
}
```

**Research Notes:**
- Cần tham khảo từ dev game về cấu trúc database item
- Thông thường Silkroad sử dụng bảng `_Item` trong database SHARD
- Item có thể lưu theo Character (CharID) hoặc Account (JID)
- Cần xác định cách xử lý item stackable và non-stackable

---

### 2. Function: Handle Tích Lũy Khi Cộng Silk

**File**: `includes/accumulation_handler.php`

```php
<?php
/**
 * Accumulation Handler
 * Xử lý tích lũy nạp và phần thưởng
 */

require_once __DIR__ . '/../connection_manager.php';
require_once __DIR__ . '/game_item_handler.php';

/**
 * Xử lý tích lũy khi user nạp tiền
 * Gọi function này TRƯỚC và SAU khi cộng silk
 * 
 * @param int $userId User JID
 * @param decimal $amount Số tiền nạp (VND)
 * @param string $when 'before' hoặc 'after' - gọi trước hay sau khi cộng silk
 * @return array ['success' => bool, 'milestones_reached' => array]
 */
function handleAccumulation($userId, $amount, $when = 'after') {
    try {
        // 1. Kiểm tra feature có đang bật không
        $config = getAccumulationConfig();
        if (!$config['IsEnabled']) {
            return ['success' => true, 'milestones_reached' => []];
        }
        
        // 2. Kiểm tra thời gian sự kiện
        $now = new DateTime();
        $startDate = new DateTime($config['StartDate']);
        $endDate = new DateTime($config['EndDate']);
        
        if ($now < $startDate || $now > $endDate) {
            return ['success' => true, 'milestones_reached' => []];
        }
        
        // 3. Lấy tích lũy hiện tại của user
        $db = ConnectionManager::getAccountDB();
        $stmt = $db->prepare("SELECT AccumulationAmount FROM TB_User WHERE JID = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        $currentAccumulation = floatval($user['AccumulationAmount'] ?? 0);
        
        // 4. Tính tích lũy mới
        if ($when === 'before') {
            $newAccumulation = $currentAccumulation; // Tích lũy trước khi cộng tiền
        } else {
            $newAccumulation = $currentAccumulation + $amount; // Tích lũy sau khi cộng tiền
        }
        
        // 5. Cập nhật tích lũy mới vào database
        $stmt = $db->prepare("UPDATE TB_User SET AccumulationAmount = ? WHERE JID = ?");
        $stmt->execute([$newAccumulation, $userId]);
        
        // 6. Lấy tất cả mốc phần thưởng
        $stmt = $db->prepare("
            SELECT MilestoneID, Amount 
            FROM TB_AccumulationMilestone 
            WHERE IsActive = 1 
            ORDER BY Amount ASC
        ");
        $stmt->execute();
        $milestones = $stmt->fetchAll();
        
        // 7. Kiểm tra user đã đạt mốc nào chưa
        $milestonesReached = [];
        
        foreach ($milestones as $milestone) {
            $milestoneAmount = floatval($milestone['Amount']);
            
            // Kiểm tra xem user đã đạt mốc này chưa (tích lũy >= mốc)
            if ($newAccumulation >= $milestoneAmount) {
                // Kiểm tra xem user đã nhận phần thưởng này chưa
                $stmtCheck = $db->prepare("
                    SELECT COUNT(*) as count 
                    FROM TB_AccumulationRewards 
                    WHERE JID = ? AND MilestoneID = ?
                ");
                $stmtCheck->execute([$userId, $milestone['MilestoneID']]);
                $checkResult = $stmtCheck->fetch();
                
                if ($checkResult['count'] == 0) {
                    // User đã đạt mốc nhưng chưa nhận phần thưởng
                    $milestonesReached[] = $milestone['MilestoneID'];
                }
            }
        }
        
        // 8. Tự động cộng item cho các mốc đã đạt (nếu cần auto-claim)
        // Hoặc chỉ return để user tự nhận sau
        
        return [
            'success' => true,
            'milestones_reached' => $milestonesReached,
            'current_accumulation' => $newAccumulation
        ];
        
    } catch (Exception $e) {
        error_log("Accumulation Handler Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'milestones_reached' => []
        ];
    }
}

/**
 * Lấy cấu hình tích lũy
 */
function getAccumulationConfig() {
    $db = ConnectionManager::getAccountDB();
    $stmt = $db->prepare("SELECT TOP 1 * FROM TB_AccumulationConfig ORDER BY ConfigID DESC");
    $stmt->execute();
    $config = $stmt->fetch();
    
    if (!$config) {
        // Tạo config mặc định nếu chưa có
        $stmt = $db->prepare("
            INSERT INTO TB_AccumulationConfig (IsEnabled, StartDate, EndDate) 
            VALUES (0, NULL, NULL)
        ");
        $stmt->execute();
        
        return [
            'ConfigID' => $db->lastInsertId(),
            'IsEnabled' => 0,
            'StartDate' => null,
            'EndDate' => null
        ];
    }
    
    return $config;
}

/**
 * Xử lý user nhận phần thưởng
 * 
 * @param int $userId User JID
 * @param int $milestoneID Milestone ID
 * @return array ['success' => bool, 'message' => string]
 */
function claimReward($userId, $milestoneID) {
    try {
        $db = ConnectionManager::getAccountDB();
        
        // 1. Kiểm tra user đã đạt mốc chưa
        $stmt = $db->prepare("SELECT AccumulationAmount FROM TB_User WHERE JID = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $accumulation = floatval($user['AccumulationAmount'] ?? 0);
        
        // 2. Lấy thông tin mốc
        $stmt = $db->prepare("SELECT Amount FROM TB_AccumulationMilestone WHERE MilestoneID = ? AND IsActive = 1");
        $stmt->execute([$milestoneID]);
        $milestone = $stmt->fetch();
        
        if (!$milestone) {
            return ['success' => false, 'message' => 'Mốc phần thưởng không tồn tại'];
        }
        
        $milestoneAmount = floatval($milestone['Amount']);
        
        if ($accumulation < $milestoneAmount) {
            return ['success' => false, 'message' => 'Bạn chưa đạt mốc tích lũy này'];
        }
        
        // 3. Kiểm tra đã nhận chưa
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM TB_AccumulationRewards WHERE JID = ? AND MilestoneID = ?");
        $stmt->execute([$userId, $milestoneID]);
        $check = $stmt->fetch();
        
        if ($check['count'] > 0) {
            return ['success' => false, 'message' => 'Bạn đã nhận phần thưởng này rồi'];
        }
        
        // 4. Lấy danh sách item của mốc
        $stmt = $db->prepare("SELECT ItemCode, Quantity FROM TB_AccumulationMilestoneItems WHERE MilestoneID = ?");
        $stmt->execute([$milestoneID]);
        $items = $stmt->fetchAll();
        
        // 5. Cộng item cho user
        $addedItems = [];
        foreach ($items as $item) {
            $result = addItemToUser($item['ItemCode'], $userId, $item['Quantity']);
            if ($result['success']) {
                $addedItems[] = $item;
            } else {
                // Log lỗi nhưng vẫn tiếp tục
                error_log("Failed to add item {$item['ItemCode']} to user $userId: " . $result['message']);
            }
        }
        
        // 6. Lưu vào lịch sử nhận phần thưởng
        $stmt = $db->prepare("
            INSERT INTO TB_AccumulationRewards (JID, MilestoneID, Amount) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $milestoneID, $accumulation]);
        
        return [
            'success' => true,
            'message' => 'Nhận phần thưởng thành công',
            'items' => $addedItems
        ];
        
    } catch (Exception $e) {
        error_log("Claim Reward Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
    }
}
```

---

### 3. Tích Hợp Vào Payment Flow

**File cần chỉnh sửa**: `includes/sepay_service.php` (hoặc nơi cộng silk)

**Vị trí**: Sau khi cập nhật silk thành công

```php
// ... existing code ...

// Update Silk for user
$stmt = $db->prepare("UPDATE SK_Silk SET silk_own = silk_own + ? WHERE JID = ?");
$stmt->execute([$order['SilkAmount'], $order['JID']]);

// NEW: Handle accumulation - Gọi TRƯỚC khi cộng silk
require_once __DIR__ . '/accumulation_handler.php';
$accumulationBefore = handleAccumulation($order['JID'], $order['Amount'], 'before');

// ... existing code ...

// NEW: Handle accumulation - Gọi SAU khi cộng silk
$accumulationAfter = handleAccumulation($order['JID'], $order['Amount'], 'after');

// Kiểm tra và tự động cộng item nếu user đạt mốc
if (!empty($accumulationAfter['milestones_reached'])) {
    foreach ($accumulationAfter['milestones_reached'] as $milestoneID) {
        // Có thể auto-claim hoặc để user tự claim
        // claimReward($order['JID'], $milestoneID);
    }
}
```

---

## 📁 File Structure

```
/includes/
  ├── accumulation_handler.php      (NEW - Xử lý tích lũy)
  ├── game_item_handler.php         (NEW - Cộng item vào game)
  
/admin/
  ├── accumulation.php              (NEW - Trang quản lý tích lũy)
  
/api/
  └── accumulation/
      ├── claim_reward.php          (NEW - API nhận phần thưởng)
      ├── get_user_accumulation.php (NEW - API lấy thông tin tích lũy)
      
/sql_scripts/
  └── add_accumulation_system.sql   (NEW - Migration script)
```

---

## ✅ Checklist Implementation

### Phase 1: Database & Core Functions
- [ ] Tạo migration script SQL
- [ ] Tạo file `includes/accumulation_handler.php`
- [ ] Tạo file `includes/game_item_handler.php` (TODO function addItemToUser)
- [ ] Research cách Silkroad lưu trữ item (tham khảo dev game)

### Phase 2: Admin Panel
- [ ] Tạo trang `/admin/accumulation.php`
- [ ] Implement bật/tắt feature
- [ ] Implement quản lý thời gian sự kiện
- [ ] Implement CRUD mốc phần thưởng
- [ ] Implement quản lý user tích lũy (reset, edit)
- [ ] Thêm menu vào CMS sidebar

### Phase 3: User Dashboard
- [ ] Thêm section tích lũy vào dashboard
- [ ] Implement hiển thị tiến độ
- [ ] Implement hiển thị mốc phần thưởng
- [ ] Implement countdown timer
- [ ] Implement nút nhận phần thưởng
- [ ] Handle trạng thái feature tắt

### Phase 4: Payment Integration
- [ ] Tích hợp `handleAccumulation()` vào payment flow
- [ ] Test tích lũy khi nạp tiền
- [ ] Test auto-claim hoặc manual claim

### Phase 5: Testing
- [ ] Test tất cả chức năng admin
- [ ] Test user flow
- [ ] Test edge cases (feature tắt, hết thời gian, etc.)
- [ ] Test performance với nhiều user

---

## 🔍 Notes & Considerations

1. **Item Storage**: Cần nghiên cứu kỹ cách Silkroad lưu trữ item. Thông thường:
   - Item lưu trong database SHARD
   - Có thể liên kết với Character (CharID) hoặc Account (JID)
   - Cần xử lý item stackable vs non-stackable

2. **Tích lũy**: 
   - Tích lũy được tính bằng VND (tiền thật), không phải Silk
   - Cập nhật tích lũy khi order status = 'completed'

3. **Performance**: 
   - Index database cho các query thường xuyên
   - Cache config nếu cần

4. **Security**:
   - Validate input ở tất cả endpoints
   - Check permission (admin only cho admin panel)
   - Prevent duplicate claim

---

## 📝 TODO Items

1. **Research Item Storage** (Priority: HIGH)
   - Tìm hiểu cấu trúc bảng item trong Silkroad
   - Xác định cách cộng item vào inventory
   - Implement function `addItemToUser()`

2. **Test Payment Integration**
   - Đảm bảo tích lũy được cập nhật đúng
   - Test với các trường hợp edge case

3. **UI/UX Enhancement**
   - Thiết kế giao diện dashboard tích lũy
   - Thiết kế giao diện admin panel
   - Animation/effect khi nhận phần thưởng

