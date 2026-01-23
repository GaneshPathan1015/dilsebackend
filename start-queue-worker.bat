@echo off
echo Starting Laravel Queue Worker...
echo ==========================================
echo.
cd /d "C:\xampp-php\htdocs\Dilse Project\dilsebackend"
echo Working Directory: %CD%
echo.
echo Starting queue worker for emails...
echo.
php artisan queue:work --queue=emails --tries=3 --timeout=60
echo.
echo Queue worker stopped. Press any key to exit...
pause