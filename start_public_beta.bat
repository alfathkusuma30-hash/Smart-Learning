@echo off
title PT ANTAM Tbk - Smart Learning Portal (Public Beta)
color 0A
cls
echo ==============================================================
echo    PT ANTAM Tbk - SMART LEARNING PORTAL [PUBLIC BETA]
echo ==============================================================
echo.
echo Menyiapkan akses online publik agar sistem bisa diakses
echo dari HP, laptop lain, maupun oleh atasan di luar jaringan lokal.
echo.
echo Memeriksa modul Cloudflare Tunnel...

cd /d "%~dp0"

:: 1. Jika cloudflared.exe sudah ada di folder
if exist "cloudflared.exe" goto launch_tunnel

:: 2. Jika cloudflared ada di PATH sistem
where cloudflared >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    set CLOUDFLARED_CMD=cloudflared
    goto launch_tunnel
)

:: 3. Unduh menggunakan curl bawaan Windows
echo.
echo [INFO] Mengunduh modul Cloudflare Tunnel (cloudflared.exe)...
echo Harap tunggu beberapa detik hingga proses unduh selesai...
echo.

curl.exe -L --progress-bar "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -o "cloudflared.exe"

if not exist "cloudflared.exe" (
    echo.
    echo [INFO] Mencoba metode unduh cadangan PowerShell...
    powershell -NoProfile -Command "Start-BitsTransfer -Source 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe' -Destination 'cloudflared.exe'"
)

if not exist "cloudflared.exe" (
    echo.
    echo ==============================================================
    echo [PERHATIAN] Gagal mengunduh cloudflared otomatis.
    echo Silakan unduh manual file cloudflared-windows-amd64.exe dari:
    echo https://github.com/cloudflare/cloudflared/releases/latest
    echo lalu ganti namanya menjadi cloudflared.exe dan letakkan di:
    echo %~dp0
    echo ==============================================================
    echo.
    pause
    exit /b
)

:launch_tunnel
cls
echo ==============================================================
echo    PT ANTAM Tbk - SMART LEARNING PORTAL [PUBLIC BETA AKTIF]
echo ==============================================================
echo.
echo [STATUS: ONLINE]
echo.
echo Petunjuk untuk dibagikan ke atasan / penguji:
echo 1. Salin tautan HTTPS "https://*.trycloudflare.com" di bawah ini.
echo 2. Tambahkan "/Smart_learning/" di belakang tautan, contoh:
echo    https://xxxx.trycloudflare.com/Smart_learning/
echo.
echo CATATAN PENTING:
echo - Jangan tutup jendela CMD ini selama Anda ingin sistem tetap online!
echo - Pastikan XAMPP (Apache & MySQL) tetap berjalan (hijau).
echo ==============================================================
echo.

if exist "cloudflared.exe" (
    cloudflared.exe tunnel --protocol http2 --edge-ip-version 4 --url http://127.0.0.1:80
) else (
    cloudflared tunnel --protocol http2 --edge-ip-version 4 --url http://127.0.0.1:80
)

pause
