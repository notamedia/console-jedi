@echo off
set cmdPath=%~dp0
set cmdFile=%~n0
php -d html_errors=off -qC %cmdPath%%cmdFile% %*