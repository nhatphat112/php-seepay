# Hướng Dẫn Sử Dụng Script Tự Động

## Tổng Quan

Dự án bao gồm các script tự động để kiểm tra, cài đặt và khởi động các service cần thiết trên Windows Server với XAMPP.

## Các Script Có Sẵn

### 1. `install-and-check.ps1` / `install-and-check.bat`
Script chính để kiểm tra và cài đặt tự động tất cả các thành phần.

### 2. `start-services.ps1` / `start-services.bat`
Script để khởi động Apache và kiểm tra SQL Server.

---

## Cách Sử Dụng Nhanh

### Bước 1: Kiểm Tra và Cài Đặt

**Cách đơn giản nhất (Khuyến nghị):**
```batch
# Click chuột phải vào install-and-check.bat
# Chọn "Run as administrator"
```

**Hoặc dùng PowerShell:**
```powershell
# Mở PowerShell với quyền Administrator
cd C:\xampp\htdocs\php-seepay
.\install-and-check.ps1
```

### Bước 2: Cấu Hình .env

Sau khi chạy script, nếu file `.env` được tạo tự động, bạn cần cấu hình:

1. Mở file `.env`
2. Điền thông tin SePay:
   - `sepay_MERCHANT_ID`
   - `sepay_API_SECRET`
   - `sepay_WEBHOOK_SECRET` (tạo random key)
   - Các thông tin khác

### Bước 3: Khởi Động Services

```batch
# Click chuột phải vào start-services.bat
# Chọn "Run as administrator"
```

Hoặc:
```powershell
.\start-services.ps1
```

---

## Các Tham Số Tùy Chỉnh

### install-and-check.ps1

```powershell
.\install-and-check.ps1 `
    -XamppPath "C:\xampp" `                    # Đường dẫn XAMPP
    -ProjectPath "C:\xampp\htdocs\php-seepay" # Đường dẫn dự án
    -SqlServer "127.0.0.1,1433" `              # SQL Server address
    -SqlUser "SA" `                            # SQL Server user
    -SqlPassword "YourPassword" `              # SQL Server password
    -SkipInstall `                             # Bỏ qua cài đặt dependencies
    -AutoStart                                 # Tự động khởi động services
```

**Ví dụ với SQL Server:**
```powershell
.\install-and-check.ps1 `
    -SqlServer "127.0.0.1,1433" `
    -SqlUser "SA" `
    -SqlPassword "MyStrongPass123" `
    -AutoStart
```

### start-services.ps1

```powershell
.\start-services.ps1 `
    -XamppPath "C:\xampp" `    # Đường dẫn XAMPP
    -StartApache `              # Khởi động Apache
    -StartSqlServer             # Khởi động SQL Server
```

---

## Các Kiểm Tra Tự Động

Script sẽ tự động kiểm tra:

1. ✅ **XAMPP** - Đã cài đặt tại đường dẫn chỉ định
2. ✅ **PHP** - Phiên bản >= 7.4 và các extensions cần thiết
3. ✅ **SQL Server Drivers** - Đã cài đặt và kích hoạt trong php.ini
4. ✅ **Composer** - Đã cài đặt và có trong PATH
5. ✅ **Project Structure** - Các file/folder quan trọng
6. ✅ **Dependencies** - Tự động chạy `composer install` nếu thiếu
7. ✅ **.env File** - Tự động tạo từ env.example nếu chưa có
8. ✅ **Database Connection** - Kiểm tra kết nối (nếu có mật khẩu)
9. ✅ **Apache Service** - Đang chạy và tự động khởi động nếu cần

---

## Tự Động Hóa

### Tự Động Cài Đặt Dependencies

Nếu thiếu thư mục `vendor/`, script sẽ tự động chạy:
```bash
composer install
```

### Tự Động Tạo .env

Nếu thiếu file `.env`, script sẽ tự động:
```bash
copy env.example .env
```

### Tự Động Kích Hoạt Extensions

Nếu SQL Server extensions chưa được kích hoạt trong `php.ini`, script sẽ tự động:
- Bỏ dấu `;` trước `extension=sqlsrv`
- Bỏ dấu `;` trước `extension=pdo_sqlsrv`

### Tự Động Khởi Động Apache

Nếu Apache chưa chạy và `-AutoStart` được bật, script sẽ tự động khởi động.

---

## Báo Cáo Kết Quả

Sau khi chạy, script sẽ hiển thị báo cáo tổng hợp:

```
========================================
   BÁO CÁO KIỂM TRA TỔNG HỢP
========================================

Tổng số kiểm tra: 9
Thành công: 8
Thất bại: 1

Chi tiết:
  ✓ XAMPP
  ✓ PHP
  ✓ SQL Server Drivers
  ✓ Composer
  ✓ Project Structure
  ✓ Dependencies
  ✗ .env File
  ✓ Database Connection
  ✓ Apache Service
```

---

## Xử Lý Lỗi

### Lỗi: "Script cần chạy với quyền Administrator"
**Giải pháp:** Chạy PowerShell hoặc Command Prompt với quyền Administrator

### Lỗi: "XAMPP chưa được cài đặt"
**Giải pháp:** Cài đặt XAMPP từ https://www.apachefriends.org/download.html

### Lỗi: "SQL Server Drivers chưa được cài đặt"
**Giải pháp:** 
1. Tải Microsoft Drivers for PHP for SQL Server
2. Copy DLL vào `C:\xampp\php\ext\`
3. Chạy lại script

### Lỗi: "Composer chưa được cài đặt"
**Giải pháp:** Cài đặt Composer từ https://getcomposer.org/download/

### Lỗi: "Không thể kết nối Database"
**Giải pháp:**
1. Kiểm tra SQL Server đang chạy
2. Kiểm tra port 1433 không bị chặn
3. Kiểm tra thông tin đăng nhập trong `database.php`

---

## Lưu Ý Quan Trọng

1. **Luôn chạy với quyền Administrator** - Script cần quyền để:
   - Kiểm tra và khởi động services
   - Sửa file php.ini
   - Tạo file .env

2. **SQL Server đã chạy sẵn** - Nếu SQL Server đã chạy trên port 1433, script sẽ bỏ qua phần khởi động

3. **Cấu hình .env** - Sau khi script tạo file .env, bạn **phải** cấu hình các giá trị thực tế

4. **Kiểm tra lại sau khi cài đặt** - Chạy lại script để đảm bảo tất cả đã sẵn sàng

---

## Ví Dụ Workflow Hoàn Chỉnh

```powershell
# 1. Clone hoặc copy code vào C:\xampp\htdocs\php-seepay
cd C:\xampp\htdocs\php-seepay

# 2. Chạy script kiểm tra (với quyền Administrator)
.\install-and-check.ps1 -SqlPassword "YourPassword" -AutoStart

# 3. Cấu hình .env (mở file .env và điền thông tin SePay)
notepad .env

# 4. Chạy lại script để kiểm tra .env đã cấu hình đúng
.\install-and-check.ps1 -SqlPassword "YourPassword"

# 5. Khởi động services (nếu chưa tự động)
.\start-services.ps1

# 6. Kiểm tra website
# Mở trình duyệt: http://localhost/php-seepay
```

---

## Hỗ Trợ

Nếu gặp vấn đề, vui lòng:
1. Chạy script với `-Verbose` để xem chi tiết
2. Kiểm tra file log (nếu có)
3. Xem file hướng dẫn chi tiết: `HUONG_DAN_TRIEN_KHAI_WINDOWS_SERVER.md`

