@echo off
title SkinExpert - Local Server
cls
echo ========================================================
echo    SKINEXPERT - SISTEM PAKAR KULIT WAJAH BERBASIS WEB
echo           Metode: Dynamic Backward Chaining
echo ========================================================
echo.
echo Menjalankan local development server pada:
echo http://localhost:8000
echo.
echo Tekan Ctrl+C di terminal ini untuk menghentikan server.
echo.

start http://localhost:8000

php -S localhost:8000
pause
