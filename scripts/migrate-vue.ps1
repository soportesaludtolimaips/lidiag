param(
    [Parameter(Mandatory)]
    [string]$Path,
    [switch]$DryRun
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $Path)) {
    Write-Error "File not found: $Path"
    exit 1
}

$content = Get-Content $Path -Raw

if ($content -match '<script\s+setup') {
    Write-Warning "Already <script setup>: $Path"
    exit 0
}

$templateMatch = [regex]::Match($content, '(?s)(<template>.*?</template>)')
if (-not $templateMatch.Success) {
    Write-Error "No <template> found in $Path"
    exit 1
}
$templateBlock = $templateMatch.Groups[1].Value

$scriptMatch = [regex]::Match($content, '(?s)<script>(.*?)</script>')
if (-not $scriptMatch.Success) {
    Write-Error "No <script> block found in $Path"
    exit 1
}
$scriptInner = $scriptMatch.Groups[1].Value

$styleBlocks = @()
$styleMatches = [regex]::Matches($content, '(?s)(<style.*?>.*?</style>)')
foreach ($m in $styleMatches) { $styleBlocks += $m.Groups[1].Value }

# Import lines
$importLines = @()
foreach ($line in ($scriptInner -split "\r\n|\n")) {
    if ($line -match '^\s*import\s+') {
        $importLines += $line
    }
}

# Validate export default exists
$exportMatch = [regex]::Match($scriptInner, '(?s)export\s+default\s*\{')
if (-not $exportMatch.Success) {
    Write-Error "No export default found in $Path"
    exit 1
}

# Get the export default object content (balanced braces)
$exportStart = $exportMatch.Index + $exportMatch.Length - 1  # position of {
$depth = 1
$pos = $exportStart + 1
while ($pos -lt $scriptInner.Length -and $depth -gt 0) {
    $ch = $scriptInner[$pos]
    if ($ch -eq '{') { $depth++ }
    if ($ch -eq '}') { $depth-- }
    $pos++
}
$exportObject = $scriptInner.Substring($exportStart, $pos - $exportStart)
Write-Host "DBG: export object length = $($exportObject.Length)" -ForegroundColor Cyan

# --- Helper: extract a top-level key from the export object ---
function Get-ExportKey($obj, $key) {
    $re = [regex]("(?s)(?:^|[\s,]+)$key\s*:\s*")
    $m = $re.Match($obj)
    if (-not $m.Success) { return $null }
    $start = $m.Index + $m.Length
    # Skip whitespace
    while ($start -lt $obj.Length -and $obj[$start] -match '\s') { $start++ }
    if ($start -ge $obj.Length) { return $null }
    $firstChar = $obj[$start]
    # Determine the extent based on first char
    if ($firstChar -eq '{') {
        # Object - find matching closing brace
        $d = 1
        $p = $start + 1
        while ($p -lt $obj.Length -and $d -gt 0) {
            if ($obj[$p] -eq '{') { $d++ }
            if ($obj[$p] -eq '}') { $d-- }
            $p++
        }
        return $obj.Substring($start, $p - $start)
    } elseif ($firstChar -eq '[') {
        # Array - find matching closing bracket
        $d = 1
        $p = $start + 1
        while ($p -lt $obj.Length -and $d -gt 0) {
            if ($obj[$p] -eq '[') { $d++ }
            if ($obj[$p] -eq ']') { $d-- }
            $p++
        }
        return $obj.Substring($start, $p - $start)
    } elseif ($firstChar -eq "'" -or $firstChar -eq '"') {
        # String - find closing quote
        $q = $firstChar
        $p = $start + 1
        while ($p -lt $obj.Length) {
            if ($obj[$p] -eq $q) { break }
            $p++
        }
        return $obj.Substring($start, $p - $start + 1)
    } else {
        # Value until comma or closing brace
        $p = $start
        while ($p -lt $obj.Length -and $obj[$p] -notmatch '[\},]') { $p++ }
        $val = $obj.Substring($start, $p - $start)
        return $val.Trim()
    }
}

