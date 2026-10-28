@echo off
title MMBPL Sync Agent - setup
REM First run: opens the settings page in the browser. Keep this window open while you test.
set MMBPL_AGENT_DIR=%ProgramData%\MMBPL-Sync-Agent
cd /d "%~dp0"
echo Starting MMBPL Sync Agent... settings page: http://127.0.0.1:8765
"%~dp0MMBPL-Sync-Agent.exe" --open
pause
