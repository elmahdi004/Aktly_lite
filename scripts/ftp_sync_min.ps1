param(
    [string]$FtpHost = "ftp.cluster014.hosting.ovh.net",
    [string]$User,
    [string]$Pass,
    [string]$LocalRoot = "C:\Users\hp\Downloads\Compressed\Aktly-main",
    [string]$RemoteRoot = "/legakteproj"
)

$ErrorActionPreference = "Stop"

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
        if ($_.Exception.Message -notmatch "550" -and $_.Exception.Message -notmatch "exists") {
            Write-Error "Failed to create dir $uri : $($_.Exception.Message)"
        }
    }
}

function Get-FtpUri([string]$ftpHost, [string]$path) {
    $trimmed = $path.TrimStart('/')
    if ([string]::IsNullOrWhiteSpace($trimmed)) {
        return "ftp://$ftpHost/"
    }
    $segments = $trimmed -split '/' | ForEach-Object { [System.Uri]::EscapeDataString($_) }
    $safePath = ($segments -join '/')
    return "ftp://$ftpHost/$safePath"
}

function Ensure-FtpDirectory([string]$ftpHost, [string]$remotePath, [System.Net.NetworkCredential]$cred) {
    $normalized = $remotePath.Trim('/')
    if ([string]::IsNullOrWhiteSpace($normalized)) {
        return
    }
    $segments = $normalized -split '/'
    $current = ''
    foreach ($seg in $segments) {
        $current = if ($current) { "$current/$seg" } else { $seg }
        $uri = Get-FtpUri $ftpHost $current
        New-FtpDirectory $uri $cred
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

# Minimal deploy list (update as needed)
$paths = @(
    "routes\api.php",
    "routes\web.php",
    "app\Http\Controllers\AuthController.php",
    "app\Http\Controllers\DemandeController.php",
    "app\Http\Controllers\DirigeantsServiceController.php",
    "app\Http\Controllers\KboApiCallController.php",
    "app\Http\Controllers\ProfileController.php",
    "app\Http\Controllers\ClaudeController.php",
    "app\Http\Controllers\IdAnalyzerController.php",
    "app\Http\Controllers\VerificationController.php",
    "app\Http\Controllers\ShareHolderController.php",
    "app\Http\Controllers\gestionDesPdsController.php",
    "app\Http\Middleware\VeriffMiddleware.php",
    "app\Http\Middleware\VerifyCsrfToken.php",
    "app\Http\Requests\UpdateDemandeRequest.php",
    "app\Services\IdAnalyzerService.php",
    "app\Services\ClaudeService.php",
    "app\Services\OpenAIService.php",
    "app\Services\PdfFieldsService.php",
    "app\Services\VeriffService.php",
    "config\services.php",
    "resources\js\App.vue",
    "resources\js\auth\idanalyzerUser.vue",
    "resources\js\components\TopRightBar.vue",
    "resources\js\form\Index.vue",
    "resources\js\i18n.js",
    "resources\js\routes.js",
    "resources\js\services\CessionPartsService.vue",
    "resources\js\services\DirigeantsService.vue",
    "resources\js\steps\Step2.vue",
    "resources\js\steps\Step6.vue",
    "resources\lang\fr\auth.php",
    "resources\lang\nl\auth.php",
    "resources\views\idanalyzer",
    "resources\views\pdfs\fr\formulaire1.blade.php",
    "resources\views\pdfs\fr\formulaire2.blade.php",
    "resources\views\pdfs\fr\attestation_identite_1.blade.php",
    "public\build",
    "public\build\manifest.json"
)

Write-Output "FTP minimal sync started: $localRootPath -> ftp://$FtpHost$RemoteRoot"

Ensure-FtpDirectory $FtpHost ($RemoteRoot.Trim('/')) $cred

foreach ($rel in $paths) {
    $localPath = Join-Path $localRootPath $rel
    if (-not (Test-Path $localPath)) {
        Write-Warning "Skip missing: $rel"
        continue
    }

    $items = @()
    if (Test-Path $localPath -PathType Container) {
        $items = Get-ChildItem -Path $localPath -Recurse -File -Force
    } else {
        $items = @((Get-Item -Path $localPath))
    }

    foreach ($file in $items) {
        $fileRel = $file.FullName.Substring($localRootPath.Length).TrimStart("\\")
        $remoteFile = ($RemoteRoot.TrimEnd('/') + '/' + ($fileRel -replace "\\", "/"))
        $remoteDir = ($remoteFile -replace '/[^/]+$','')

        Ensure-FtpDirectory $FtpHost ($remoteDir.Trim('/')) $cred

        $uri = Get-FtpUri $FtpHost $remoteFile
        Upload-FtpFile $file.FullName $uri $cred
        Write-Output "Uploaded: $fileRel"
    }
}

Write-Output "FTP minimal sync done."