@echo off
REM Run as administrator. Stops the agent and removes the auto start.
schtasks /End /TN "MMBPL Sync Agent" >nul 2>&1
schtasks /Delete /F /TN "MMBPL Sync Agent"
taskkill /F /IM MMBPL-Sync-Agent.exe >nul 2>&1
echo Auto start removed.
pause
