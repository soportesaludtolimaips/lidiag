# detect_drives.ps1 - Deteccion robusta de unidades opticas CD/DVD para LIDiag
$results = @()

# 1. IMAPI2 COM (Grabadoras opticas directas de Windows)
try {
    $master = New-Object -ComObject IMAPI2.MsftDiscMaster2
    $rec = New-Object -ComObject IMAPI2.MsftDiscRecorder2
    $fmt = $null
    try {
        $fmt = New-Object -ComObject IMAPI2.MsftDiscFormat2Data
    } catch {}

    foreach ($id in $master) {
        try {
            $rec.InitializeDiscRecorder($id)
            $driveLetter = $null
            if ($rec.VolumePathNames) {
                foreach ($p in $rec.VolumePathNames) {
                    if ($p -match '^[a-zA-Z]:') {
                        $driveLetter = $p.Substring(0, 1).ToUpper()
                        break
                    }
                }
            }

            $vendor = ""
            $product = ""
            try { $vendor = $rec.VendorId } catch {}
            try { $product = $rec.ProductId } catch {}
            $recorderName = ($vendor + " " + $product).Trim()

            $mediaLoaded = $false
            $discType = $null

            if ($fmt) {
                try {
                    $fmt.Recorder = $rec
                    $mt = [int]$fmt.CurrentPhysicalMediaType
                    $mediaLoaded = $true
                    if ($mt -ge 1 -and $mt -le 3) {
                        $discType = "CD"
                    } elseif ($mt -ge 4 -and $mt -le 13) {
                        $discType = "DVD"
                    } elseif ($mt -ge 14) {
                        $discType = "BD"
                    }
                } catch {
                    # No hay medio insertado o no compatible
                }
            }

            if (-not $discType) {
                if ($recorderName -match 'DVD') {
                    $discType = "DVD"
                } elseif ($recorderName -match 'CD') {
                    $discType = "CD"
                } elseif ($recorderName -match 'BD|Blu-ray') {
                    $discType = "BD"
                }
            }

            if ($driveLetter) {
                $results += [PSCustomObject]@{
                    drive = $driveLetter
                    disc_type = $discType
                    media_loaded = $mediaLoaded
                    recorder_name = if ($recorderName) { $recorderName } else { "Unidad de CD/DVD ($driveLetter`:)" }
                }
            }
        } catch {}
    }

    if ($fmt) { try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($fmt) | Out-Null } catch {} }
    if ($rec) { try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($rec) | Out-Null } catch {} }
    if ($master) { try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($master) | Out-Null } catch {} }
} catch {}

# 2. .NET DriveInfo (Garantizado para cualquier cuenta de Windows sin WMI)
try {
    $drives = [System.IO.DriveInfo]::GetDrives() | Where-Object { $_.DriveType -eq [System.IO.DriveType]::CDRom }
    foreach ($d in $drives) {
        $l = $d.Name.Substring(0, 1).ToUpper()
        $exists = $results | Where-Object { $_.drive -eq $l }
        if (-not $exists) {
            $isReady = $false
            try { $isReady = $d.IsReady } catch {}
            $results += [PSCustomObject]@{
                drive = $l
                disc_type = "CD"
                media_loaded = $isReady
                recorder_name = "Unidad de CD/DVD ($l`:)"
            }
        }
    }
} catch {}

# 3. WMI / CIM Fallback (si estuviera disponible)
try {
    $cimDrives = Get-CimInstance Win32_CDROMDrive -ErrorAction SilentlyContinue
    if ($cimDrives) {
        foreach ($cd in $cimDrives) {
            if ($cd.Drive) {
                $l = $cd.Drive.Substring(0, 1).ToUpper()
                $found = $results | Where-Object { $_.drive -eq $l }
                if ($found) {
                    if (-not $found.recorder_name -and $cd.Name) {
                        $found.recorder_name = $cd.Name
                    }
                    if (-not $found.media_loaded -and $cd.MediaLoaded) {
                        $found.media_loaded = [bool]$cd.MediaLoaded
                    }
                } else {
                    $results += [PSCustomObject]@{
                        drive = $l
                        disc_type = if ($cd.Name -match 'DVD') { "DVD" } else { "CD" }
                        media_loaded = [bool]$cd.MediaLoaded
                        recorder_name = $cd.Name
                    }
                }
            }
        }
    }
} catch {}

if ($results.Count -eq 0) {
    Write-Output "[]"
} else {
    Write-Output ($results | ConvertTo-Json -Compress)
}
