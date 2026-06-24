param(
    [string]$FtpHost = "ftp.cluster014.hosting.ovh.net",
    [string]$User,
    [string]$Pass,
    [string]$LocalRoot = "C:\Users\hp\Downloads\Compressed\Aktly-main",
    [string]$RemoteRoot = "/legakteproj"
)

# Allow env vars to avoid passing credentials in clear text on the command line
if (-not $User) { $User = $env:FTP_USER }
if (-not $Pass) { $Pass = $env:FTP_PASS }

if (-not $User -or -not $Pass) {
    if (-not $User) {
        $User = Read-Host "FTP user"
    }
    if (-not $Pass) {
        $securePass = Read-Host "FTP password" -AsSecureString
        $ptr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePass)
        try {
            $Pass = [System.Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)
        } finally {
            [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr)
        }
    }
}

if (-not $User -or -not $Pass) {
    Write-Error "FTP credentials are required. Provide -User/-Pass or set FTP_USER/FTP_PASS."
    exit 1
}

$excludeTop = @(".git", "node_modules")
$excludeExact = @(
    ".env",
    "aktly-deploy.zip",
    "aktly_deploy.tar.gz",
    "deploy\upload.txt",
    "deploy\README-deploy.md",
    "scripts\ftp_sync.ps1",
    "scripts\make_zip.php"
)

function Should-Exclude([string]$relativePath) {
    $normalized = $relativePath.TrimStart([char[]]('\','/'))
    foreach ($top in $excludeTop) {
        if ($normalized -like "$top*" ) { return $true }
    }
    foreach ($exact in $excludeExact) {
        if ($normalized -ieq $exact) { return $true }
    }
    return $false
}

function New-FtpDirectory([string]$uri, [System.Net.NetworkCredential]$cred) {
    try {
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $request.Credentials = $cred
        $request.UseBinary = $true
        $request.UsePassive = $true
        $request.KeepAlive = $false
        $response = $request.GetResponse()
        $response.Close()
    } catch {
        # ignore "directory exists" errors
        if ($_.Exception.Message -notmatch "550" -and $_.Exception.Message -notmatch "exists") {
            Write-Error "Failed to create dir $uri : $($_.Exception.Message)"
        }
    }
}

function Upload-FtpFile([string]$localPath, [string]$uri, [System.Net.NetworkCredential]$cred) {
    $request = [System.Net.FtpWebRequest]::Create($uri)
    $request.Method = [System.Net.WebRequestMethods+Ftp]::UploadFile
    $request.Credentials = $cred
    $request.UseBinary = $true
    $request.UsePassive = $true
    $request.KeepAlive = $false

    $sourceStream = [System.IO.File]::OpenRead($localPath)
    $targetStream = $request.GetRequestStream()
    $buffer = New-Object byte[] 8192
    while (($read = $sourceStream.Read($buffer, 0, $buffer.Length)) -gt 0) {
        $targetStream.Write($buffer, 0, $read)
    }
    $targetStream.Close()
    $sourceStream.Close()
    $response = $request.GetResponse()
    $response.Close()
}

$cred = New-Object System.Net.NetworkCredential($User, $Pass)
$localRootPath = (Resolve-Path $LocalRoot).Path

Write-Output "FTP sync started: $localRootPath -> ftp://$FtpHost$RemoteRoot"
Write-Output "Scanning local files..."

# Ensure remote root exists
New-FtpDirectory ("ftp://$FtpHost$RemoteRoot") $cred

# Create directories first
$dirs = Get-ChildItem -Path $localRootPath -Recurse -Directory -Force
$dirCount = 0
foreach ($dir in $dirs) {
    $rel = $dir.FullName.Substring($localRootPath.Length).TrimStart("\\")
    if (Should-Exclude $rel) { continue }
    $remotePath = ($RemoteRoot.TrimEnd('/') + '/' + ($rel -replace "\\", "/")).TrimEnd('/')
    $uri = "ftp://$FtpHost$remotePath"
    New-FtpDirectory $uri $cred
    $dirCount++
    if ($dirCount % 200 -eq 0) {
        Write-Output "Created $dirCount directories..."
    }
}
Write-Output "Directory creation done. Total directories: $dirCount"

# Upload files
$files = Get-ChildItem -Path $localRootPath -Recurse -File -Force
$count = 0
Write-Output "Uploading files..."
foreach ($file in $files) {
    $rel = $file.FullName.Substring($localRootPath.Length).TrimStart("\\")
    if (Should-Exclude $rel) { continue }
    $remotePath = ($RemoteRoot.TrimEnd('/') + '/' + ($rel -replace "\\", "/"))
    $uri = "ftp://$FtpHost$remotePath"
    Upload-FtpFile $file.FullName $uri $cred
    $count++
    if ($count % 200 -eq 0) {
        Write-Output "Uploaded $count files..."
    }
}

Write-Output "FTP sync completed. Files uploaded: $count"
