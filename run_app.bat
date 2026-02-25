@echo off
echo ===================================================
echo   Waste Segregation Monitoring System - Launcher
echo ===================================================
echo.
echo 1. Start MySQL in XAMPP first!
echo 2. If this is your first time, type 'init' to setup database.
echo 3. Press Enter to start the server.
echo.
set /p choice="Type 'init' and press Enter to reset DB, or just press Enter to run: "

if "%choice%"=="init" (
    echo.
    echo Initializing Database...
    php setup_db.php
    echo Database setup complete.
    echo.
)

echo Starting PHP Server at http://localhost:8000...
start http://localhost:8000
php -S localhost:8000
