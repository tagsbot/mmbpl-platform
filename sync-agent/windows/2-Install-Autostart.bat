@echo off
title MMBPL Sync Agent - auto start
REM Run this file with "Run as administrator". The agent will start automatically when the computer starts.
net session >nul 2>&1
if %errorlevel% neq 0 (
  echo Please right-click this file and choose "Run as administrator".
  pause
  exit /b 1
)
set DIR=%~dp0
schtasks /Create /F /TN "MMBPL Sync Agent" /SC ONSTART /RU SYSTEM /RL HIGHEST /TR "wscript.exe \"%DIR%start-hidden.vbs\""
schtasks /Run /TN "MMBPL Sync Agent"
echo.
echo Done. The agent now runs in the background and starts with Windows.
echo Settings page: http://127.0.0.1:8765
pause
