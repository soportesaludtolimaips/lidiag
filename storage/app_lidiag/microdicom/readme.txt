It is autorun package for distribution of CD/DVD with DICOM files.
 
It contains:
Win32 directory
x64 directory
licenses directory
settings directory
autorun.inf
readme.txt
run.bat

You will see in autorun.inf that you can use "mdicom.exe DICOMDIR". It will open DICOMDIR file and will display DICOM files on start up. In this situation DICOMDIR file must be placed in root of CD. If you place DICOMDIR file on other location on CD, you must edit autorun.inf file with path to DICOMDIR file:
mdicom.exe <path_to_DICOMDIR_file>
Also you can use : "mDicom.exe /scan <path>". It will scan for DICOM files.
 
Copy content of zip file and your DICOM files to CD/DVD. Note that content of zip file must be in root of CD/DVD.

If MicroDicom DICOM viewer doesn't start with autorun, please open the viewer by double clicking the file 'run.bat' instead.
