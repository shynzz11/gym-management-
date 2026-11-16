<#
.SYNOPSIS
    Deploys the current pushed commit to the shared host.

.DESCRIPTION
    1. Checks the working tree is clean and in sync with GitHub (the server pulls from GitHub,
       so the frontend built here must match the code it will run).
    2. Builds the frontend (shared hosting has no Node).
    3. Uploads the build and runs deploy.sh on the server.

    SSH details are read from scripts/deploy.config.psd1 (not committed).
    Copy scripts/deploy.config.example.psd1 to create it.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts/deploy.ps1
#>

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
$configPath = Join-Path $PSScriptRoot 'deploy.config.psd1'

if (-not (Test-Path $configPath)) {
    throw "Missing $configPath. Copy deploy.config.example.psd1 to deploy.config.psd1 and fill in your SSH details."
}

$config = Import-PowerShellDataFile $configPath
$target = "$($config.SshUser)@$($config.SshHost)"
$archive = Join-Path $env:TEMP 'iron-nest-build.tgz'

function Assert-LastExitCode([string] $step) {
    if ($LASTEXITCODE -ne 0) {
        throw "$step failed (exit code $LASTEXITCODE)."
    }
}

Push-Location $root
try {
    Write-Host '==> Checking git state'
    git fetch --quiet
    Assert-LastExitCode 'git fetch'

    if (git status --porcelain) {
        throw 'You have uncommitted changes. Commit and push them before deploying.'
    }

    $localCommit = git rev-parse HEAD
    $remoteCommit = git rev-parse '@{u}'
    if ($localCommit -ne $remoteCommit) {
        throw 'Your branch is not in sync with GitHub. Push (or pull) first, then deploy.'
    }

    Write-Host '==> Building frontend'
    npm run build
    Assert-LastExitCode 'npm run build'

    Write-Host '==> Packing build'
    if (Test-Path $archive) {
        Remove-Item $archive -Force
    }
    # Windows' own bsdtar; Git's GNU tar would read "C:" in the path as a remote host.
    & "$env:SystemRoot\System32\tar.exe" -czf $archive -C public/build .
    Assert-LastExitCode 'tar'

    Write-Host "==> Uploading build to $target"
    scp -P $config.SshPort $archive "${target}:iron-nest-build.tgz"
    Assert-LastExitCode 'scp'

    Write-Host '==> Running deploy.sh on the server'
    ssh -p $config.SshPort $target "cd $($config.AppPath) && bash deploy.sh"
    Assert-LastExitCode 'Remote deploy'

    Write-Host '==> Done'
}
finally {
    Pop-Location
    if (Test-Path $archive) {
        Remove-Item $archive -Force
    }
}
