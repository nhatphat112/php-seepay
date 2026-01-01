# Con Đường Tơ Lụa - Website Game

Website chính thức cho trò chơi **Con Đường Tơ Lụa** (Silkroad Online) với giao diện đẹp mắt và đầy đủ tính năng.

## 🎮 Tính Năng

### Trang Chủ
- ✨ Carousel hình ảnh tự động với 3 ảnh game
- 📊 Hiển thị trạng thái server real-time
- 🎯 Số lượng người chơi online
- 📰 Tin tức và sự kiện mới nhất
- 🏆 Bảng xếp hạng (Top Level, Bang Hội, PvP)

### Hệ Thống Tài Khoản
- 🔐 Đăng ký tài khoản mới
- 🔑 Đăng nhập an toàn
- 👤 Dashboard quản lý tài khoản
- 📈 Xem thông tin nhân vật
- 📊 Thống kê và hoạt động

### Kết Nối Database
- 🔗 Kết nối SQL Server tự động
- 🔄 Quản lý kết nối thông minh
- 🛡️ Bảo mật và xử lý lỗi
- 📝 Logging hoạt động

## 🚀 Cài Đặt

### Yêu Cầu
- PHP 7.4 hoặc cao hơn
- SQL Server Driver cho PHP (`sqlsrv` extension)
- Web Server (Apache/Nginx)
- SQL Server database

### Bước 1: Cấu Hình Database
Các thông tin kết nối đã được cấu hình sẵn trong `database.php`:
- Server: 103.48.192.220,49668
- Database: SRO_VT_ACCOUNT, SRO_VT_LOG, SRO_VT_SHARD

### Bước 2: Cài Đặt PHP Extensions
```bash
# Windows
# Download và cài đặt SQL Server Driver từ Microsoft
# https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server

# Thêm vào php.ini:
extension=php_sqlsrv_81_ts.dll
extension=php_pdo_sqlsrv_81_ts.dll
```

### Bước 3: Chạy Website
```bash
# Khởi động PHP Built-in Server
php -S localhost:8000

# Hoặc copy toàn bộ thư mục vào htdocs (XAMPP)
# Truy cập: http://localhost/Web/
```

## 📁 Cấu Trúc Thư Mục

```
Web/
├── index.php              # Trang chủ
├── login.php              # Đăng nhập
├── register.php           # Đăng ký
├── dashboard.php          # Bảng điều khiển
├── logout.php             # Đăng xuất
├── database.php           # Cấu hình database
├── connection_manager.php # Quản lý kết nối
├── css/
│   └── style.css         # Stylesheet chính
├── js/
│   └── main.js           # JavaScript chính
├── api/
│   ├── ranking.php       # API bảng xếp hạng
│   ├── server_status.php # API trạng thái server
│   └── character_info.php # API thông tin nhân vật
├── 1.jpg, 2.jpg, 3.jpg   # Hình ảnh game
└── README.md             # File này
```

## 🎨 Giao Diện

### Màu Sắc Chủ Đạo
- **Primary**: Vàng đồng (#d4a574)
- **Secondary**: Nâu (#8b6f47)
- **Accent**: Vàng kim (#ffd700)
- **Background**: Xanh đen (#0a0e27)

### Font Chữ
- **Headings**: Cinzel (serif, sang trọng)
- **Body**: Roboto (sans-serif, dễ đọc)

## 🔧 API Endpoints

### 1. Server Status
```
GET /api/server_status.php
```
Trả về:
- Trạng thái server (online/offline)
- Số người chơi online
- Tổng số nhân vật
- Thời gian uptime

### 2. Ranking
```
GET /api/ranking.php?type={level|guild|pvp}
```
Trả về danh sách xếp hạng theo loại:
- `level`: Top nhân vật theo cấp độ
- `guild`: Top bang hội
- `pvp`: Top PvP

### 3. Character Info
```
GET /api/character_info.php?char_id={id}
```
Trả về thông tin chi tiết nhân vật (yêu cầu đăng nhập)

## 🔐 Bảo Mật

### Đã Triển Khai
- ✅ Password hashing (MD5 - tương thích Silkroad)
- ✅ Prepared statements (chống SQL injection)
- ✅ Session management
- ✅ Input validation
- ✅ Error handling

### Khuyến Nghị
- 🔒 Sử dụng HTTPS trong production
- 🔑 Thay đổi thông tin database trong `database.php`
- 🛡️ Cấu hình firewall cho SQL Server
- 📝 Backup database định kỳ

## 📊 Database Tables

### TB_User (Account DB)
- JID (Primary Key)
- StrUserID (Username)
- password (MD5 hashed)
- Email
- RegDate

### _Char (Shard DB)
- CharID (Primary Key)
- UserJID (Foreign Key)
- CharName16 (Character Name)
- CurLevel
- ExpOffset
- RemainGold

### _LogEventUser (Log DB)
- UserJID
- EventID
- EventData
- RegDate

## 🎯 Sử Dụng

### Đăng Ký Tài Khoản
1. Truy cập trang chủ
2. Click "Đăng ký"
3. Điền thông tin (username 4-20 ký tự, password tối thiểu 6 ký tự)
4. Xác nhận điều khoản
5. Đăng ký thành công!

### Đăng Nhập
1. Click "Đăng nhập"
2. Nhập username và password
3. Chọn "Ghi nhớ đăng nhập" (tùy chọn)
4. Đăng nhập

### Xem Thông Tin Tài Khoản
1. Đăng nhập thành công
2. Tự động chuyển đến Dashboard
3. Xem thông tin tài khoản, nhân vật, thống kê

## 🎮 Tính Năng Nổi Bật

### Carousel Tự Động
- Chuyển ảnh tự động mỗi 5 giây
- Điều khiển thủ công bằng nút prev/next
- Indicator dots để chuyển nhanh

### Real-time Updates
- Số người chơi online cập nhật mỗi 30 giây
- Trạng thái server real-time
- Bảng xếp hạng động

### Responsive Design
- Tối ưu cho mobile
- Tablet friendly
- Desktop full-featured

## 🐛 Xử Lý Lỗi

Website đã xử lý các lỗi phổ biến:
- ❌ Kết nối database thất bại
- ❌ Username/email đã tồn tại
- ❌ Đăng nhập sai thông tin
- ❌ Session hết hạn
- ❌ API không khả dụng

## 📞 Hỗ Trợ

- 📧 Email: support@silkroad.com
- 📱 Hotline: 1900-xxxx
- 💬 Discord: [Link]
- 📘 Facebook: [Link]

## 📝 Changelog

### Version 1.0.0 (02/10/2025)
- ✨ Ra mắt website chính thức
- 🎨 Giao diện hiện đại với carousel
- 🔐 Hệ thống đăng ký/đăng nhập
- 📊 Dashboard quản lý tài khoản
- 🏆 Bảng xếp hạng đa dạng
- 🔗 Kết nối SQL Server ổn định
- 📱 Responsive design

## 🙏 Credits

- **Game**: Silkroad Online by Joymax
- **Developer**: [Your Name]
- **Design**: Modern Gaming UI
- **Fonts**: Google Fonts (Cinzel, Roboto)
- **Icons**: Font Awesome 6.4.0

## 📜 License

Copyright © 2025 Con Đường Tơ Lụa. All rights reserved.

---

**Chúc bạn có trải nghiệm tuyệt vời! 🎮🐉**

