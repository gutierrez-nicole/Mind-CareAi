@echo off
REM Kill any python process running camera.py
for /f "tokens=2 delims=," %%a in ('wmic process where "CommandLine like '%%camera.py%%'" get ProcessId^,CommandLine /format:csv ^| findstr /i "camera.py"') do (
    taskkill /PID %%a /F
)