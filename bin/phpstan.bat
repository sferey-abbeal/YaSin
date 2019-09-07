@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../vendor/phpstan/phpstan-shim/phpstan
php "%BIN_TARGET%" %*
