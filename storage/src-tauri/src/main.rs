use std::process::Command;
use tauri::{Runtime, Emitter, Manager};
use std::io::copy;
use zip::ZipArchive;

#[tauri::command]
fn list_cd_drives<R: Runtime>(_app: tauri::AppHandle<R>) -> Result<Vec<String>, String> {
    #[cfg(target_os = "windows")]
    {
        use std::collections::HashMap;
        use wmi::{COMLibrary, Variant};
        let com_con = COMLibrary::new().map_err(|e| format!("Failed to initialize COM: {}", e))?;
        let wmi_con = wmi::WMIConnection::new(com_con.into()).map_err(|e| format!("WMI connection failed: {}", e))?;
        let mut drives = Vec::new();
        let results: Vec<HashMap<String, Variant>> = wmi_con
            .raw_query("SELECT DriveId FROM Win32_CDROMDrive")
            .map_err(|e| format!("WMI query failed: {}", e))?;
        for item in results {
            if let Some(variant) = item.get("DriveId") {
                match variant {
                    Variant::String(s) => {
                        let letter = s.trim_end_matches(':').to_uppercase();
                        drives.push(letter);
                    }
                    _ => {}
                }
            }
        }
        Ok(drives)
    }

    #[cfg(not(target_os = "windows"))]
    {
        let mut drives = Vec::new();
        let possible = vec!["/dev/cdrom", "/dev/dvd", "/dev/sr0", "/dev/sr1"];
        for p in possible {
            if std::path::Path::new(p).exists() {
                drives.push(p.to_string());
            }
        }
        Ok(drives)
    }
}

#[tauri::command]
fn list_burn_commands() -> Result<Vec<String>, String> {
    let mut commands = Vec::new();
    if Command::new("wodim").arg("--version").output().is_ok() {
        commands.push("wodim".to_string());
    }
    if Command::new("cdrecord").arg("--version").output().is_ok() {
        commands.push("cdrecord".to_string());
    }
    if Command::new("growisofs").arg("--version").output().is_ok() {
        commands.push("growisofs".to_string());
    }
    #[cfg(target_os = "windows")]
    {
        let oscdimg_paths = [
            r"C:\Program Files (x86)\Windows Kits\10\bin\x86\oscdimg.exe",
            r"C:\Program Files (x86)\Windows Kits\10\bin\x64\oscdimg.exe",
            r"C:\Program Files\Windows Kits\10\bin\x86\oscdimg.exe",
            r"C:\Program Files\Windows Kits\10\bin\x64\oscdimg.exe",
        ];
        for path in &oscdimg_paths {
            if std::path::Path::new(path).exists() {
                commands.push(format!("oscdimg {}", path));
                break;
            }
        }
    }
    Ok(commands)
}

#[tauri::command]
fn get_microdicom_path() -> Result<String, String> {
    #[cfg(target_os = "windows")]
    {
        let paths = vec![
            r"C:\Program Files\MicroDicom\MicroDicom.exe".to_string(),
            r"C:\Program Files (x86)\MicroDicom\MicroDicom.exe".to_string(),
            format!("{}\\AppData\\Local\\MicroDicom\\MicroDicom.exe", std::env::var("USERPROFILE").unwrap_or_default()),
        ];
        for p in paths {
            if std::path::Path::new(&p).exists() {
                return Ok(p);
            }
        }
    }
    #[cfg(target_os = "macos")]
    {
        let paths = vec![
            "/Applications/MicroDicom.app/Contents/MacOS/MicroDicom".to_string(),
            "/usr/local/bin/microdicom".to_string(),
        ];
        for p in paths {
            if std::path::Path::new(&p).exists() {
                return Ok(p);
            }
        }
    }
    #[cfg(target_os = "linux")]
    {
        let paths = vec![
            "/usr/bin/microdicom".to_string(),
            "/usr/local/bin/microdicom".to_string(),
        ];
        for p in paths {
            if std::path::Path::new(&p).exists() {
                return Ok(p);
            }
        }
    }
    Err("MicroDicom not found".to_string())
}