# --- Helper: extract methods and lifecycle hooks from export object ---
# Returns a hashtable of { name -> body }
function Get-TopLevelMethods($obj) {
    $result = @{}
    # Match any function-like entry: key(...) { ... }
    $re = [regex]('(?s)(?:^|[\s,]+)(async\s+)?(mounted|beforeUnmount|created|beforeMount|beforeCreate|unmounted|activated|deactivated|updated|beforeUpdate)\s*\(\s*\)\s*\{')
    $m = $re.Match($obj)
    if (-not $m.Success) { return $result }

    while ($m.Success) {
        $name = $m.Groups[2].Value
        $funcStart = $m.Index + $m.Length
        # Find matching closing brace
        $d = 1
        $p = $funcStart
        while ($p -lt $obj.Length -and $d -gt 0) {
            if ($obj[$p] -eq '{') { $d++ }
            if ($obj[$p] -eq '}') { $d-- }
            $p++
        }
        $fullMatch = $obj.Substring($m.Index, $p - $m.Index)
        $result[$name] = $fullMatch
        $pos2 = $m.Index + 1
        if ($pos2 -ge $obj.Length) { break }
        $m = $re.Match($obj, $pos2)
    }
    return $result
}

# --- Helper: extract methods: { } block ---
function Get-MethodsBlock($obj) {
    $re = [regex]('(?s)(?:^|[\s,]+)methods\s*:\s*\{')
    $m = $re.Match($obj)
    if (-not $m.Success) { return $null }
    $start = $m.Index + $m.Length - 1  # position of {
    $d = 1
    $p = $start + 1
    while ($p -lt $obj.Length -and $d -gt 0) {
        if ($obj[$p] -eq '{') { $d++ }
        if ($obj[$p] -eq '}') { $d-- }
        $p++
    }
    return $obj.Substring($start, $p - $start)
}

function Parse-MethodsBlock($block) {
    $result = @{}
    if (-not $block -or $block.Trim() -eq '{}') { return $result }

    $inner = $block.Substring(1, $block.Length - 2)  # strip outer {}
    $re = [regex]('(?s)(\w+)\s*[:(]')
    $m = $re.Match($inner)
    $lastPos = 0
    $names = New-Object System.Collections.Generic.List[string]

    while ($m.Success) {
        $names.Add($m.Groups[1].Value)
        $lastPos = $m.Index + $m.Length
        if ($lastPos -ge $inner.Length) { break }
        $m = $re.Match($inner, $lastPos)
    }

    for ($i = 0; $i -lt $names.Count; $i++) {
        $n = $names[$i]
        # Find start of this method
        $m2 = [regex]::Match($inner, $n + '\s*[:(]')
        if (-not $m2.Success) { continue }
        $startIdx = $m2.Index
        # Find end - either next method name or end of block
        $endIdx = $inner.Length
        if ($i + 1 -lt $names.Count) {
            $m3 = [regex]::Match($inner, $names[$i+1] + '\s*[:(]')
            if ($m3.Success -and $m3.Index -gt $startIdx) {
                $endIdx = $m3.Index
            }
        }
        $body = $inner.Substring($startIdx, $endIdx - $startIdx)
        $body = $body.TrimEnd().TrimEnd(',').TrimEnd()
        $result[$n] = $body
    }
    return $result
}

# --- Extract all sections ---

# Props
$props = Get-ExportKey $exportObject 'props'

# Convert array props ['a','b'] to object form
if ($props -ne $null -and $props -match '^\[') {
    $propNames = @()
    $temp = $props
    while ($temp -match "'([^']+)'") {
        $propNames += $Matches[1]
        $idx = $temp.IndexOf($Matches[0])
        if ($idx + $Matches[0].Length -ge $temp.Length) { break }
        $temp = $temp.Substring($idx + $Matches[0].Length)
    }
    if ($propNames.Count -gt 0) {
        $propLines = "  {"
        $first = $true
        foreach ($pn in $propNames) {
            if (-not $first) { $propLines += "," }
            $propLines += "`n    $($pn): {}"
            $first = $false
        }
        $propLines += "`n  }"
        $props = $propLines
    } else {
        $props = "{}"
    }
}

# Emits
$emits = Get-ExportKey $exportObject 'emits'

