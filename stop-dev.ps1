# Stop all Node.js processes (Vite dev server)
Get-Process -Name "node" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue

# Remove the hot file to force production build usage
if (Test-Path "public\hot") {
    Remove-Item "public\hot" -Force
    Write-Host "✓ Removed hot file" -ForegroundColor Green
}

# Clear Laravel caches
php artisan optimize:clear

Write-Host "`n✓ Development servers stopped" -ForegroundColor Green
Write-Host "✓ Using production build from public/build/assets" -ForegroundColor Green
Write-Host "`nRefresh your browser at http://127.0.0.1:8000/login" -ForegroundColor Cyan
