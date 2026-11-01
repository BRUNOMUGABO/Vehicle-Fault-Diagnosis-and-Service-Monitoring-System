#!/bin/bash
# setup-docker.sh — Export XAMPP database and start Docker containers
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SQL_FILE="$SCRIPT_DIR/vfdsms.sql"

# ── 1. Find mysqldump ──────────────────────────────────────────
MYSQLDUMP=""
for candidate in \
    /Applications/XAMPP/xamppfiles/bin/mysqldump \
    /usr/local/mysql/bin/mysqldump \
    /opt/homebrew/bin/mysqldump \
    /usr/local/bin/mysqldump \
    $(which mysqldump 2>/dev/null); do
    if [ -x "$candidate" ]; then
        MYSQLDUMP="$candidate"
        break
    fi
done

# ── 2. Export database ────────────────────────────────────────
if [ ! -f "$SQL_FILE" ]; then
    if [ -z "$MYSQLDUMP" ]; then
        echo ""
        echo "ERROR: mysqldump not found."
        echo "Please export the database manually from phpMyAdmin or MySQL Workbench:"
        echo "  1. Open phpMyAdmin (http://localhost/phpmyadmin)"
        echo "  2. Select the 'vfdsms' database"
        echo "  3. Click Export → Go"
        echo "  4. Save the file as: $SQL_FILE"
        echo ""
        echo "Then run this script again, or run:"
        echo "  docker compose up --build -d"
        exit 1
    fi

    echo "Exporting XAMPP database..."
    "$MYSQLDUMP" -u root --password="" vfdsms > "$SQL_FILE"
    echo "Export saved to vfdsms.sql"
else
    echo "vfdsms.sql already exists, skipping export."
fi

# ── 3. Start Docker containers ────────────────────────────────
echo ""
echo "Starting Docker containers..."
cd "$SCRIPT_DIR"
docker compose up --build -d

echo ""
echo "Done! Open: http://localhost:8080/Vehicles%20Fault%20Diagnosis%20and%20Service%20Monitoring%20System/home.php"
echo "phpMyAdmin: http://localhost:8081"
