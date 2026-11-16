# Install myphp on Windows: irm https://raw.githubusercontent.com/crabphp/myphp/main/install.ps1 | iex
$ErrorActionPreference = 'Stop'
function Fail($message) { Write-Error "myphp: $message"; exit 1 }

if (-not [Environment]::Is64BitOperatingSystem -or $env:PROCESSOR_ARCHITECTURE -eq 'ARM64') {
    Fail 'only Windows x86-64 is supported for now'
}
$asset = 'myphp-windows-x86_64.exe'
$dir = if ($env:MYPHP_INSTALL_DIR) { $env:MYPHP_INSTALL_DIR } else { Join-Path $env:LOCALAPPDATA 'Programs\myphp' }
$base = 'https://github.com/crabphp/myphp/releases'
$url = if ($env:MYPHP_DOWNLOAD_URL) { $env:MYPHP_DOWNLOAD_URL }
    elseif ($env:MYPHP_VERSION -eq 'nightly') { "$base/download/nightly/$asset" }
    elseif ($env:MYPHP_VERSION) { "$base/download/v$($env:MYPHP_VERSION)/$asset" }
    else { "$base/latest/download/$asset" }

# A local path is accepted too, for testing a build before it is published.
function Fetch($from, $to) {
    if (Test-Path -LiteralPath $from) { Copy-Item -LiteralPath $from -Destination $to -Force }
    else { Invoke-WebRequest -Uri $from -OutFile $to -UseBasicParsing }
}

New-Item -ItemType Directory -Force -Path $dir | Out-Null
$temp = Join-Path $dir ".myphp-$PID.tmp"
$sum = "$temp.sha256"
try {
    try { Fetch $url $temp } catch { Fail "download failed: $url" }
    $expected = $null
    try { Fetch "$url.sha256" $sum; $expected = ((Get-Content -LiteralPath $sum -TotalCount 1) -split '\s+')[0] } catch {}
    if ($expected) {
        $actual = (Get-FileHash -LiteralPath $temp -Algorithm SHA256).Hash
        if ($actual -ne $expected) { Fail 'checksum mismatch' }
    } else {
        Write-Warning 'myphp: checksum unavailable; installing without verification'
    }
    Move-Item -LiteralPath $temp -Destination (Join-Path $dir 'myphp.exe') -Force
} finally {
    Remove-Item -LiteralPath $temp, $sum -Force -ErrorAction SilentlyContinue
}
$version = & (Join-Path $dir 'myphp.exe') version
if ($LASTEXITCODE -ne 0 -or "$version" -notmatch '^myphp \S+ \(PHP [0-9.]+\)$') { Fail 'the installed binary does not run' }
Write-Host $version

$userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
if (-not $env:MYPHP_NO_PATH -and (($userPath -split ';') -notcontains $dir)) {
    [Environment]::SetEnvironmentVariable('Path', (($userPath, $dir) -ne '' -join ';'), 'User')
    Write-Host "Added $dir to your PATH. Open a new terminal to use myphp."
}