#[tauri::command(name = "procesarYGrabarZip")]
fn procesar_y_grabar_zip<R: Runtime>(
    app: tauri::AppHandle<R>,
    _study_pk: i64,
    url: String,
    device_letter: String,
) -> Result<String, String> {
    let temp_dir = tempfile::TempDir::new().map_err(|e| format!("Failed to create temp dir: {}", e))?;
    let zip_path = temp_dir.path().join("study.zip");
    let mut response = reqwest::blocking::get(&url)
        .map_err(|e| format!("Failed to download ZIP: {}", e))?;
    if !response.status().is_success() {
        return Err(format!("Failed to download ZIP: HTTP {}", response.status()));
    }
    let mut file = std::fs::File::create(&zip_path).map_err(|e| format!("Failed to create file: {}", e))?;
    std::io::copy(&mut response, &mut file).map_err(|e| format!("Failed to write ZIP file: {}", e))?;

    let extract_dir = temp_dir.path().join("extract");
    std::fs::create_dir_all(&extract_dir).map_err(|e| e.to_string())?;
    {
        let file = std::fs::File::open(&zip_path).map_err(|e| format!("Failed to open zip file: {}", e))?;
        let mut archive = ZipArchive::new(file).map_err(|e| format!("Failed to read zip archive: {}", e))?;
        for i in 0..archive.len() {
            let mut file = archive.by_index(i).map_err(|e| format!("Failed to get entry {}: {}", i, e))?;
            let outpath = extract_dir.join(file.mangled_name());
            if (&*file.name()).ends_with('/') {
                std::fs::create_dir_all(&outpath).map_err(|e| e.to_string())?;
            } else {
                if let Some(p) = outpath.parent() {
                    if !p.exists() {
                        std::fs::create_dir_all(&p).map_err(|e| e.to_string())?;
                    }
                }
                let mut outfile = std::fs::File::create(&outpath).map_err(|e| e.to_string())?;
                copy(&mut file, &mut outfile).map_err(|e| e.to_string())?;
            }
        }
    }

    let iso_path = temp_dir.path().join("image.iso");
    let mkisofs = if cfg!(target_os = "windows") { "mkisofs" } else { "genisoimage" };
    let status = std::process::Command::new(mkisofs)
        .arg("-o").arg(&iso_path).arg("-J").arg("-r").arg(&extract_dir)
        .status()
        .map_err(|e| format!("Failed to execute {}: {}", mkisofs, e))?;
    if !status.success() {
        return Err("Failed to create ISO image".to_string());
    }

    let burner = if Command::new("wodim").arg("--version").output().is_ok() {
        "wodim"
    } else if Command::new("cdrecord").arg("--version").output().is_ok() {
        "cdrecord"
    } else if Command::new("growisofs").arg("--version").output().is_ok() {
        "growisofs"
    } else {
        return Err("No burning tool found (wodim, cdrecord, growisofs)".to_string());
    };

    let mut drive = device_letter;
    #[cfg(target_os = "windows")]
    {
        if !drive.ends_with(':') { drive.push(':'); }
    }

    let mut cmd = std::process::Command::new(burner);
    if burner == "growisofs" {
        cmd.arg("-Z").arg(format!("{}={}", drive, iso_path.display()))
            .arg("-use-the-force-luke=notray").arg("-use-the-force-luke=tty");
    } else {
        cmd.arg("dev=").arg(drive).arg("-dao").arg("-v").arg(&iso_path);
    }
    let status = cmd.status().map_err(|e| format!("Failed to execute {}: {}", burner, e))?;
    if !status.success() {
        return Err(format!("Burning failed with exit code: {}", status.code().unwrap_or(-1)));
    }

    app.emit("burn-completed", ()).map_err(|e| e.to_string())?;
    Ok("Burn completed successfully".to_string())
}

fn main() {
    tauri::Builder::default()
        .plugin(tauri_plugin_deep_link::init())
        .setup(|app| {
            let handle = app.handle().clone();

            // Listen for deep link events (lidiag://burn?study_pk=X&sede_id=Y)
            if let Some(deep_link) = app.plugin::<tauri_plugin_deep_link::DeepLink>() {
                deep_link.on_open_url(move |event| {
                    let urls = event.urls();
                    for url in urls {
                        let url_str = url.to_string();
                        if url_str.starts_with("lidiag://burn") {
                            // Parse query parameters from the URL
                            let params: std::collections::HashMap<String, String> = url.query_pairs()
                                .map(|(k, v)| (k.to_string(), v.to_string()))
                                .collect();

                            // Show the main window
                            if let Some(window) = handle.get_webview_window("main") {
                                let _ = window.show();
                                let _ = window.set_focus();
                            }

                            // Emit event to frontend with the burn parameters
                            let _ = handle.emit("deep-link-burn", &params);
                        }
                    }
                });
            }

            // Also handle command line arguments for direct exe invocation
            let args: Vec<String> = std::env::args().collect();
            for arg in &args {
                if arg.starts_with("lidiag://burn") {
                    if let Ok(parsed) = url::Url::parse(arg) {
                        let params: std::collections::HashMap<String, String> = parsed.query_pairs()
                            .map(|(k, v)| (k.to_string(), v.to_string()))
                            .collect();

                        if let Some(window) = handle.get_webview_window("main") {
                            let _ = window.show();
                            let _ = window.set_focus();
                        }

                        let _ = handle.emit("deep-link-burn", &params);
                    }
                }
            }

            Ok(())
        })
        .invoke_handler(tauri::generate_handler![
            list_cd_drives,
            list_burn_commands,
            get_microdicom_path,
            procesar_y_grabar_zip
        ])
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}