# Data
$dataKeys = @{}
$dataVal = Get-ExportKey $exportObject 'data'
if ($dataVal -ne $null) {
    # data() { return { ... } }
    $retMatch = [regex]::Match($dataVal, 'return\s*(\{')
    if ($retMatch.Success) {
        $retStart = $retMatch.Groups[1].Index
        $d = 1
        $p = $retStart + 1
        while ($p -lt $dataVal.Length -and $d -gt 0) {
            if ($dataVal[$p] -eq '{') { $d++ }
            if ($dataVal[$p] -eq '}') { $d-- }
            $p++
        }
        $retObj = $dataVal.Substring($retStart, $p - $retStart)
        $retInner = $retObj.Substring(1, $retObj.Length - 2)
        $dLines = $retInner -split '\n'
        $di = 0
        while ($di -lt $dLines.Length) {
            $dl = $dLines[$di]
            if ($dl -match '^\s*(\w+)\s*:\s*(.*)') {
                $k = $Matches[1]
                $v = $dl.Substring($dl.IndexOf(':') + 1).Trim()
                if ($v -match '^\{') {
                    $vd = 0
                    $vp = 0
                    while ($vp -lt $v.Length) {
                        if ($v[$vp] -eq '{') { $vd++ }
                        if ($v[$vp] -eq '}') { $vd-- }
                        if ($vd -eq 0) { break }
                        $vp++
                    }
                    if ($vd -gt 0) {
                        $extra = $di + 1
                        while ($extra -lt $dLines.Length -and $vd -gt 0) {
                            $v = $v + "`n" + $dLines[$extra]
                            for ($pp = 0; $pp -lt $dLines[$extra].Length; $pp++) {
                                if ($dLines[$extra][$pp] -eq '{') { $vd++ }
                                if ($dLines[$extra][$pp] -eq '}') { $vd-- }
                            }
                            $extra++
                        }
                        $di = $extra - 1
                    }
                    $dataKeys[$k] = @{ type = 'reactive'; val = $v }
                } else {
                    $v = $v.TrimEnd(',')
                    $dataKeys[$k] = @{ type = 'ref'; val = $v }
                }
            }
            $di++
        }
    }
}

# Computed
$computed = @{}
$compVal = Get-ExportKey $exportObject 'computed'
if ($compVal -ne $null) {
    $compInner = $compVal.Substring(1, $compVal.Length - 2)
    $re = [regex]('(?s)(\w+)\s*\(\s*\)\s*\{')
    $m = $re.Match($compInner)
    while ($m.Success) {
        $name = $m.Groups[1].Value
        $bodyStart = $m.Index + $m.Length
        $d = 1
        $bp = $bodyStart
        while ($bp -lt $compInner.Length -and $d -gt 0) {
            if ($compInner[$bp] -eq '{') { $d++ }
            if ($compInner[$bp] -eq '}') { $d-- }
            $bp++
        }
        $computed[$name] = $compInner.Substring($m.Index, $bp - $m.Index)
        $nextStart = $m.Index + 1
        if ($nextStart -ge $compInner.Length) { break }
        $m = $re.Match($compInner, $nextStart)
    }
}

# Top-level lifecycle hooks (mounted, beforeUnmount, etc.)
$topLevelHooks = Get-TopLevelMethods $exportObject

# Methods
$methodsBlock = Get-MethodsBlock $exportObject
$methods = @{}
if ($methodsBlock -ne $null) {
    $methods = Parse-MethodsBlock $methodsBlock
}

# Helpful debug
Write-Host "DBG: dataKeys = $($dataKeys.Keys -join ', ')" -ForegroundColor Cyan
Write-Host "DBG: methods = $($methods.Keys -join ', ')" -ForegroundColor Cyan
Write-Host "DBG: computed = $($computed.Keys -join ', ')" -ForegroundColor Cyan
Write-Host "DBG: hooks = $($topLevelHooks.Keys -join ', ')" -ForegroundColor Cyan
Write-Host "DBG: props = $props" -ForegroundColor Cyan
Write-Host "DBG: emits = $emits" -ForegroundColor Cyan

# --- Build <script setup> ---
$lines = @()

# Vue imports
$vueImports = @()
if ($topLevelHooks.ContainsKey('mounted')) { $vueImports += 'onMounted' }
if ($topLevelHooks.ContainsKey('beforeUnmount')) { $vueImports += 'onBeforeUnmount' }
if ($computed.Keys.Count -gt 0) { $vueImports += 'computed' }
if ($scriptInner -match 'watch\s*:') { $vueImports += 'watch' }
if ($scriptInner -match '\$nextTick') { $vueImports += 'nextTick' }
if ($dataKeys.Keys.Count -gt 0) { $vueImports += 'ref' }
foreach ($k2 in $dataKeys.Keys) {
    if ($dataKeys[$k2]['type'] -eq 'reactive') {
        $vueImports += 'reactive'
        break
    }
}
$vueImports = $vueImports | Select-Object -Unique | Sort-Object
if ($vueImports.Count -gt 0) {
    $lines += "import { $($vueImports -join ', ') } from 'vue';"
}

