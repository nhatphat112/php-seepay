# ============================================
# Script Tự Động Kiểm Tra và Cài Đặt PHP-Seepay
# Windows Server với XAMPP
# ============================================

# Yêu cầu chạy với quyền Administrator
#Requires -RunAsAdministrator

param(
    [string]$XamppPath = "C:\xampp",
    [string]$ProjectPath = "",
    [string]$SqlServer = "127.0.0.1,1433",
    [string]$SqlUser = "SA",
    [string]$SqlPassword = "",
    [switch]$SkipInstall = $false,
    [switch]$AutoStart = $true
)

# Màu sắc cho output
function Write-Step {
    param([string]$Message)
    Write-Host "`n[STEP] $Message" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "[✓] $Message" -ForegroundColor Green
}

function Write-Error {
    param([string]$Message)
    Write-Host "[✗] $Message" -ForegroundColor Red
}

function Write-Warning {
    param([string]$Message)
    Write-Host "[!] $Message" -ForegroundColor Yellow
}

function Write-Info {
    param([string]$Message)
    Write-Host "[i] $Message" -ForegroundColor Gray
}

# Kiểm tra quyền Administrator
function Test-Administrator {
    $currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

# Kiểm tra XAMPP
function Test-Xampp {
    Write-Step "Kiểm tra XAMPP..."
    
    if (Test-Path "$XamppPath\xampp-control.exe") {
        Write-Success "XAMPP đã được cài đặt tại: $XamppPath"
        return $true
    }
    
    Write-Error "XAMPP chưa được cài đặt tại: $XamppPath"
    Write-Info "Vui lòng cài đặt XAMPP từ: https://www.apachefriends.org/download.html"
    return $false
}

# Kiểm tra PHP
function Test-PHP {
    Write-Step "Kiểm tra PHP..."
    
    $phpPath = "$XamppPath\php\php.exe"
    if (-not (Test-Path $phpPath)) {
        Write-Error "PHP không tìm thấy tại: $phpPath"
        return $false
    }
    
    $phpVersion = & $phpPath -r "echo PHP_VERSION;"
    Write-Success "PHP Version: $phpVersion"
    
    # Kiểm tra phiên bản PHP >= 7.4
    $versionParts = $phpVersion -split '\.'
    $major = [int]$versionParts[0]
    $minor = [int]$versionParts[1]
    
    if ($major -lt 7 -or ($major -eq 7 -and $minor -lt 4)) {
        Write-Error "PHP version phải >= 7.4. Hiện tại: $phpVersion"
        return $false
    }
    
    # Kiểm tra extensions
    $extensions = @('json', 'curl', 'pdo_sqlsrv', 'sqlsrv')
    $missing = @()
    
    foreach ($ext in $extensions) {
        $result = & $phpPath -m | Select-String -Pattern "^$ext$"
        if ($result) {
            Write-Success "Extension '$ext' đã được cài đặt"
        } else {
            Write-Warning "Extension '$ext' chưa được cài đặt"
            $missing += $ext
        }
    }
    
    if ($missing.Count -gt 0) {
        Write-Warning "Thiếu các extensions: $($missing -join ', ')"
        Write-Info "Cần cài đặt SQL Server drivers cho PHP"
        return $false
    }
    
    return $true
}

# Kiểm tra SQL Server Drivers
function Test-SqlServerDrivers {
    Write-Step "Kiểm tra SQL Server Drivers..."
    
    $phpPath = "$XamppPath\php\php.exe"
    $extPath = "$XamppPath\php\ext"
    
    # Kiểm tra file DLL
    $dllFiles = @('php_sqlsrv.dll', 'php_pdo_sqlsrv.dll')
    $missing = @()
    
    foreach ($dll in $dllFiles) {
        $dllPath = Join-Path $extPath $dll
        if (Test-Path $dllPath) {
            Write-Success "Tìm thấy: $dll"
        } else {
            Write-Warning "Không tìm thấy: $dll"
            $missing += $dll
        }
    }
    
    # Kiểm tra trong php.ini
    $phpIni = "$XamppPath\php\php.ini"
    if (Test-Path $phpIni) {
        $content = Get-Content $phpIni -Raw
        if ($content -match 'extension=sqlsrv' -and $content -match 'extension=pdo_sqlsrv') {
            Write-Success "SQL Server extensions đã được kích hoạt trong php.ini"
        } else {
            Write-Warning "SQL Server extensions chưa được kích hoạt trong php.ini"
            if (-not $SkipInstall) {
                Write-Info "Đang kích hoạt extensions..."
                Enable-SqlServerExtensions -PhpIniPath $phpIni
            }
        }
    }
    
    if ($missing.Count -gt 0) {
        Write-Error "Thiếu SQL Server drivers. Vui lòng cài đặt từ:"
        Write-Info "https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server"
        return $false
    }
    
    return $true
}

# Kích hoạt SQL Server extensions trong php.ini
function Enable-SqlServerExtensions {
    param([string]$PhpIniPath)
    
    try {
        $content = Get-Content $PhpIniPath -Raw
        
        # Thêm extension nếu chưa có
        if ($content -notmatch 'extension=sqlsrv') {
            $content = $content -replace '(;extension=sqlsrv)', 'extension=sqlsrv'
            if ($content -notmatch 'extension=sqlsrv') {
                $content += "`nextension=sqlsrv`n"
            }
        }
        
        if ($content -notmatch 'extension=pdo_sqlsrv') {
            $content = $content -replace '(;extension=pdo_sqlsrv)', 'extension=pdo_sqlsrv'
            if ($content -notmatch 'extension=pdo_sqlsrv') {
                $content += "`nextension=pdo_sqlsrv`n"
            }
        }
        
        Set-Content -Path $PhpIniPath -Value $content -NoNewline
        Write-Success "Đã kích hoạt SQL Server extensions trong php.ini"
        return $true
    } catch {
        Write-Error "Không thể cập nhật php.ini: $_"
        return $false
    }
}

# Kiểm tra Composer
function Test-Composer {
    Write-Step "Kiểm tra Composer..."
    
    try {
        $composerVersion = composer --version 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "Composer đã được cài đặt: $composerVersion"
            return $true
        }
    } catch {
        # Composer không có trong PATH
    }
    
    # Kiểm tra đường dẫn mặc định
    $composerPaths = @(
        "$env:ProgramData\ComposerSetup\bin\composer.bat",
        "$env:LOCALAPPDATA\Programs\Composer\bin\composer.bat",
        "C:\ProgramData\ComposerSetup\bin\composer.bat"
    )
    
    foreach ($path in $composerPaths) {
        if (Test-Path $path) {
            Write-Success "Tìm thấy Composer tại: $path"
            $env:Path += ";$(Split-Path $path)"
            return $true
        }
    }
    
    Write-Error "Composer chưa được cài đặt"
    Write-Info "Tải và cài đặt từ: https://getcomposer.org/download/"
    return $false
}

