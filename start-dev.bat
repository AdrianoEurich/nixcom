@echo off
chcp 65001 >nul
echo ========================================
echo    INICIANDO AMBIENTE DE DESENVOLVIMENTO
echo ========================================
echo.

echo [1/3] Verificando XAMPP...
if exist "C:\xampp\apache\bin\httpd.exe" (
    echo [OK] Apache encontrado
) else (
    echo [ERRO] Apache nao encontrado em C:\xampp\
    pause
    exit
)

if exist "C:\xampp\mysql\bin\mysqld.exe" (
    echo [OK] MySQL encontrado
) else (
    echo [ERRO] MySQL nao encontrado em C:\xampp\
    pause
    exit
)

echo.
echo [2/3] Iniciando servicos XAMPP...
echo Iniciando Apache...
start /B "" "C:\xampp\apache\bin\httpd.exe" -k start
timeout /t 3 /nobreak >nul

echo Iniciando MySQL...
start /B "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"
timeout /t 3 /nobreak >nul

echo.
echo [3/3] Verificando status...
timeout /t 2 /nobreak >nul

echo.
echo ========================================
echo    AMBIENTE DE DESENVOLVIMENTO PRONTO
echo ========================================
echo.
echo URLs disponiveis:
echo - Aplicacao: http://localhost/nixcom/
echo - Admin: http://localhost/nixcom/adms/
echo - Relatorios: http://localhost/nixcom/adms/financial-reports
echo - Webhook Config: http://localhost/nixcom/adms/webhook-config
echo.
echo Proximo passo: Configurar ngrok para webhook
echo Comando: ngrok http 80
echo.
echo Pressione qualquer tecla para abrir o navegador...
pause >nul

start http://localhost/nixcom/adms/

echo.
echo Para parar os servicos, execute: stop-dev.bat
echo.
pause