# 🎲 Thuật Toán Weighted Random Selection

## 📋 Tổng Quan

Thuật toán **Weighted Random** (Random có trọng số) cho phép chọn ngẫu nhiên một phần tử từ danh sách, nhưng mỗi phần tử có xác suất được chọn khác nhau dựa trên "trọng số" (weight) của nó.

Trong trường hợp **Vòng Quay May Mắn**, mỗi vật phẩm có một tỉ lệ quay ra (`WinRate`), và thuật toán sẽ chọn vật phẩm dựa trên tỉ lệ này.

---

## 🔢 Cách Hoạt Động

### Ví Dụ Đơn Giản

Giả sử có 3 vật phẩm:
- **Vật phẩm A**: WinRate = 50% (50)
- **Vật phẩm B**: WinRate = 30% (30)
- **Vật phẩm C**: WinRate = 20% (20)

**Tổng tỉ lệ** = 50 + 30 + 20 = 100

**Cách chọn:**
1. Tạo số ngẫu nhiên từ 0 đến 100
2. Nếu số ngẫu nhiên ≤ 50 → Chọn vật phẩm A
3. Nếu số ngẫu nhiên > 50 và ≤ 80 (50+30) → Chọn vật phẩm B
4. Nếu số ngẫu nhiên > 80 → Chọn vật phẩm C

### Minh Họa

```
[0--------50--------80--------100]
  Vật A    Vật B     Vật C
```

---

## 💻 Implementation Hiện Tại

### Code trong `calculateSpinResult()`

```php
function calculateSpinResult() {
    $items = getLuckyWheelItems();
    
    // 1. Tính tổng tỉ lệ
    $totalRate = 0;
    foreach ($items as $item) {
        $totalRate += floatval($item['WinRate']);
    }
    
    // 2. Tạo số ngẫu nhiên từ 0 đến totalRate
    $random = mt_rand(0, intval($totalRate * 100)) / 100;
    
    // 3. Tìm vật phẩm tương ứng
    $currentRate = 0;
    foreach ($items as $item) {
        $currentRate += floatval($item['WinRate']);
        if ($random <= $currentRate) {
            return $item; // Trúng vật phẩm này
        }
    }
    
    // Fallback: trả về vật phẩm cuối cùng
    return end($items);
}
```

### Ví Dụ Chi Tiết

**Input:**
```php
$items = [
    ['ItemName' => 'iPhone 16 Pro', 'WinRate' => 0.5],   // 0.5%
    ['ItemName' => 'MacBook Air', 'WinRate' => 1.0],    // 1.0%
    ['ItemName' => 'Voucher 500K', 'WinRate' => 20.0],   // 20%
    ['ItemName' => 'Voucher 100K', 'WinRate' => 78.5], // 78.5%
];
```

**Bước 1: Tính tổng**
```
totalRate = 0.5 + 1.0 + 20.0 + 78.5 = 100.0
```

**Bước 2: Tạo số ngẫu nhiên**
```
random = mt_rand(0, 10000) / 100  // 0.00 đến 100.00
Giả sử random = 45.67
```

**Bước 3: Tìm vật phẩm**
```
currentRate = 0
- Item 1: currentRate = 0 + 0.5 = 0.5
  → 45.67 > 0.5? NO, tiếp tục
- Item 2: currentRate = 0.5 + 1.0 = 1.5
  → 45.67 > 1.5? YES, tiếp tục
- Item 3: currentRate = 1.5 + 20.0 = 21.5
  → 45.67 > 21.5? YES, tiếp tục
- Item 4: currentRate = 21.5 + 78.5 = 100.0
  → 45.67 <= 100.0? YES → TRÚNG Item 4 (Voucher 100K)
```

---

## ⚠️ Vấn Đề Và Cải Thiện

### Vấn Đề 1: Precision Loss

**Vấn đề:**
```php
$random = mt_rand(0, intval($totalRate * 100)) / 100;
```

Nếu `totalRate = 0.5 + 1.0 + 20.0 + 78.5 = 100.0`, thì:
- `intval(100.0 * 100) = 10000`
- `mt_rand(0, 10000)` → 0 đến 10000
- Chia 100 → 0.00 đến 100.00

