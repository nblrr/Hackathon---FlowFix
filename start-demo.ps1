# FlowFix Demo Startup Script
# Run from repo root: .\start-demo.ps1

$PHP        = "C:\xampp\php\php.exe"
$BACKEND    = "$PSScriptRoot\backend"
$FRONTEND   = "$PSScriptRoot\frontend"

Write-Host "=== FlowFix Demo ===" -ForegroundColor Cyan

# Backend: use -d flags (NOT -c) to keep all XAMPP extensions (mbstring etc.) loaded
Write-Host "Starting Laravel backend on http://localhost:8000 ..." -ForegroundColor Yellow
Start-Process powershell -WindowStyle Minimized -ArgumentList @(
    "-NoProfile", "-Command",
    "Set-Location '$BACKEND'; & '$PHP' -dmax_execution_time=0 -ddefault_socket_timeout=300 '$BACKEND\artisan' serve --host=127.0.0.1 --port=8000"
)

# Frontend
Write-Host "Starting Vite frontend on http://localhost:5173 ..." -ForegroundColor Yellow
Start-Process powershell -WindowStyle Minimized -ArgumentList @(
    "-NoProfile", "-Command",
    "Set-Location '$FRONTEND'; npm run dev"
)

Start-Sleep -Seconds 8
Write-Host ""
Write-Host "Open: http://localhost:5173" -ForegroundColor Green
Write-Host "Student:  mahasiswa@flowfix.test  / FlowFix-demo-2026!"
Write-Host "Reviewer: peninjau@flowfix.test   / FlowFix-demo-2026!"
