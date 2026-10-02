# FlowFix Demo Startup Script
# Starts Laravel backend with correct PHP timeout settings + Vite frontend.
# Run from the repo root: .\start-demo.ps1

$PHP       = "C:\xampp\php\php.exe"
$BACKEND   = "$PSScriptRoot\backend"
$FRONTEND  = "$PSScriptRoot\frontend"
$PHP_INI   = "$BACKEND\runtime\php\conf.d\uploads.ini"

Write-Host "=== FlowFix Demo ===" -ForegroundColor Cyan
Write-Host "Starting Laravel backend on http://localhost:8000 ..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoProfile -Command `"& '$PHP' -c '$PHP_INI' '$BACKEND\artisan' serve --host=127.0.0.1 --port=8000`"" -WindowStyle Normal
Write-Host "Starting Vite frontend on http://localhost:5173 ..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoProfile -Command `"Set-Location '$FRONTEND'; npm run dev`"" -WindowStyle Normal
Write-Host ""
Write-Host "Open http://localhost:5173" -ForegroundColor Green
Write-Host "Student:  mahasiswa@flowfix.test  / FlowFix-demo-2026!"
Write-Host "Reviewer: peninjau@flowfix.test   / FlowFix-demo-2026!"
