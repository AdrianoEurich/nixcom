@echo off
chcp 65001 >nul
echo ========================================
echo    CONFIGURACAO DO NGROK
echo ========================================
echo.

echo [1/4] Verificando ngrok...
if exist "C:\ngrok\ngrok.exe" (
    echo [OK] ngrok encontrado
) else (
    echo [AVISO] ngrok nao encontrado
    echo.
    echo Baixando ngrok...
    powershell -Command "Invoke-WebRequest -Uri 'https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-windows-amd64.zip' -OutFile 'ngrok.zip'"
    powershell -Command "Expand-Archive -Path 'ngrok.zip' -DestinationPath 'C:\ngrok\'"
    del ngrok.zip
    echo [OK] ngrok baixado e instalado
)

echo.
echo [2/4] Verificando configuracao...
cd /d C:\ngrok\
if not exist "ngrok.yml" (
    echo [AVISO] Authtoken nao configurado
    echo.
    echo Para configurar:
    echo 1. Acesse: https://ngrok.com/
    echo 2. Crie uma conta gratuita
    echo 3. Obtenha seu authtoken
    echo 4. Execute: ngrok config add-authtoken SEU_AUTHTOKEN
    echo.
    echo Pressione qualquer tecla para continuar...
    pause >nul
) else (
    echo [OK] ngrok configurado
)

echo.
echo [3/4] Testando ngrok...
echo Iniciando ngrok na porta 80...
echo.
echo IMPORTANTE:
echo 1. Copie a URL HTTPS gerada pelo ngrok
echo 2. Atualize: app/adms/Config/MercadoPagoConfig.php
echo 3. Substitua: WEBHOOK_URL_SANDBOX
echo.
echo Pressione qualquer tecla para iniciar ngrok...
pause >nul

ngrok http 80

pause