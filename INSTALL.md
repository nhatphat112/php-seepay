# Hướng Dẫn Cài Đặt Chi Tiết

## 📋 Yêu Cầu Hệ Thống

### Máy Chủ
- **Operating System**: Windows Server 2016+ hoặc Linux
- **Web Server**: Apache 2.4+ hoặc Nginx 1.18+
- **PHP**: 7.4+ hoặc 8.0+
- **Database**: Microsoft SQL Server 2014+

### PHP Extensions
- `php-sqlsrv` (SQL Server Driver)
- `php-pdo_sqlsrv` (PDO SQL Server)
- `php-mbstring` (Multi-byte string)
- `php-openssl` (SSL/TLS)
- `php-json` (JSON support)

## 🔧 Cài Đặt Từng Bước

### Bước 1: Cài Đặt PHP và Extensions

#### Windows với XAMPP
```bash
1. Download XAMPP từ https://www.apachefriends.org/
2. Cài đặt XAMPP vào C:\xampp
3. Download Microsoft SQL Server Drivers:
   https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server
4. Copy các file DLL vào C:\xampp\php\ext\
   - php_sqlsrv_81_ts.dll
   - php_pdo_sqlsrv_81_ts.dll
5. Edit C:\xampp\php\php.ini:
   extension=php_sqlsrv_81_ts.dll
   extension=php_pdo_sqlsrv_81_ts.dll
6. Restart Apache
```

#### Linux (Ubuntu/Debian)
```bash
# Install PHP
sudo apt update
sudo apt install php8.0 php8.0-cli php8.0-common php8.0-mbstring

# Install SQL Server drivers
curl https://packages.microsoft.com/keys/microsoft.asc | sudo apt-key add -
curl https://packages.microsoft.com/config/ubuntu/$(lsb_release -rs)/prod.list | sudo tee /etc/apt/sources.list.d/mssql-release.list
sudo apt update
sudo ACCEPT_EULA=Y apt install msodbcsql17 mssql-tools
sudo apt install unixodbc-dev

# Install PHP SQL Server extensions
sudo pecl install sqlsrv pdo_sqlsrv
echo "extension=sqlsrv.so" | sudo tee -a /etc/php/8.0/mods-available/sqlsrv.ini
echo "extension=pdo_sqlsrv.so" | sudo tee -a /etc/php/8.0/mods-available/pdo_sqlsrv.ini
sudo phpenmod sqlsrv pdo_sqlsrv

# Restart web server
sudo systemctl restart apache2
```

### Bước 2: Cấu Hình Database

#### Kiểm Tra Kết Nối SQL Server
```php
<?php
// test_connection.php
$serverName = "103.48.192.220,49668";
$connectionOptions = array(
    "Database" => "SRO_VT_ACCOUNT",
    "Uid" => "sa",
    "PWD" => "251292Son"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn) {
    echo "Connection established.<br />";
    sqlsrv_close($conn);
} else {
    echo "Connection could not be established.<br />";
    die(print_r(sqlsrv_errors(), true));
}
?>
```

#### Cấu Hình Firewall
```bash
# Windows Firewall
netsh advfirewall firewall add rule name="SQL Server" dir=in action=allow protocol=TCP localport=49668

# Linux UFW
sudo ufw allow 49668/tcp
```

### Bước 3: Upload Website

#### Sử dụng FTP
```bash
1. Connect to your server via FTP (FileZilla, WinSCP)
2. Upload all files to /var/www/html/ (Linux) or C:\xampp\htdocs\ (Windows)
3. Ensure permissions are set correctly:
   - Files: 644
   - Directories: 755
   - PHP files: 644
```

#### Sử dụng Git
```bash
cd /var/www/html/
git clone <your-repository-url> silkroad
cd silkroad
chmod -R 755 .
```

### Bước 4: Cấu Hình Website

#### 1. Database Configuration
Edit `database.php`:
```php
const SERVER_NAME = "your-server-ip,port";
const SERVER_USER = "your-username";
const SERVER_PASS = "your-password";
```

#### 2. Apache Configuration
```apache
<VirtualHost *:80>
    ServerName silkroad.example.com
    DocumentRoot /var/www/html/silkroad
    
    <Directory /var/www/html/silkroad>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/silkroad-error.log
    CustomLog ${APACHE_LOG_DIR}/silkroad-access.log combined
</VirtualHost>
```

#### 3. Nginx Configuration
```nginx
server {
    listen 80;
    server_name silkroad.example.com;
    root /var/www/html/silkroad;
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

### Bước 5: Kiểm Tra và Test

#### 1. Test PHP Info
Create `info.php`:
```php
<?php phpinfo(); ?>
```
Visit: http://your-domain/info.php

#### 2. Test Database Connection
Visit: http://your-domain/test_connection.php

#### 3. Test Website
- Homepage: http://your-domain/index.php
- Register: http://your-domain/register.php
- Login: http://your-domain/login.php

### Bước 6: Bảo Mật (Production)

#### 1. Enable HTTPS
```bash
# Install Let's Encrypt
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d silkroad.example.com
```

#### 2. Change Default Credentials
Edit `database.php` và thay đổi:
- Database passwords
- Server addresses

#### 3. Disable Error Display
Edit `php.ini`:
```ini
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

#### 4. Set Proper Permissions
```bash
# Linux
sudo chown -R www-data:www-data /var/www/html/silkroad
sudo chmod -R 755 /var/www/html/silkroad
sudo chmod 600 /var/www/html/silkroad/database.php
```

## ⚡ Quick Start (Development)

### Windows
```bash
1. Copy all files to C:\xampp\htdocs\silkroad
2. Start XAMPP Control Panel
3. Start Apache
4. Visit: http://localhost/silkroad/
```

### Linux
```bash
# Install PHP built-in server
cd /path/to/silkroad
php -S localhost:8000

# Visit: http://localhost:8000
```

## 🐛 Xử Lý Lỗi Thường Gặp

### Lỗi: "could not find driver"
**Giải pháp**: Cài đặt SQL Server PHP extensions

### Lỗi: "Connection failed"
**Giải pháp**: 
- Kiểm tra thông tin database trong `database.php`
- Kiểm tra firewall
- Kiểm tra SQL Server đang chạy

### Lỗi: "Class 'PDO' not found"
**Giải pháp**: Enable PDO extension trong php.ini

### Lỗi: 500 Internal Server Error
**Giải pháp**:
- Check Apache error logs
- Check file permissions
- Check .htaccess syntax

## 📞 Hỗ Trợ

Nếu gặp vấn đề, vui lòng:
1. Check logs: `/var/log/apache2/error.log`
2. Enable PHP errors temporarily
3. Contact support: support@silkroad.com

## ✅ Checklist

- [ ] PHP 7.4+ installed
- [ ] SQL Server drivers installed
- [ ] Database credentials configured
- [ ] Website files uploaded
- [ ] Permissions set correctly
- [ ] Apache/Nginx configured
- [ ] Database connection tested
- [ ] Website accessible
- [ ] HTTPS enabled (production)
- [ ] Error logging configured

---

**Chúc bạn cài đặt thành công! 🎮**

