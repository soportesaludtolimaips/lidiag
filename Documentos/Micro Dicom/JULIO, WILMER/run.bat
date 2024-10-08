@echo off
title Start MicroDicom DICOM viewer

if exist DICOMDIR (
  set "target=DICOMDIR"
) else (
  set "target=/scan ."
)

if defined ProgramFiles(x86) (
    ::64-bit
    start /B x64\mdicom.exe %target%
) else (
    ::32-bit
    start /B x64\mdicom.exe %target%
)

