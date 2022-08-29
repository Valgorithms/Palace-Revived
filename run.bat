chcp 65001
@echo off
cls
set watch=Tutelar
title %watch% Watchdog
:watchdog
echo (%time%) %watch% started.
php -dopcache.enable_cli=1 -dopcache.jit_buffer_size=264M "bot.php"
echo (%time%) %watch% closed or crashed, restarting.
goto watchdog