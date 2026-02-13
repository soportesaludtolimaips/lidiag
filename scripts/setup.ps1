Param(
    [switch]$SkipNpm,
    [switch]$SkipMigrate,
    [switch]$SkipSeed
)

$ErrorActionPreference = "Stop"

Write-Host "== LIDiag setup (Windows/PowerShell) ==" -ForegroundColor Cyan

if (-not (Test-Path ".env")) {
    Write-Host "Creando .env desde .env.example" -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
}

Write-Host "Composer install" -ForegroundColor Yellow
composer install

Write-Host "App key" -ForegroundColor Yellow
php artisan key:generate

Write-Host "Storage links" -ForegroundColor Yellow
php artisan storage:link

if (-not $SkipMigrate) {
    Write-Host "Migraciones" -ForegroundColor Yellow
    php artisan migrate
}

if (-not $SkipSeed) {
    Write-Host "Seeders" -ForegroundColor Yellow
    php artisan db:seed
}

if (-not $SkipNpm) {
    Write-Host "NPM install" -ForegroundColor Yellow
    npm install

    Write-Host "Vite dev (usa otra terminal si quieres dejarlo corriendo)" -ForegroundColor Yellow
    Write-Host "  npm run dev"
}

Write-Host "OK" -ForegroundColor Green

