# ============================================
# Script Khởi Động Services
# ============================================

param(
    [string]$XamppPath = "C:\xampp",
    [switch]$StartApache = $true,
    [switch]$StartSqlServer = $false
)

function Write-Step {
    param([string]$Message)
    Write-Host "[STEP] $Message" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "[✓] $Message" -ForegroundColor Green
}

function Write-Error {
    param([string]$Message)
    Write-Host "[✗] $Message" -ForegroundColor Red
}

function Write-Info {
    param([string]$Message)
    Write-Host "[i] $Message" -ForegroundColor Gray
}

# Khởi động Apache từ XAMPP
function Start-XamppApache {
    Write-Step "Khởi động Apache..."
    
    $apacheExe = "$XamppPath\apache\bin\httpd.exe"
    if (-not (Test-Path $apacheExe)) {
        Write-Error "Không tìm thấy Apache tại: $apacheExe"
        return $false
    }
    
    # Kiểm tra Apache đã chạy chưa
    $apacheProcess = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
    if ($apacheProcess) {
        Write-Success "Apache đã đang chạy (PID: $($apacheProcess.Id))"
        return $true
    }
    
    # Kiểm tra service Apache
    $apacheService = Get-Service -Name "Apache*" -ErrorAction SilentlyContinue
    if ($apacheService) {
        if ($apacheService.Status -eq "Running") {
            Write-Success "Apache Service đang chạy"
            return $true
        } else {
            Write-Info "Đang khởi động Apache Service..."
            try {
                Start-Service $apacheService.Name
                Start-Sleep -Seconds 3
                if ((Get-Service $apacheService.Name).Status -eq "Running") {
                    Write-Success "Đã khởi động Apache Service"
                    return $true
                }
            } catch {
                Write-Error "Không thể khởi động Apache Service: $_"
            }
        }
    }
    
    # Khởi động Apache từ XAMPP
    Write-Info "Đang khởi động Apache từ XAMPP..."
    try {
        Start-Process -FilePath $apacheExe -WindowStyle Hidden
        Start-Sleep -Seconds 5
        
        $apacheProcess = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
        if ($apacheProcess) {
            Write-Success "Đã khởi động Apache (PID: $($apacheProcess.Id))"
            return $true
        } else {
            Write-Error "Không thể khởi động Apache"
            return $false
        }
    } catch {
        Write-Error "Lỗi khi khởi động Apache: $_"
        return $false
    }
}

# Khởi động SQL Server (nếu cần)
function Start-SqlServerService {
    Write-Step "Kiểm tra SQL Server Service..."
    
    $sqlServices = @(
        "MSSQLSERVER",
        "MSSQL`$SQLEXPRESS",
        "MSSQL`$*"
    )
    
    $found = $false
    foreach ($serviceName in $sqlServices) {
        $service = Get-Service -Name $serviceName -ErrorAction SilentlyContinue
        if ($service) {
            $found = $true
            if ($service.Status -eq "Running") {
                Write-Success "SQL Server Service '$($service.Name)' đang chạy"
                return $true
            } else {
                Write-Info "Đang khởi động SQL Server Service '$($service.Name)'..."
                try {
                    Start-Service $service.Name
                    Start-Sleep -Seconds 5
                    if ((Get-Service $service.Name).Status -eq "Running") {
                        Write-Success "Đã khởi động SQL Server Service"
                        return $true
                    }
                } catch {
                    Write-Error "Không thể khởi động SQL Server Service: $_"
                }
            }
        }
    }
    
    if (-not $found) {
        Write-Info "Không tìm thấy SQL Server Service. Có thể SQL Server đang chạy ở chế độ khác."
    }
    
    return $true
}

# Kiểm tra port 1433
function Test-SqlServerPort {
    Write-Step "Kiểm tra SQL Server Port 1433..."
    
    $connection = Test-NetConnection -ComputerName localhost -Port 1433 -WarningAction SilentlyContinue
    if ($connection.TcpTestSucceeded) {
        Write-Success "SQL Server đang lắng nghe trên port 1433"
        return $true
    } else {
        Write-Error "SQL Server không lắng nghe trên port 1433"
        return $false
    }
}

# ============================================
# MAIN
# ============================================

Write-Host @"
========================================
  KHỞI ĐỘNG SERVICES
========================================
"@ -ForegroundColor Cyan

$results = @{}

if ($StartApache) {
    $results["Apache"] = Start-XamppApache
}

if ($StartSqlServer) {
    $results["SQL Server Service"] = Start-SqlServerService
    $results["SQL Server Port"] = Test-SqlServerPort
}

Write-Host "`n" -NoNewline
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "   KẾT QUẢ KHỞI ĐỘNG" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

foreach ($key in $results.Keys) {
    $status = if ($results[$key]) { "✓" } else { "✗" }
    $color = if ($results[$key]) { "Green" } else { "Red" }
    Write-Host "  $status $key" -ForegroundColor $color
}

$failed = ($results.Values | Where-Object { $_ -eq $false }).Count
if ($failed -eq 0) {
    Write-Host "`n[✓] Tất cả services đã sẵn sàng!" -ForegroundColor Green
} else {
    Write-Host "`n[!] Có $failed service chưa sẵn sàng." -ForegroundColor Yellow
}

