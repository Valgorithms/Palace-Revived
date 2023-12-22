chcp 65001
@echo off
cls
set watch=Tutelar
title %watch% Watchdog
:watchdog
echo (%time%) %watch% started.
php -v
php -dopcache.cache_id=2 -dopcache.enable_cli=1 -dopcache.jit_buffer_size=264M "run.php" > botlog.txt
echo (%time%) %watch% closed or crashed, restarting.
goto watchdog