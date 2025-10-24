@echo off
chcp 65001 >nul
echo ========================================
echo    PARANDO SERVICOS DE DESENVOLVIMENTO
echo ========================================
echo.

echo [1/2] Parando Apache...
taskkill /F /IM httpd.exe >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Apache parado
) else (
    echo [AVISO] Apache nao estava rodando
)

echo.
echo [2/2] Parando MySQL...
taskkill /F /IM mysqld.exe >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] MySQL parado
) else (
    echo [AVISO] MySQL nao estava rodando
)

echo.
echo ========================================
echo    SERVICOS PARADOS
echo ========================================
echo.
echo Para iniciar novamente, execute: start-dev.bat
echo.
pause