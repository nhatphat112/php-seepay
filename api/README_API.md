# 🔌 API Debug & Testing Guide

## 🐛 Sửa Lỗi 500 Internal Server Error

### Các File Debug Đã Tạo:

1. **`api/test.php`** - Test cơ bản
   - Kiểm tra API hoạt động
   - Kiểm tra connection manager
   - Kiểm tra extensions

2. **`api/debug.php`** - Debug chi tiết  
   - Xem error logs
   - Test database connections
   - Xem request info
   - ⚠️ **XÓA FILE NÀY SAU KHI DEBUG!**

### Các Bước Debug:

#### Bước 1: Test API Cơ Bản
```
http://localhost:8088/api/test.php
```

Nếu thấy `"status": "success"` → API hoạt động!

#### Bước 2: Xem Debug Chi Tiết
```
http://localhost:8088/api/debug.php
```

Kiểm tra:
- ✅ PHP Extensions loaded?
- ✅ Database connected?
- ✅ Error logs có gì?

#### Bước 3: Test Từng API
```
http://localhost:8088/api/server_status.php
http://localhost:8088/api/ranking.php?type=level
http://localhost:8088/api/ranking.php?type=guild
http://localhost:8088/api/ranking.php?type=pvp
```

## 🔧 Các Thay Đổi Đã Sửa:

### 1. Security Check (database.php)
✅ Cho phép API GET requests mà không cần CSRF token
✅ Cho phép requests từ localhost

### 2. Error Handling
✅ Better error messages
✅ Try-catch cho từng query
✅ Return empty array thay vì error khi không có data

### 3. CORS Headers
✅ Thêm `Access-Control-Allow-Origin: *`
✅ Cho phép cross-origin requests

## 📊 API Endpoints

### 1. Server Status
```
GET /api/server_status.php

Response:
{
  "status": "online",
  "online_players": 0,
  "total_characters": 123,
  "uptime": 3600,
  "server_time": "2025-10-02 20:00:00",
  "connections": {...}
}
```

### 2. Ranking
```
GET /api/ranking.php?type=level
GET /api/ranking.php?type=guild
GET /api/ranking.php?type=pvp

Response:
[
  {
    "name": "Player1",
    "level": 100,
    "exp": 999999
  },
  ...
]
```

### 3. Character Info
```
GET /api/character_info.php?char_id=123

Response:
{
  "CharID": 123,
  "name": "Player1",
  "level": 100,
  "gold": 1000000,
  ...
}
```

## 🐞 Common Errors & Solutions

### Error: "could not find driver"
**Nguyên nhân**: Chưa cài SQL Server extensions

**Giải pháp**:
```ini
; Thêm vào php.ini:
extension=php_sqlsrv_81_ts.dll
extension=php_pdo_sqlsrv_81_ts.dll
```
Restart Apache!

### Error: "Connection failed"
**Nguyên nhân**: 
- SQL Server không chạy
- Thông tin kết nối sai
- Firewall chặn

**Giải pháp**:
1. Check SQL Server đang chạy
2. Check `database.php` - thông tin đúng?
3. Check firewall port 49668

### Error: "Failed to load connection manager"
**Nguyên nhân**: Lỗi require path hoặc syntax error

**Giải pháp**:
1. Check file path: `../connection_manager.php`
2. Check syntax errors trong connection_manager.php
3. Xem error log

### Error: Empty response `[]`
**Nguyên nhân**: Database chưa có dữ liệu (BÌNH THƯỜNG!)

**Giải pháp**: Không cần sửa, đây không phải lỗi!

## 🧪 Testing Checklist

- [ ] `api/test.php` → Status success?
- [ ] `api/debug.php` → All green checkmarks?
- [ ] `api/server_status.php` → Returns JSON?
- [ ] `api/ranking.php?type=level` → Returns array?
- [ ] Refresh trang chủ → Không còn lỗi console?

## 📝 Error Log Locations

### Windows (XAMPP):
```
C:\xampp\apache\logs\error.log
C:\xampp\php\logs\php_error_log
```

### Linux:
```
/var/log/apache2/error.log
/var/log/php_errors.log
```

## 🔒 Security Notes

### Development (Localhost):
- ✅ display_errors = On
- ✅ debug.php enabled
- ✅ CORS = *

### Production:
- ⚠️ display_errors = Off
- ⚠️ DELETE debug.php
- ⚠️ CORS = specific domain
- ⚠️ Enable rate limiting

## 💡 Tips

### 1. Clear Browser Cache
```
Ctrl + Shift + R (Chrome)
Cmd + Shift + R (Mac)
```

### 2. Check Console
```
F12 → Console Tab
Look for errors!
```

### 3. Test in Postman
```
GET http://localhost:8088/api/server_status.php
```

### 4. Check Apache Status
```
XAMPP Control Panel → Apache → Running (green)?
```

## 🎯 Quick Fix Commands

### Restart Apache:
```bash
# XAMPP
Stop → Start Apache

# Linux
sudo systemctl restart apache2
```

### View Logs:
```bash
# Windows
type C:\xampp\apache\logs\error.log

# Linux
tail -f /var/log/apache2/error.log
```

## 📞 Still Not Working?

1. Check `api/debug.php` output
2. Read error logs carefully
3. Google the error message
4. Check database credentials
5. Verify SQL Server is running

## ⚠️ Remember!

- **DELETE `api/debug.php` before production!**
- **DELETE `test_connection.php` before production!**
- Change database passwords!
- Enable HTTPS!

---

**Need more help?** Check the main README.md or error logs!

Good luck! 🚀