**Vấn đề:** Nếu `WinRate` là số thập phân nhỏ (ví dụ: 0.01%), precision có thể bị mất.

**Giải pháp:** Sử dụng `mt_rand()` với range lớn hơn, hoặc dùng `random_int()` cho precision tốt hơn.

### Vấn Đề 2: Tổng Tỉ Lệ Không Bằng 100%

**Vấn đề:** Tổng tỉ lệ có thể > 100% hoặc < 100%.

**Ví dụ:**
- Item A: 50%
- Item B: 50%
- Item C: 50%
- **Tổng = 150%**

**Cách xử lý hiện tại:** Không normalize, chỉ dùng tổng thực tế.

**Cải thiện:** Có thể normalize về 100% hoặc giữ nguyên (cả 2 cách đều hợp lệ).

### Vấn Đề 3: Fallback Logic

**Vấn đề:** Nếu không tìm thấy (do floating point precision), trả về item cuối cùng.

**Cải thiện:** Đảm bảo luôn tìm thấy item (sử dụng `<=` thay vì `<`).

---

## ✅ Implementation Cải Thiện

### Version 2.0 (Improved)

```php
function calculateSpinResult() {
    try {
        $items = getLuckyWheelItems();
        
        if (empty($items)) {
            throw new Exception("No active items in lucky wheel");
        }
        
        // 1. Tính tổng tỉ lệ và build cumulative ranges
        $totalRate = 0;
        $ranges = [];
        
        foreach ($items as $index => $item) {
            $winRate = floatval($item['WinRate']);
            if ($winRate <= 0) {
                continue; // Bỏ qua item có tỉ lệ <= 0
            }
            
            $start = $totalRate;
            $totalRate += $winRate;
            $end = $totalRate;
            
            $ranges[] = [
                'item' => $item,
                'start' => $start,
                'end' => $end,
                'index' => $index
            ];
        }
        
        if (empty($ranges)) {
            throw new Exception("No valid items with win rate > 0");
        }
        
        // 2. Generate random number với precision cao hơn
        // Sử dụng microtime để tăng randomness
        $maxValue = intval($totalRate * 10000); // Precision: 0.0001
        $random = mt_rand(0, $maxValue) / 10000;
        
        // 3. Binary search hoặc linear search để tìm item
        // Linear search đủ nhanh cho số lượng item nhỏ (< 100)
        foreach ($ranges as $range) {
            if ($random >= $range['start'] && $random < $range['end']) {
                return $range['item'];
            }
        }
        
        // Fallback: Trả về item cuối cùng (shouldn't happen)
        return $ranges[count($ranges) - 1]['item'];
        
    } catch (Exception $e) {
        error_log("Error calculating spin result: " . $e->getMessage());
        throw $e;
    }
}
```

### Version 3.0 (Optimized với Normalization)

```php
function calculateSpinResult() {
    try {
        $items = getLuckyWheelItems();
        
        if (empty($items)) {
            throw new Exception("No active items in lucky wheel");
        }
        
        // 1. Filter và tính tổng
        $validItems = [];
        $totalRate = 0;
        
        foreach ($items as $item) {
            $winRate = floatval($item['WinRate']);
            if ($winRate > 0) {
                $validItems[] = $item;
                $totalRate += $winRate;
            }
        }
        
        if (empty($validItems)) {
            throw new Exception("No valid items with win rate > 0");
        }
        
        // 2. Normalize về 100% (optional)
        // Nếu muốn tổng luôn = 100%, uncomment:
        // $normalizeFactor = 100.0 / $totalRate;
        // foreach ($validItems as &$item) {
        //     $item['WinRate'] *= $normalizeFactor;
        // }
        // $totalRate = 100.0;
        
        // 3. Generate random với precision cao
        $random = mt_rand(0, intval($totalRate * 10000)) / 10000;
        
        // 4. Linear search (O(n), đủ nhanh cho < 100 items)
        $cumulative = 0;
        foreach ($validItems as $item) {
            $cumulative += floatval($item['WinRate']);
            if ($random <= $cumulative) {
                return $item;
            }
        }
        
        // Fallback
        return end($validItems);
        
    } catch (Exception $e) {
        error_log("Error calculating spin result: " . $e->getMessage());
        throw $e;
    }
}
```

