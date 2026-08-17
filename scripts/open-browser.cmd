@echo off

:wait
C:\Windows\System32\curl.exe -s -o NUL http://localhost:8000

if errorlevel 1 (
    C:\Windows\System32\ping.exe 127.0.0.1 -n 2 > NUL
    goto wait
)

start "" "http://localhost:8000"