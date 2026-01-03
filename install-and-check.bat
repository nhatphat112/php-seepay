@echo off
REM ============================================
REM Script Batch đơn giản để chạy PowerShell script
REM ============================================

echo ========================================
echo   PHP-SEEPAY AUTO INSTALL ^& CHECK
echo ========================================
echo.

REM Kiểm tra quyền Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Script can chay voi quyen Administrator!
    echo Vui long mo Command Prompt voi quyen Administrator.
    pause
    exit /b 1
)

REM Lấy đường dẫn hiện tại
set "PROJECT_PATH=%~dp0"
set "PROJECT_PATH=%PROJECT_PATH:~0,-1%"

echo [INFO] Duong dan du an: %PROJECT_PATH%
echo.

REM Kiểm tra PowerShell
powershell -Command "Get-Host" >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] PowerShell khong tim thay!
    pause
    exit /b 1
)

REM Chạy PowerShell script
echo [INFO] Dang chay script kiem tra...
echo.

powershell -ExecutionPolicy Bypass -File "%~dp0install-and-check.ps1" -ProjectPath "%PROJECT_PATH%" -AutoStart

if %errorLevel% equ 0 (
    echo.
    echo [SUCCESS] Tat ca kiem tra deu thanh cong!
) else (
    echo.
    echo [WARNING] Co mot so kiem tra that bai. Vui long xem chi tiet o tren.
)

echo.
pause

