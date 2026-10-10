# FlowFix Demo Startup Script
# Run from repo root: .\start-demo.ps1
#
# Konfigurasi disimpan di .env di root repo (salin dari .env.example, lalu isi nilainya).
# Script ini otomatis membaca .env — tidak perlu set env var manual.

# Auto-load .env dari root repo
$envFile = Join-Path $PSScriptRoot ".env"
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        $line = $_.Trim()
        if ($line -and -not $line.StartsWith("#") -and $line -match "^([^=]+)=(.*)$") {
            $k = $Matches[1].Trim(); $v = $Matches[2].Trim()
            [System.Environment]::SetEnvironmentVariable($k, $v, "Process")
        }
    }
    Write-Host "Loaded .env" -ForegroundColor DarkGray
} else {
    Write-Host "WARNING: .env not found. Copy .env.example to .env and fill in values." -ForegroundColor Red
}

$PHP        = if ($env:FLOWFIX_PHP)      { $env:FLOWFIX_PHP }      else { "C:\xampp\php\php.exe" }
$PYTHON     = if ($env:FLOWFIX_PYTHON)   { $env:FLOWFIX_PYTHON }   else { "python" }
$LLM_HOST   = if ($env:FLOWFIX_LLM_HOST) { $env:FLOWFIX_LLM_HOST } else { "127.0.0.1" }
$BACKEND    = "$PSScriptRoot\backend"
$FRONTEND   = "$PSScriptRoot\frontend"

Write-Host "=== FlowFix Demo ===" -ForegroundColor Cyan

# Langflow: SSRF allowed hosts wajib agar LLM local bisa diakses
Write-Host "Starting Langflow on http://localhost:7860 ..." -ForegroundColor Yellow
Start-Process powershell -WindowStyle Minimized -ArgumentList @(
    "-NoProfile", "-Command",
    "`$env:LANGFLOW_SSRF_ALLOWED_HOSTS = '$LLM_HOST'; & '$PYTHON' -m langflow run --host 127.0.0.1 --port 7860"
)

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
