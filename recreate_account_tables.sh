#!/bin/bash
# Script Drop and Recreate Account Tables
# Database: SRO_VT_ACCOUNT
# Tables: TB_User, SK_Silk
# 
# WARNING: This script will DROP existing tables and all data!
# Make sure to backup your data before running this script.

SQL_SERVER="localhost,1433"
SQL_USER="sa"
SQL_PASSWORD="${SQL_PASSWORD:-MyStrongPass123}"

echo "=========================================="
echo "⚠️  WARNING: This script will DROP tables!"
echo "⚠️  All data in TB_User and SK_Silk will be LOST!"
echo "=========================================="
echo ""
read -p "Are you sure you want to continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "❌ Operation cancelled."
    exit 1
fi

echo ""
echo "🚀 Starting table recreation process..."

# Tìm container name
CONTAINER_NAME=$(docker ps --filter "ancestor=mcr.microsoft.com/mssql/server:2022-latest" --format "{{.Names}}" | head -n 1)

if [ -z "$CONTAINER_NAME" ]; then
    CONTAINER_NAME=$(docker ps --filter "ancestor=mcr.microsoft.com/azure-sql-edge" --format "{{.Names}}" | head -n 1)
fi

if [ -z "$CONTAINER_NAME" ]; then
    echo "❌ Không tìm thấy container SQL Server đang chạy."
    echo "   Vui lòng đảm bảo container SQL Server đã được khởi động."
    exit 1
fi

echo "✅ Tìm thấy container: $CONTAINER_NAME"

# Tìm sqlcmd
SQLCMD_PATH=""
if command -v sqlcmd &> /dev/null; then
    SQLCMD_PATH="sqlcmd"
    echo "✅ Tìm thấy sqlcmd trên máy local"
elif docker exec "$CONTAINER_NAME" test -f /opt/mssql-tools/bin/sqlcmd 2>/dev/null; then
    SQLCMD_PATH="docker exec -i $CONTAINER_NAME /opt/mssql-tools/bin/sqlcmd"
    echo "✅ Sử dụng sqlcmd trong container"
else
    echo "❌ Không tìm thấy sqlcmd. Vui lòng cài đặt sqlcmd hoặc đảm bảo container có sqlcmd."
    exit 1
fi

# Chạy script SQL
echo ""
echo "📝 Đang drop và recreate tables..."
echo ""

if [ "$SQLCMD_PATH" = "sqlcmd" ]; then
    # Use local sqlcmd
    sqlcmd -S "$SQL_SERVER" -U "$SQL_USER" -P "$SQL_PASSWORD" -d SRO_VT_ACCOUNT -i sql_scripts/recreate_account_tables.sql
else
    # Use docker exec sqlcmd
    docker exec -i "$CONTAINER_NAME" /opt/mssql-tools/bin/sqlcmd \
      -S localhost \
      -U "$SQL_USER" \
      -P "$SQL_PASSWORD" \
      -d SRO_VT_ACCOUNT \
      -i /dev/stdin < sql_scripts/recreate_account_tables.sql
fi

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Tables đã được drop và recreate thành công!"
    echo ""
    echo "📋 Tables created:"
    echo "   - TB_User (Account table)"
    echo "   - SK_Silk (Silk currency table)"
    echo ""
    echo "📋 Relationships:"
    echo "   - SK_Silk.JID → TB_User.JID (Foreign Key, CASCADE)"
    echo ""
    echo "📋 Indexes created:"
    echo "   - PK_TB_User (Primary Key on JID)"
    echo "   - IX_TB_User_StrUserID (Unique Index)"
    echo "   - IX_TB_User_Email (Index)"
    echo "   - PK_SK_Silk (Primary Key on JID)"
else
    echo ""
    echo "❌ Có lỗi xảy ra khi recreate tables."
    exit 1
fi

