@echo off
REM ============================================
REM Script Khởi Động Services
REM ============================================

echo ========================================
echo   KHOI DONG SERVICES
echo ========================================
echo.

REM Kiểm tra quyền Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Script can chay voi quyen Administrator!
    pause
    exit /b 1
)

REM Chạy PowerShell script
powershell -ExecutionPolicy Bypass -File "%~dp0start-services.ps1" -StartApache -StartSqlServer

pause

