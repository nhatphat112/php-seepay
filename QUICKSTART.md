# 🚀 Quick Start Guide - Con Đường Tơ Lụa

## ⚡ Chạy Website trong 5 Phút

### Bước 1: Khởi động XAMPP
```bash
1. Mở XAMPP Control Panel
2. Click "Start" Apache
3. Đợi Apache chạy (màu xanh)
```

### Bước 2: Truy cập Website
```
http://localhost/Web/
```

### Bước 3: Test Kết Nối
```
http://localhost/Web/test_connection.php
```

## 🎯 Các Trang Chính

| Trang | URL | Mô tả |
|-------|-----|-------|
| 🏠 Trang chủ | `/index.php` | Homepage với carousel |
| 📝 Đăng ký | `/register.php` | Tạo tài khoản mới |
| 🔑 Đăng nhập | `/login.php` | Login vào hệ thống |
| 👤 Dashboard | `/dashboard.php` | Quản lý tài khoản |
| 🧪 Test | `/test_connection.php` | Kiểm tra database |

## 🔧 Tùy Chỉnh Nhanh

### Đổi hình ảnh carousel
```bash
# Thay thế các file:
1.jpg  →  Ảnh của bạn (1920x1080px)
2.jpg  →  Ảnh của bạn (1920x1080px)  
3.jpg  →  Ảnh của bạn (1920x1080px)
```

### Đổi màu sắc
```css
/* Edit: css/style.css */
:root {
    --primary-color: #d4a574;    /* Màu chính */
    --secondary-color: #8b6f47;  /* Màu phụ */
    --accent-color: #ffd700;     /* Màu nhấn */
}
```

### Đổi thời gian carousel
```javascript
// Edit: js/main.js (line 44)
setInterval(() => {
    moveCarousel(1);
}, 5000);  // 5000 = 5 giây
```

## 📊 Database

### Thông tin kết nối
```php
Server:   103.48.192.220,49668
User:     sa
Password: 251292Son
```

### Databases
- `SRO_VT_ACCOUNT` - Tài khoản
- `SRO_VT_LOG` - Logs
- `SRO_VT_SHARD` - Game data

## 🐛 Fix Lỗi Nhanh

### Lỗi: "could not find driver"
```bash
✅ Cài SQL Server PHP extensions
   Edit php.ini, add:
   extension=php_sqlsrv_81_ts.dll
   extension=php_pdo_sqlsrv_81_ts.dll
```

### Lỗi: "Connection failed"
```bash
✅ Check database.php
✅ Check SQL Server running
✅ Check firewall
```

### Lỗi: Trang trắng
```bash
✅ Check: C:\xampp\apache\logs\error.log
✅ Enable display_errors in php.ini
```

## 📱 Test Trên Mobile

```bash
# Lấy IP máy tính:
ipconfig

# Truy cập từ điện thoại:
http://192.168.x.x/Web/
```

## 🔒 Checklist Production

- [ ] Đổi password database
- [ ] Enable HTTPS
- [ ] Xóa test_connection.php
- [ ] Tắt display_errors
- [ ] Set file permissions
- [ ] Enable firewall
- [ ] Setup backup

## 📚 Đọc Thêm

- `README.md` - Tài liệu đầy đủ
- `INSTALL.md` - Hướng dẫn cài đặt
- `HUONG_DAN.txt` - Hướng dẫn tiếng Việt
- `_BAT_DAU_O_DAY.txt` - Bắt đầu từ đây!

## 🎮 Enjoy!

Website đã sẵn sàng. Chúc bạn thành công! 🚀

---

**Need help?** Check `HUONG_DAN.txt` hoặc `README.md`

