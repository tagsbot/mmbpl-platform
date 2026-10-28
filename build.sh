#!/usr/bin/env bash
# Builds the cPanel upload zip and the Windows agent zip into ./dist
set -euo pipefail
cd "$(dirname "$0")"
VER=$(grep -oP "APP_VERSION = '\K[^']+" web/app/lib/core.php)
rm -rf dist && mkdir -p dist/site
rsync -a --exclude 'app/config.php' --exclude 'app/storage/*.log' --exclude 'uploads/20*' web/ dist/site/
(cd dist/site && zip -qr "../MMBPL-Website-v$VER.zip" . -x '.DS_Store')
mkdir -p dist/agent/MMBPL-Sync-Agent
cp sync-agent/dist/MMBPL-Sync-Agent.exe sync-agent/windows/* sync-agent/koha-reports.sql dist/agent/MMBPL-Sync-Agent/
cp docs/02-Koha-Sync-Agent-Setup.md dist/agent/MMBPL-Sync-Agent/README-Setup.md
(cd dist/agent && zip -qr "../MMBPL-Sync-Agent-Windows-v$VER.zip" MMBPL-Sync-Agent)
rm -rf dist/site dist/agent
ls -la dist
