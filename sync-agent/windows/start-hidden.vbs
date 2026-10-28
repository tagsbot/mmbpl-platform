' Runs the MMBPL Sync Agent in the background without a window.
Set sh = CreateObject("WScript.Shell")
Set env = sh.Environment("PROCESS")
env("MMBPL_AGENT_DIR") = sh.ExpandEnvironmentStrings("%ProgramData%") & "\MMBPL-Sync-Agent"
dir = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)
sh.CurrentDirectory = dir
sh.Run """" & dir & "\MMBPL-Sync-Agent.exe"" --no-browser", 0, False
