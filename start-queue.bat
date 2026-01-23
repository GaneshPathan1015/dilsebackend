@echo off
cd /d "C:\xampp-php\htdocs\Dilse Project\dilsebackend"
php artisan queue:work --tries=3 --timeout=90
pause
