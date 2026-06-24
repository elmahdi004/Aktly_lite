$ErrorActionPreference = 'Stop'

$projectRoot = 'C:\Users\hp\Downloads\Compressed\Aktly-main'
$temp = Join-Path $projectRoot 'tmp_deploy_icons'

if (Test-Path $temp) {
    Remove-Item $temp -Recurse -Force
}

New-Item -ItemType Directory -Path (Join-Path $temp 'resources\js\services') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $temp 'public') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $temp 'app\Http\Controllers') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $temp 'database\migrations') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $temp 'resources\js') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $temp 'resources\js\components') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $temp 'app\Http\Controllers') -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $temp 'public\build') -Force | Out-Null

Copy-Item (Join-Path $projectRoot 'resources\js\services\DirigeantsService.vue') -Destination (Join-Path $temp 'resources\js\services\DirigeantsService.vue') -Force
Copy-Item (Join-Path $projectRoot 'resources\js\services\CessionPartsService.vue') -Destination (Join-Path $temp 'resources\js\services\CessionPartsService.vue') -Force
Copy-Item (Join-Path $projectRoot 'resources\js\i18n.js') -Destination (Join-Path $temp 'resources\js\i18n.js') -Force
Copy-Item (Join-Path $projectRoot 'resources\js\components\TopRightBar.vue') -Destination (Join-Path $temp 'resources\js\components\TopRightBar.vue') -Force
Copy-Item (Join-Path $projectRoot 'public\demissionner.png') -Destination (Join-Path $temp 'public\demissionner.png') -Force
Copy-Item (Join-Path $projectRoot 'public\valider.png') -Destination (Join-Path $temp 'public\valider.png') -Force
Copy-Item (Join-Path $projectRoot 'app\Http\Controllers\DirigeantsServiceController.php') -Destination (Join-Path $temp 'app\Http\Controllers\DirigeantsServiceController.php') -Force
Copy-Item (Join-Path $projectRoot 'app\Http\Controllers\ShareHolderController.php') -Destination (Join-Path $temp 'app\Http\Controllers\ShareHolderController.php') -Force
Copy-Item (Join-Path $projectRoot 'app\Http\Controllers\OpenAIController.php') -Destination (Join-Path $temp 'app\Http\Controllers\OpenAIController.php') -Force
New-Item -ItemType Directory -Path (Join-Path $temp 'app\Services') -Force | Out-Null
Copy-Item (Join-Path $projectRoot 'app\Services\OpenAIService.php') -Destination (Join-Path $temp 'app\Services\OpenAIService.php') -Force
Copy-Item (Join-Path $projectRoot 'database\migrations\2026_01_25_120000_add_gsm_to_dirigeants_services_table.php') -Destination (Join-Path $temp 'database\migrations\2026_01_25_120000_add_gsm_to_dirigeants_services_table.php') -Force
Copy-Item (Join-Path $projectRoot 'database\migrations\2026_01_25_130000_add_gsm_to_share_holders_table.php') -Destination (Join-Path $temp 'database\migrations\2026_01_25_130000_add_gsm_to_share_holders_table.php') -Force
Copy-Item (Join-Path $projectRoot 'public\build\*') -Destination (Join-Path $temp 'public\build') -Recurse -Force

& (Join-Path $projectRoot 'scripts\ftp_sync.ps1') -FtpHost 'ftp.cluster014.hosting.ovh.net' -User 'homedroprw' -Pass 'Momik19811981' -LocalRoot $temp -RemoteRoot '/legakteproj'
