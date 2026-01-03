# Hướng Dẫn Triển Khai PHP-Seepay trên Windows Server với XAMPP

## Mục Lục
1. [Cài Đặt Tự Động (Khuyến Nghị)](#cài-đặt-tự-động-khuyến-nghị)
2. [Yêu Cầu Hệ Thống](#yêu-cầu-hệ-thống)
3. [Cài Đặt XAMPP](#cài-đặt-xampp)
4. [Cài Đặt SQL Server Driver cho PHP](#cài-đặt-sql-server-driver-cho-php)
5. [Cài Đặt Composer](#cài-đặt-composer)
6. [Triển Khai Code](#triển-khai-code)
7. [Cấu Hình Database](#cấu-hình-database)
8. [Cấu Hình Môi Trường](#cấu-hình-môi-trường)
9. [Cấu Hình Apache](#cấu-hình-apache)
10. [Kiểm Tra và Xử Lý Lỗi](#kiểm-tra-và-xử-lý-lỗi)
11. [Bảo Mật](#bảo-mật)

---

## Cài Đặt Tự Động (Khuyến Nghị)

### Script Tự Động Kiểm Tra và Cài Đặt

Dự án đã bao gồm các script tự động để kiểm tra và cài đặt:

#### 1. Script Kiểm Tra Tự Động

**Cách 1: Sử dụng Batch File (Đơn giản nhất)**
```batch
# Chạy file install-and-check.bat với quyền Administrator
# Script sẽ tự động kiểm tra tất cả các thành phần
```

**Cách 2: Sử dụng PowerShell Script**
```powershell
# Mở PowerShell với quyền Administrator
cd C:\xampp\htdocs\php-seepay

# Chạy script kiểm tra cơ bản
.\install-and-check.ps1

# Hoặc với các tham số tùy chỉnh
.\install-and-check.ps1 `
    -XamppPath "C:\xampp" `
    -ProjectPath "C:\xampp\htdocs\php-seepay" `
    -SqlServer "127.0.0.1,1433" `
    -SqlUser "SA" `
    -SqlPassword "YourPassword" `
    -AutoStart
```

#### 2. Script Khởi Động Services

**Sử dụng Batch File:**
```batch
# Chạy file start-services.bat với quyền Administrator
# Script sẽ tự động khởi động Apache và kiểm tra SQL Server
```

**Sử dụng PowerShell:**
```powershell
# Khởi động Apache
.\start-services.ps1 -StartApache

# Khởi động cả Apache và SQL Server
.\start-services.ps1 -StartApache -StartSqlServer
```

### Các Tính Năng Của Script

Script tự động sẽ kiểm tra:

1. ✅ **XAMPP** - Đã cài đặt chưa
2. ✅ **PHP** - Phiên bản và extensions (json, curl, pdo_sqlsrv, sqlsrv)
3. ✅ **SQL Server Drivers** - Đã cài đặt và kích hoạt chưa
4. ✅ **Composer** - Đã cài đặt chưa
5. ✅ **Project Structure** - Các file/folder cần thiết
6. ✅ **Dependencies** - Tự động chạy `composer install` nếu thiếu
7. ✅ **.env File** - Tự động tạo từ env.example nếu chưa có
8. ✅ **Database Connection** - Kiểm tra kết nối SQL Server (nếu có mật khẩu)
9. ✅ **Apache Service** - Đang chạy chưa, tự động khởi động nếu cần

### Ví Dụ Sử Dụng

**Bước 1: Kiểm tra hệ thống**
```powershell
# Chạy với quyền Administrator
.\install-and-check.ps1
```

**Bước 2: Nếu thiếu dependencies, script sẽ tự động cài đặt**
```powershell
# Script tự động chạy: composer install
```

**Bước 3: Nếu thiếu .env, script sẽ tự động tạo**
```powershell
# Script tự động copy env.example thành .env
```

**Bước 4: Khởi động services**
```powershell
.\start-services.ps1
```

### Lưu Ý

- Script cần chạy với **quyền Administrator**
- Nếu SQL Server đã chạy sẵn trên port 1433, script sẽ bỏ qua phần khởi động SQL Server
- Script sẽ tự động kích hoạt SQL Server extensions trong php.ini nếu chưa được kích hoạt
- Nếu thiếu dependencies, script sẽ tự động chạy `composer install`

---

---

## Yêu Cầu Hệ Thống

### Phần Mềm Cần Thiết:
- **Windows Server** (2012 R2 trở lên hoặc Windows 10/11)
- **XAMPP** (phiên bản mới nhất với PHP 7.4+)
- **SQL Server** (SQL Server Express hoặc SQL Server Standard/Enterprise)
- **SQL Server Management Studio (SSMS)** (để quản lý database)
- **Composer** (PHP dependency manager)
- **Git** (để clone code hoặc tải code)

### Yêu Cầu PHP:
- PHP >= 7.4
- Extension: `ext-json` (thường có sẵn)
- Extension: `ext-curl` (thường có sẵn)
- Extension: `pdo_sqlsrv` (cần cài đặt riêng)
- Extension: `sqlsrv` (cần cài đặt riêng)

---

## Cài Đặt XAMPP

### Bước 1: Tải XAMPP
1. Truy cập: https://www.apachefriends.org/download.html
2. Tải phiên bản mới nhất (khuyến nghị PHP 7.4 hoặc 8.0+)
3. Chọn bản **Windows x64**

### Bước 2: Cài Đặt XAMPP
1. Chạy file installer với quyền **Administrator**
2. Chọn thư mục cài đặt (mặc định: `C:\xampp`)
3. Chọn các thành phần cần thiết:
   - ✅ Apache
   - ✅ MySQL (nếu cần, nhưng dự án này dùng SQL Server)
   - ✅ PHP
   - ✅ phpMyAdmin (tùy chọn)
4. Hoàn tất cài đặt

### Bước 3: Khởi Động Apache
1. Mở **XAMPP Control Panel**
2. Click **Start** cho **Apache**
3. Kiểm tra Apache đã chạy (nút chuyển sang màu xanh)

### Bước 4: Kiểm Tra PHP
1. Mở trình duyệt, truy cập: `http://localhost`
2. Click **phpinfo()** hoặc truy cập: `http://localhost/dashboard/phpinfo.php`
3. Ghi nhớ phiên bản PHP và đường dẫn PHP (thường là `C:\xampp\php`)

---

## Cài Đặt SQL Server Driver cho PHP

### Bước 1: Tải Microsoft Drivers for PHP for SQL Server
1. Truy cập: https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
2. Tải phiên bản phù hợp với PHP của bạn:
   - PHP 7.4: Tải **Microsoft Drivers 5.9 for PHP for SQL Server**
   - PHP 8.0: Tải **Microsoft Drivers 5.10+ for PHP for SQL Server**

### Bước 2: Giải Nén và Copy Files
1. Giải nén file đã tải (thường là file `.exe` tự giải nén)
2. Tìm thư mục chứa các file DLL:
   - `php_pdo_sqlsrv_74_nts.dll` (hoặc `php_pdo_sqlsrv_80_nts.dll`)
   - `php_sqlsrv_74_nts.dll` (hoặc `php_sqlsrv_80_nts.dll`)
   - Các file tương ứng cho **ts** (thread-safe) nếu PHP của bạn là thread-safe

### Bước 3: Xác Định Loại PHP (Thread-Safe hay Non-Thread-Safe)
1. Mở Command Prompt
2. Chạy: `C:\xampp\php\php.exe -i | findstr "Thread Safety"`
3. Nếu hiển thị **"Thread Safety => enabled"** → dùng file **ts**
4. Nếu hiển thị **"Thread Safety => disabled"** → dùng file **nts**

### Bước 4: Copy DLL vào Thư Mục PHP
1. Copy các file DLL vào: `C:\xampp\php\ext\`
   - `php_pdo_sqlsrv_74_nts.dll` → `php_pdo_sqlsrv.dll`
   - `php_sqlsrv_74_nts.dll` → `php_sqlsrv.dll`
   - (Hoặc đổi tên theo phiên bản PHP của bạn)

### Bước 5: Cài Đặt Microsoft ODBC Driver
1. Tải **Microsoft ODBC Driver for SQL Server**:
   - Truy cập: https://docs.microsoft.com/en-us/sql/connect/odbc/download-odbc-driver-for-sql-server
   - Tải phiên bản mới nhất (khuyến nghị **ODBC Driver 17 for SQL Server**)
2. Cài đặt ODBC Driver

### Bước 6: Kích Hoạt Extensions trong PHP
1. Mở file: `C:\xampp\php\php.ini`
2. Tìm dòng `;extension=sqlsrv` và `;extension=pdo_sqlsrv`
3. Bỏ dấu `;` và sửa thành:
   ```ini
   extension=sqlsrv
   extension=pdo_sqlsrv
   ```
4. Lưu file
5. **Khởi động lại Apache** trong XAMPP Control Panel

### Bước 7: Kiểm Tra Extensions
1. Tạo file `test_sqlsrv.php` trong `C:\xampp\htdocs\`:
   ```php
   <?php
   phpinfo();
   ?>
   ```
2. Truy cập: `http://localhost/test_sqlsrv.php`
3. Tìm kiếm "sqlsrv" và "pdo_sqlsrv" - phải thấy cả hai extensions

---

## Cài Đặt Composer

### Bước 1: Tải Composer
1. Truy cập: https://getcomposer.org/download/
2. Tải **Composer-Setup.exe**

### Bước 2: Cài Đặt Composer
1. Chạy file installer
2. Chọn đường dẫn PHP: `C:\xampp\php\php.exe`
3. Hoàn tất cài đặt

### Bước 3: Kiểm Tra Composer
1. Mở Command Prompt
2. Chạy: `composer --version`
3. Phải hiển thị phiên bản Composer

---

## Triển Khai Code

### Bước 1: Tạo Thư Mục Dự Án
1. Tạo thư mục trong `C:\xampp\htdocs\` (ví dụ: `C:\xampp\htdocs\php-seepay`)
2. Hoặc tạo thư mục riêng và cấu hình Virtual Host (khuyến nghị)

### Bước 2: Copy Code
**Cách 1: Clone từ Git (nếu có repository)**
```bash
cd C:\xampp\htdocs
git clone <repository-url> php-seepay
cd php-seepay
```

**Cách 2: Copy Code Thủ Công**
1. Copy toàn bộ code vào thư mục `C:\xampp\htdocs\php-seepay\`
2. Đảm bảo giữ nguyên cấu trúc thư mục

### Bước 3: Cài Đặt Dependencies
1. Mở Command Prompt
2. Di chuyển đến thư mục dự án:
   ```bash
   cd C:\xampp\htdocs\php-seepay
   ```
3. Chạy Composer:
   ```bash
   composer install
   ```
4. Đợi quá trình cài đặt hoàn tất

### Bước 4: Kiểm Tra Cấu Trúc
Đảm bảo có các thư mục/file sau:
```
php-seepay/
├── vendor/              (đã được tạo bởi composer)
├── api/
├── includes/
├── composer.json
├── composer.lock
├── env.example
└── ...
```

---

## Cấu Hình Database

### Bước 1: Tạo Database trong SQL Server
1. Mở **SQL Server Management Studio (SSMS)**
2. Kết nối đến SQL Server instance
3. Tạo database mới (ví dụ: `SRO_VT_ACCOUNT`)
4. Chạy các script SQL trong thư mục `sql_scripts/`:
   - `complete_database_setup.sql`
   - `cms_tables.sql` (nếu cần)

### Bước 2: Cấu Hình Kết Nối Database
1. Mở file: `database.php`
2. Cập nhật thông tin kết nối:
   ```php
   const SERVER_NAME = '127.0.0.1,1433';  // hoặc 'localhost,1433'
   const SERVER_USER = 'SA';              // hoặc username của bạn
   const SERVER_PASS = 'YourPassword';    // mật khẩu SQL Server
   
   const DB_ACCOUNT = "SRO_VT_ACCOUNT";
   const DB_LOG = "SRO_VT_ACCOUNT";
   const DB_SHARD = "SRO_VT_ACCOUNT";
   ```

### Bước 3: Kiểm Tra Kết Nối
1. Tạo file test: `test_db.php` trong thư mục dự án:
   ```php
   <?php
   require_once 'database.php';
   
   try {
       $db = DatabaseConfig::getAccountDB();
       echo "Kết nối database thành công!";
   } catch (Exception $e) {
       echo "Lỗi: " . $e->getMessage();
   }
   ?>
   ```
2. Truy cập: `http://localhost/php-seepay/test_db.php`
3. Nếu hiển thị "Kết nối database thành công!" → OK
4. **Xóa file test sau khi kiểm tra xong**

---

## Cấu Hình Môi Trường

### Bước 1: Tạo File .env
1. Copy file `env.example` thành `.env`:
   ```bash
   cd C:\xampp\htdocs\php-seepay
   copy env.example .env
   ```
   Hoặc thủ công: Copy `env.example` và đổi tên thành `.env`

### Bước 2: Cấu Hình .env
1. Mở file `.env` bằng Notepad++ hoặc text editor
2. Điền thông tin SePay:
   ```env
   # Sepay API Credentials
   # Lấy từ Sepay Dashboard: https://dashboard.sepay.vn
   sepay_MERCHANT_ID=SP-LIVE-XXXXXXX
   sepay_API_SECRET=spsk_live_xxxxxxxxxxx
   
   # Environment: production hoặc sandbox
   sepay_ENV=production
   
   # Webhook Secret (tạo random key)
   sepay_WEBHOOK_SECRET=your-random-secret-key-here
   
   # Thông tin tài khoản ngân hàng (cho QR code)
   sepay_BANK_ACCOUNT=012345678
   sepay_BANK_NAME=MBBANK
   sepay_ACCOUNT_NAME=NGUYEN VAN A
   sepay_QR_TEMPLATE=compact
   sepay_QR_DOWNLOAD=1
   ```

### Bước 3: Tạo Webhook Secret
1. Tạo một chuỗi ngẫu nhiên (có thể dùng online tool hoặc PowerShell):
   ```powershell
   -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 32 | % {[char]$_})
   ```
2. Copy chuỗi vào `sepay_WEBHOOK_SECRET`

### Bước 4: Cấu Hình Webhook URL trong SePay Dashboard
1. Đăng nhập: https://dashboard.sepay.vn
2. Vào phần **Webhook Settings**
3. Đặt Webhook URL: `https://yourdomain.com/api/sepay/webhook.php`
4. Đặt Webhook Secret: (giống với `sepay_WEBHOOK_SECRET` trong file `.env`)

---

## Cấu Hình Apache

### Bước 1: Cấu Hình Virtual Host (Khuyến Nghị)
1. Mở file: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. Thêm cấu hình:
   ```apache
   <VirtualHost *:80>
       ServerName yourdomain.com
       DocumentRoot "C:/xampp/htdocs/php-seepay"
       
       <Directory "C:/xampp/htdocs/php-seepay">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog "C:/xampp/apache/logs/php-seepay-error.log"
       CustomLog "C:/xampp/apache/logs/php-seepay-access.log" common
   </VirtualHost>
   ```

### Bước 2: Kích Hoạt Virtual Host
1. Mở file: `C:\xampp\apache\conf\httpd.conf`
2. Tìm dòng: `#Include conf/extra/httpd-vhosts.conf`
3. Bỏ dấu `#` để kích hoạt:
   ```apache
   Include conf/extra/httpd-vhosts.conf
   ```

### Bước 3: Cấu Hình .htaccess (Nếu Cần)
1. Tạo file `.htaccess` trong thư mục dự án (nếu chưa có)
2. Thêm các rule cần thiết:
   ```apache
   # Enable Rewrite Engine
   RewriteEngine On
   
   # Redirect to HTTPS (nếu có SSL)
   # RewriteCond %{HTTPS} off
   # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   
   # Protect .env file
   <Files ".env">
       Order allow,deny
       Deny from all
   </Files>
   
   # Protect sensitive files
   <FilesMatch "^(composer\.(json|lock)|\.git)">
       Order allow,deny
       Deny from all
   </FilesMatch>
   ```

### Bước 4: Khởi Động Lại Apache
1. Mở XAMPP Control Panel
2. Click **Stop** cho Apache
3. Click **Start** lại Apache

---

## Kiểm Tra và Xử Lý Lỗi

### Bước 1: Kiểm Tra PHP Errors
1. Mở file: `C:\xampp\php\php.ini`
2. Đảm bảo các cấu hình sau:
   ```ini
   display_errors = On          # Bật trong development
   error_reporting = E_ALL      # Hiển thị tất cả lỗi
   log_errors = On              # Ghi log lỗi
   error_log = "C:/xampp/php/logs/php_error.log"
   ```

### Bước 2: Kiểm Tra Apache Logs
- Error Log: `C:\xampp\apache\logs\error.log`
- Access Log: `C:\xampp\apache\logs\access.log`

### Bước 3: Kiểm Tra Quyền Truy Cập
1. Đảm bảo thư mục dự án có quyền đọc/ghi
2. Right-click thư mục → Properties → Security
3. Thêm quyền cho **IIS_IUSRS** hoặc **Everyone** (tùy môi trường)

### Bước 4: Kiểm Tra Extensions PHP
Tạo file `check_extensions.php`:
```php
<?php
$required = ['json', 'curl', 'pdo_sqlsrv', 'sqlsrv'];
$loaded = get_loaded_extensions();

echo "<h2>Kiểm tra PHP Extensions</h2>";
foreach ($required as $ext) {
    $status = in_array($ext, $loaded) ? '✅' : '❌';
    echo "$status $ext<br>";
}
?>
```

### Bước 5: Test API Endpoints
1. Test webhook endpoint: `http://localhost/php-seepay/api/sepay/webhook.php`
2. Test create order: `http://localhost/php-seepay/api/sepay/create_order.php`
3. Kiểm tra response và logs

---

## Bảo Mật

### 1. Bảo Vệ File .env
- Đảm bảo file `.env` không được truy cập từ web
- Sử dụng `.htaccess` để chặn truy cập

### 2. Cấu Hình Firewall
- Mở port 80 (HTTP) và 443 (HTTPS) nếu cần
- Chặn các port không cần thiết

### 3. Cài Đặt SSL Certificate (Khuyến Nghị)
- Sử dụng Let's Encrypt hoặc SSL certificate thương mại
- Cấu hình HTTPS trong Apache

### 4. Cập Nhật Định Kỳ
- Cập nhật XAMPP và PHP
- Cập nhật dependencies qua Composer: `composer update`
- Cập nhật SQL Server drivers

### 5. Backup Database
- Thiết lập backup tự động cho SQL Server
- Backup file `.env` và cấu hình

---

## Troubleshooting

### Lỗi: "Class 'PDO' not found"
**Giải pháp:** Kiểm tra extension `pdo` đã được bật trong `php.ini`:
```ini
extension=pdo
```

### Lỗi: "SQLSTATE[IMSSP]: This extension requires the Microsoft ODBC Driver for SQL Server"
**Giải pháp:** 
1. Cài đặt Microsoft ODBC Driver for SQL Server
2. Kiểm tra driver trong ODBC Data Source Administrator

### Lỗi: "Connection failed"
**Giải pháp:**
1. Kiểm tra SQL Server đang chạy
2. Kiểm tra SQL Server Browser service đang chạy
3. Kiểm tra firewall không chặn port 1433
4. Kiểm tra thông tin kết nối trong `database.php`

### Lỗi: "Composer not found"
**Giải pháp:**
1. Thêm đường dẫn Composer vào PATH environment variable
2. Hoặc sử dụng đường dẫn đầy đủ: `C:\ProgramData\ComposerSetup\bin\composer.bat`

### Lỗi: "Permission denied"
**Giải pháp:**
1. Chạy XAMPP Control Panel với quyền Administrator
2. Kiểm tra quyền truy cập thư mục

---

## Liên Hệ Hỗ Trợ

- **SePay Documentation:** https://developer.sepay.vn
- **SePay Dashboard:** https://dashboard.sepay.vn
- **Email Support:** info@sepay.vn

---

## Tóm Tắt Các Bước Chính

1. ✅ Cài đặt XAMPP
2. ✅ Cài đặt SQL Server Driver cho PHP
3. ✅ Cài đặt Composer
4. ✅ Copy code và chạy `composer install`
5. ✅ Cấu hình database trong `database.php`
6. ✅ Tạo file `.env` và điền thông tin SePay
7. ✅ Cấu hình Apache Virtual Host
8. ✅ Kiểm tra và test

**Chúc bạn triển khai thành công! 🚀**