# Non-vue imports
$nonVueImports = @()
foreach ($imp in $importLines) {
    if ($imp -cmatch 'from\s+[''"]([^''""]+)[''"]') {
        $fromVal = $Matches[1]
        if ($fromVal -ne 'vue') {
            $nonVueImports += $imp
        }
    }
}
if ($nonVueImports.Count -gt 0) {
    $lines += ''
    $lines += $nonVueImports
}

# Axios
if ($scriptInner -match '\baxios\b') {
    $hasAxiosImport = $false
    foreach ($imp in $importLines) {
        if ($imp -match 'axios') { $hasAxiosImport = $true; break }
    }
    if (-not $hasAxiosImport) {
        $lines += "import axios from 'axios';"
    }
}

# defineProps
if ($props) {
    $lines += ''
    $lines += "const props = defineProps($props);"
}

# defineEmits
if ($emits) {
    $lines += "const emit = defineEmits($emits);"
}

# data -> ref/reactive
if ($dataKeys.Keys.Count -gt 0) {
    $lines += ''
}
foreach ($key in $dataKeys.Keys) {
    $info = $dataKeys[$key]
    if ($info['type'] -eq 'reactive') {
        $lines += "const $key = reactive($($info['val']));"
    } else {
        $lines += "const $key = ref($($info['val']));"
    }
}

# computed
if ($computed.Keys.Count -gt 0) {
    $lines += ''
}
foreach ($cName in $computed.Keys) {
    $body = $computed[$cName]
    $body = $body -replace '^[^(]*\(\)\s*\{', ''
    $body = $body.TrimEnd().TrimEnd(',')
    $lines += "const $cName = computed(() => {$body`n});"
}

# Helper for this. replacements
function Convert-Body {
    param($text)
    foreach ($k in $script:dataKeys.Keys) {
        $info = $script:dataKeys[$k]
        $suf = ''
        if ($info['type'] -eq 'ref') { $suf = '.value' }
        $text = $text -replace "(?<!\w)this\.$k(?!\w)", "$k$suf"
    }
    foreach ($cn in $script:computed.Keys) {
        $text = $text -replace "(?<!\w)this\.$cn(?!\w)", "$cn.value"
    }
    $text = $text -replace '\$emit\(', 'emit('
    $text = $text -replace '\$nextTick\(', 'nextTick('
    $text = $text -replace '\$refs\.(\w+)', '$1.value'
    $text = $text -replace '\$forceUpdate\(\)', ''
    foreach ($mn in $script:methods.Keys) {
        $text = $text -replace "(?<!\w)this\.$mn\(", "$mn("
    }
    foreach ($hn in $script:topLevelHooks.Keys) {
        $text = $text -replace "(?<!\w)this\.$hn\(", "$hn("
    }
    $text = $text -replace '(?<!\w)this\.', ''
    return $text
}

# Methods from the methods: {} block
if ($methods.Keys.Count -gt 0) {
    $lines += ''
}
foreach ($name in $methods.Keys) {
    $body = $methods[$name]
    # Strip method signature
    $body = $body -replace '^\s*', ''
    $body = $body -replace '^' + $name + '\s*', ''
    $body = $body -replace '^\([^)]*\)\s*\{', ''
    $body = $body.TrimEnd().TrimEnd(',')

    $body = Convert-Body $body
    $lines += "async function $name() {$body`n}"
}

# Lifecycle hooks as onMounted/onBeforeUnmount
foreach ($hookName in @('mounted', 'beforeUnmount')) {
    if ($topLevelHooks.ContainsKey($hookName)) {
        $vueHook = "on$((Get-Culture).TextInfo.ToTitleCase($hookName))"
        $body = $topLevelHooks[$hookName]
        $body = $body -replace '^\s*' + $hookName + '\s*\(\s*\)', ''
        $body = $body.TrimStart()
        if ($body -match '^\{') { $body = $body.Substring(1) }
        $body = $body.TrimEnd().TrimEnd('}').Trim()
        $body = Convert-Body $body
        $lines += "`n$vueHook(() => {$body`n});"
    }
}

# Final output
$output = $templateBlock + "`n"
$output = $output + "<script setup>`n"
$output = $output + ($lines -join "`n")
$output = $output + "`n</script>`n"
$output = $output + ($styleBlocks -join "`n`n")

if ($DryRun) {
    Write-Host "=== Generated <script setup> ==="
    Write-Host $output
    Write-Host "=== File not written (DryRun) ==="
    exit 0
}

$backupPath = $Path + ".bak"
Copy-Item $Path $backupPath -Force
Write-Host "Backup saved: $backupPath"

Set-Content -Path $Path -Value $output -NoNewline
Write-Host "Migrated: $Path"