# Kiểm tra Database Connection
function Test-DatabaseConnection {
    param(
        [string]$Server,
        [string]$User,
        [string]$Password
    )
    
    Write-Step "Kiểm tra kết nối Database..."
    
    if ([string]::IsNullOrEmpty($Password)) {
        Write-Warning "Chưa cung cấp mật khẩu SQL Server. Bỏ qua kiểm tra kết nối."
        return $true
    }
    
    $phpPath = "$XamppPath\php\php.exe"
    $testScript = @"
<?php
try {
    `$dsn = "sqlsrv:server=$Server;database=master";
    `$conn = new PDO(`$dsn, "$User", "$Password");
    `$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "SUCCESS";
} catch (Exception `$e) {
    echo "ERROR: " . `$e->getMessage();
}
?>
"@
    
    $tempFile = [System.IO.Path]::GetTempFileName() + ".php"
    Set-Content -Path $tempFile -Value $testScript
    
    try {
        $result = & $phpPath $tempFile 2>&1
        Remove-Item $tempFile -Force
        
        if ($result -match "SUCCESS") {
            Write-Success "Kết nối Database thành công!"
            return $true
        } else {
            Write-Error "Không thể kết nối Database: $result"
            return $false
        }
    } catch {
        Remove-Item $tempFile -ErrorAction SilentlyContinue
        Write-Error "Lỗi khi kiểm tra Database: $_"
        return $false
    }
}

# Kiểm tra Project Structure
function Test-ProjectStructure {
    param([string]$Path)
    
    Write-Step "Kiểm tra cấu trúc dự án..."
    
    if ([string]::IsNullOrEmpty($Path)) {
        $Path = Get-Location
    }
    
    if (-not (Test-Path $Path)) {
        Write-Error "Thư mục dự án không tồn tại: $Path"
        return $false
    }
    
    Write-Success "Thư mục dự án: $Path"
    
    # Kiểm tra các file/folder quan trọng
    $requiredItems = @(
        "composer.json",
        "database.php",
        "env.example",
        "includes",
        "api"
    )
    
    $missing = @()
    foreach ($item in $requiredItems) {
        $itemPath = Join-Path $Path $item
        if (Test-Path $itemPath) {
            Write-Success "Tìm thấy: $item"
        } else {
            Write-Warning "Không tìm thấy: $item"
            $missing += $item
        }
    }
    
    if ($missing.Count -gt 0) {
        Write-Warning "Thiếu các file/folder: $($missing -join ', ')"
    }
    
    return $true
}

# Kiểm tra .env file
function Test-EnvFile {
    param([string]$ProjectPath)
    
    Write-Step "Kiểm tra file .env..."
    
    $envPath = Join-Path $ProjectPath ".env"
    $envExamplePath = Join-Path $ProjectPath "env.example"
    
    if (Test-Path $envPath) {
        Write-Success "File .env đã tồn tại"
        
        # Kiểm tra các biến cần thiết
        $content = Get-Content $envPath -Raw
        $requiredVars = @(
            "sepay_MERCHANT_ID",
            "sepay_API_SECRET",
            "sepay_ENV",
            "sepay_WEBHOOK_SECRET"
        )
        
        $missing = @()
        foreach ($var in $requiredVars) {
            if ($content -match "$var=") {
                $value = ($content | Select-String -Pattern "$var=(.+)" | ForEach-Object { $_.Matches.Groups[1].Value.Trim() })
                if ([string]::IsNullOrWhiteSpace($value) -or $value -match "xxxxx|your_|example") {
                    Write-Warning "$var chưa được cấu hình đúng"
                    $missing += $var
                } else {
                    Write-Success "$var đã được cấu hình"
                }
            } else {
                Write-Warning "$var không tồn tại trong .env"
                $missing += $var
            }
        }
        
        if ($missing.Count -gt 0) {
            Write-Warning "Cần cấu hình các biến: $($missing -join ', ')"
            return $false
        }
        
        return $true
    } else {
        Write-Warning "File .env chưa tồn tại"
        
        if (Test-Path $envExamplePath) {
            Write-Info "Đang tạo file .env từ env.example..."
            Copy-Item $envExamplePath $envPath
            Write-Success "Đã tạo file .env. Vui lòng cấu hình các giá trị cần thiết."
        } else {
            Write-Error "Không tìm thấy env.example"
        }
        
        return $false
    }
}

# Kiểm tra Dependencies (vendor folder)
function Test-Dependencies {
    param([string]$ProjectPath)
    
    Write-Step "Kiểm tra Dependencies..."
    
    $vendorPath = Join-Path $ProjectPath "vendor"
    $composerJsonPath = Join-Path $ProjectPath "composer.json"
    
    if (-not (Test-Path $composerJsonPath)) {
        Write-Error "Không tìm thấy composer.json"
        return $false
    }
    
    if (Test-Path $vendorPath) {
        Write-Success "Thư mục vendor đã tồn tại"
        
        # Kiểm tra autoload
        $autoloadPath = Join-Path $vendorPath "autoload.php"
        if (Test-Path $autoloadPath) {
            Write-Success "Composer autoload đã được tạo"
            return $true
        } else {
            Write-Warning "Thư mục vendor tồn tại nhưng thiếu autoload.php"
        }
    } else {
        Write-Warning "Thư mục vendor chưa tồn tại"
    }
    
    if (-not $SkipInstall) {
        Write-Info "Đang cài đặt dependencies..."
        Push-Location $ProjectPath
        try {
            composer install --no-interaction 2>&1 | Out-Host
            if ($LASTEXITCODE -eq 0) {
                Write-Success "Đã cài đặt dependencies thành công"
                return $true
            } else {
                Write-Error "Lỗi khi cài đặt dependencies"
                return $false
            }
        } catch {
            Write-Error "Lỗi: $_"
            return $false
        } finally {
            Pop-Location
        }
    } else {
        Write-Info "Bỏ qua cài đặt dependencies (--SkipInstall)"
        return $false
    }
}

# Kiểm tra Apache Service
function Test-ApacheService {
    Write-Step "Kiểm tra Apache Service..."
    
    $apacheService = Get-Service -Name "Apache*" -ErrorAction SilentlyContinue
    
    if ($apacheService) {
        $status = $apacheService.Status
        if ($status -eq "Running") {
            Write-Success "Apache đang chạy"
            return $true
        } else {
            Write-Warning "Apache đã được cài đặt nhưng chưa chạy (Status: $status)"
            if ($AutoStart) {
                Write-Info "Đang khởi động Apache..."
                Start-Service $apacheService.Name
                Start-Sleep -Seconds 3
                if ((Get-Service $apacheService.Name).Status -eq "Running") {
                    Write-Success "Đã khởi động Apache"
                    return $true
                }
            }
            return $false
        }
    } else {
        # Kiểm tra XAMPP Apache
        $xamppApache = "$XamppPath\apache\bin\httpd.exe"
        if (Test-Path $xamppApache) {
            Write-Info "Apache của XAMPP đã được cài đặt"
            Write-Info "Vui lòng khởi động Apache từ XAMPP Control Panel"
            return $false
        } else {
            Write-Error "Apache chưa được cài đặt"
            return $false
        }
    }
}

# Tạo báo cáo tổng hợp
function Show-Summary {
    param(
        [hashtable]$Results
    )
    
    Write-Host "`n" -NoNewline
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "   BÁO CÁO KIỂM TRA TỔNG HỢP" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    
    $total = $Results.Count
    $passed = ($Results.Values | Where-Object { $_ -eq $true }).Count
    $failed = $total - $passed
    
    Write-Host "`nTổng số kiểm tra: $total" -ForegroundColor White
    Write-Host "Thành công: $passed" -ForegroundColor Green
    Write-Host "Thất bại: $failed" -ForegroundColor Red
    
    Write-Host "`nChi tiết:" -ForegroundColor Yellow
    foreach ($key in $Results.Keys) {
        $status = if ($Results[$key]) { "✓" } else { "✗" }
        $color = if ($Results[$key]) { "Green" } else { "Red" }
        Write-Host "  $status $key" -ForegroundColor $color
    }
    
    if ($failed -eq 0) {
        Write-Host "`n[✓] Tất cả kiểm tra đều thành công!" -ForegroundColor Green
        Write-Host "Hệ thống đã sẵn sàng để sử dụng." -ForegroundColor Green
    } else {
        Write-Host "`n[!] Có $failed kiểm tra thất bại. Vui lòng xem chi tiết ở trên." -ForegroundColor Yellow
    }
    
    Write-Host "`n========================================" -ForegroundColor Cyan
}

# ============================================
# MAIN SCRIPT
# ============================================

Write-Host @"
========================================
  PHP-SEEPAY AUTO INSTALL & CHECK SCRIPT
  Windows Server với XAMPP
========================================
"@ -ForegroundColor Cyan

# Kiểm tra quyền Administrator
if (-not (Test-Administrator)) {
    Write-Error "Script cần chạy với quyền Administrator!"
    Write-Info "Vui lòng mở PowerShell với quyền Administrator và chạy lại."
    exit 1
}

# Xác định đường dẫn dự án
if ([string]::IsNullOrEmpty($ProjectPath)) {
    $ProjectPath = Get-Location
    Write-Info "Sử dụng thư mục hiện tại: $ProjectPath"
} else {
    if (-not (Test-Path $ProjectPath)) {
        Write-Error "Thư mục dự án không tồn tại: $ProjectPath"
        exit 1
    }
}

# Kết quả kiểm tra
$checkResults = @{}

# Thực hiện các kiểm tra
$checkResults["XAMPP"] = Test-Xampp
$checkResults["PHP"] = Test-PHP
$checkResults["SQL Server Drivers"] = Test-SqlServerDrivers
$checkResults["Composer"] = Test-Composer
$checkResults["Project Structure"] = Test-ProjectStructure -Path $ProjectPath
$checkResults["Dependencies"] = Test-Dependencies -ProjectPath $ProjectPath
$checkResults[".env File"] = Test-EnvFile -ProjectPath $ProjectPath

# Kiểm tra Database nếu có mật khẩu
if (-not [string]::IsNullOrEmpty($SqlPassword)) {
    $checkResults["Database Connection"] = Test-DatabaseConnection -Server $SqlServer -User $SqlUser -Password $SqlPassword
} else {
    Write-Info "Bỏ qua kiểm tra Database (chưa cung cấp mật khẩu)"
    $checkResults["Database Connection"] = $true
}

$checkResults["Apache Service"] = Test-ApacheService

# Hiển thị báo cáo
Show-Summary -Results $checkResults

# Thoát với mã lỗi nếu có lỗi
$failedCount = ($checkResults.Values | Where-Object { $_ -eq $false }).Count
if ($failedCount -gt 0) {
    exit 1
} else {
    exit 0
}

