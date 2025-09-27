$ErrorActionPreference = 'Stop'

Write-Host "Starting selective restore: keep only today's edits, revert other tracked changes to HEAD." -ForegroundColor Cyan

function Is-Today($path) {
    try {
        if (-not (Test-Path -LiteralPath $path)) { return $false }
        $t = (Get-Item -LiteralPath $path).LastWriteTime.Date
        return ($t -eq (Get-Date).Date)
    } catch {
        return $false
    }
}

# Collect tracked file changes using git plumbing to avoid parsing porcelain lines with special chars
$modifiedWT = @(git ls-files -m | Where-Object { $_ -ne $null -and $_.Trim() -ne '' })
$deletedWT  = @(git ls-files -d | Where-Object { $_ -ne $null -and $_.Trim() -ne '' })
$stagedAdded = @(git diff --name-only --cached --diff-filter=A | Where-Object { $_ -ne $null -and $_.Trim() -ne '' })
$stagedMod   = @(git diff --name-only --cached --diff-filter=M | Where-Object { $_ -ne $null -and $_.Trim() -ne '' })

$toRestore = New-Object System.Collections.ArrayList
$toUnstage = New-Object System.Collections.ArrayList

# Worktree modified tracked files: restore if not modified today
foreach ($f in $modifiedWT) {
    $p = $f.Trim('"')
    if (-not (Is-Today -path $p)) { [void]$toRestore.Add($p) }
}

# Staged modified tracked files
foreach ($f in $stagedMod) {
    $p = $f.Trim('"')
    if (-not (Is-Today -path $p)) { [void]$toRestore.Add($p) }
}

# Deleted tracked files: restore (treat as removed code to bring back)
foreach ($f in $deletedWT) {
    $p = $f.Trim('"')
    if (-not $toRestore.Contains($p)) { [void]$toRestore.Add($p) }
}

# Staged newly added files: if not from today, unstage (keeps the file untracked)
foreach ($f in $stagedAdded) {
    $p = $f.Trim('"')
    if (-not (Is-Today -path $p)) { [void]$toUnstage.Add($p) }
}

if ($toRestore.Count -gt 0) {
    Write-Host "Reverting tracked files not modified today (and restoring deleted ones):" -ForegroundColor Yellow
    $toRestore | ForEach-Object { Write-Host " - $_" }
    git restore --worktree --staged --source=HEAD -- @toRestore
} else {
    Write-Host "No tracked files to revert." -ForegroundColor Green
}

if ($toUnstage.Count -gt 0) {
    Write-Host "Unstaging newly added files not from today (will remain in working directory):" -ForegroundColor Yellow
    $toUnstage | ForEach-Object { Write-Host " - $_" }
    git restore --staged -- @toUnstage
} else {
    Write-Host "No added files to unstage." -ForegroundColor Green
}

Write-Host "Done. Current status:" -ForegroundColor Cyan
git status -uno
