#!/bin/bash

echo "🔧 SOKAB - Database Reset Script"
echo "=================================="
echo ""

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Config
DB_NAME="sokab_db"
DB_USER="root"
DB_PASS=""
SQL_FILE="sokab_complete.sql"

echo -e "${YELLOW}⚠️  WARNING: This will DROP and RECREATE the database!${NC}"
echo ""
read -p "Continue? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Aborted."
    exit 1
fi

echo ""
echo "Step 1: Dropping old database..."
mysql -u$DB_USER -p$DB_PASS -e "DROP DATABASE IF EXISTS $DB_NAME;" 2>/dev/null
echo -e "${GREEN}✅ Old database dropped${NC}"

echo ""
echo "Step 2: Creating new database..."
mysql -u$DB_USER -p$DB_PASS -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo -e "${GREEN}✅ New database created${NC}"

echo ""
echo "Step 3: Importing SQL file..."
mysql -u$DB_USER -p$DB_PASS $DB_NAME < $SQL_FILE

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ SQL import successful${NC}"
else
    echo -e "${RED}❌ SQL import failed${NC}"
    exit 1
fi

echo ""
echo "Step 4: Verifying database..."
mysql -u$DB_USER -p$DB_PASS $DB_NAME < VERIFY_DATABASE.sql

echo ""
echo -e "${GREEN}🎉 DATABASE RESET COMPLETE!${NC}"
echo ""
echo "Next steps:"
echo "1. Open browser: http://localhost/sokab"
echo "2. Login: admin / admin123"
echo "3. Test all menus"
echo ""
