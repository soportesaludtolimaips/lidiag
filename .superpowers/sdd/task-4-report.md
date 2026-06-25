# Task 4 Report: Encryption at Rest

## Status: ✅ Complete

## Changes Made

### 1. `app/Http/Controllers/Ecografias/EcografiaConsentimientosController.php`

**`firmarPacientePorToken()`** (lines 287-294):
- Changed signature filename from `.png` to `.png.enc`
- Wrapped raw PNG content with `encrypt()` before saving to `firmas_consntimientos` disk
- Path stored in `$consent->firma_paciente_path` automatically uses the new `.png.enc` extension

**`generarPdf()`** (lines 413-420):
- Appended `.enc` to the PDF filename (result: `Consentimiento_*.pdf.enc`)
- Wrapped PDF output with `encrypt()` before saving to `files_consentimientos` disk
- Path stored in `$consent->pdf_path` automatically uses the new `.enc` extension

### 2. `app/Http/Controllers/Ecografias/PlantillaEcografiaController.php`

**`convertDocxToPdf()`** (lines 243-246):
- After unoconv generates the PDF, reads the file content, encrypts it with `encrypt()`
- Saves encrypted content as `{filename}.pdf.enc` (original filename + `.enc`)
- Deletes the unencrypted original PDF with `@unlink()`

## Verification
- `php -l` passed on both modified files (no syntax errors)
- Commit: `e0b0785a` with message `feat(security): encrypt patient signatures, consent PDFs, and report PDFs at rest`
