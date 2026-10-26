@echo off
title SkinExpert - Flask Server
cls
echo ========================================================
echo    SKINEXPERT - SISTEM PAKAR KULIT WAJAH (FLASK)
echo           Metode: Backward Chaining
echo ========================================================
echo.
echo Menjalankan local server Flask pada:
echo http://127.0.0.1:5000
echo.
echo Tekan Ctrl+C untuk menghentikan server.
echo.

start http://127.0.0.1:5000

python app.py
pause
