param (
    [string]$newVersion
)

if (-not $newVersion) {
    Write-Host "Vui lòng cung cấp version mới. VD: .\bump-version.ps1 3.0.12" -ForegroundColor Red
    exit
}

$mainFile = "wp-plugin-security.php"
$content = Get-Content $mainFile -Raw
$content = $content -replace "Version:\s+[\d\.]+", "Version:     $newVersion"
Set-Content $mainFile $content

Write-Host "Đã cập nhật $mainFile lên version $newVersion" -ForegroundColor Green