---

## 📊 So Sánh Các Phương Pháp

### 1. Linear Search (Hiện tại)
- **Độ phức tạp:** O(n)
- **Ưu điểm:** Đơn giản, dễ hiểu
- **Nhược điểm:** Chậm với số lượng lớn (> 1000 items)
- **Phù hợp:** Vòng quay may mắn (thường < 20 items)

### 2. Binary Search
- **Độ phức tạp:** O(log n)
- **Ưu điểm:** Nhanh với số lượng lớn
- **Nhược điểm:** Cần sort trước, code phức tạp hơn
- **Phù hợp:** Hệ thống có > 100 items

### 3. Alias Method (Walker's Alias)
- **Độ phức tạp:** O(1) cho mỗi lần chọn, O(n) để setup
- **Ưu điểm:** Cực kỳ nhanh cho nhiều lần chọn
- **Nhược điểm:** Code phức tạp, memory overhead
- **Phù hợp:** Hệ thống cần chọn nhiều lần liên tiếp

---

## 🧪 Test Cases

### Test Case 1: Tỉ Lệ Chuẩn (Tổng = 100%)
```php
$items = [
    ['WinRate' => 50.0],  // 50%
    ['WinRate' => 30.0],  // 30%
    ['WinRate' => 20.0],  // 20%
];
// Expected: Item 1 = 50%, Item 2 = 30%, Item 3 = 20%
```

### Test Case 2: Tỉ Lệ Nhỏ (Tổng < 100%)
```php
$items = [
    ['WinRate' => 0.5],   // 0.5%
    ['WinRate' => 1.0],   // 1.0%
    ['WinRate' => 2.5],   // 2.5%
];
// Total = 4%, nghĩa là 96% không trúng gì (nếu muốn)
// Hoặc normalize về 100%
```

### Test Case 3: Tỉ Lệ Lớn (Tổng > 100%)
```php
$items = [
    ['WinRate' => 50.0],  // 50%
    ['WinRate' => 50.0],  // 50%
    ['WinRate' => 50.0],  // 50%
];
// Total = 150%, mỗi item có xác suất thực tế = 50/150 = 33.33%
```

### Test Case 4: Precision Test
```php
$items = [
    ['WinRate' => 0.01],  // 0.01%
    ['WinRate' => 0.02],  // 0.02%
    ['WinRate' => 99.97], // 99.97%
];
// Total = 100%, cần đảm bảo precision đủ cao
```

---

## 📈 Thống Kê Và Validation

### Cách Test Thuật Toán

```php
function testWeightedRandom($iterations = 10000) {
    $items = getLuckyWheelItems();
    $results = [];
    
    // Initialize counters
    foreach ($items as $item) {
        $results[$item['Id']] = 0;
    }
    
    // Run many iterations
    for ($i = 0; $i < $iterations; $i++) {
        $won = calculateSpinResult();
        $results[$won['Id']]++;
    }
    
    // Calculate actual percentages
    $totalRate = array_sum(array_column($items, 'WinRate'));
    
    echo "Expected vs Actual:\n";
    foreach ($items as $item) {
        $expected = ($item['WinRate'] / $totalRate) * 100;
        $actual = ($results[$item['Id']] / $iterations) * 100;
        $diff = abs($expected - $actual);
        
        printf(
            "Item %s: Expected %.2f%%, Actual %.2f%%, Diff: %.2f%%\n",
            $item['ItemName'],
            $expected,
            $actual,
            $diff
        );
    }
}
```

---

## 🎯 Kết Luận

### Implementation Hiện Tại
- ✅ **Đúng:** Thuật toán hoạt động chính xác
- ✅ **Đủ nhanh:** O(n) phù hợp với < 20 items
- ⚠️ **Có thể cải thiện:** Precision và error handling

### Khuyến Nghị
1. **Giữ nguyên** nếu số lượng items < 50
2. **Cải thiện precision** nếu có WinRate < 0.1%
3. **Thêm validation** cho edge cases
4. **Thêm logging** để debug

### Code Hiện Tại: **ĐỦ TỐT** cho production ✅

---

**Last Updated:** 2026-01-14
