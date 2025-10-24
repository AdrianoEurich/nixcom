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
    echo ========================================
    echo    CONFIGURACAO NECESSARIA
    echo ========================================
    echo.
    echo 1. Acesse: https://dashboard.ngrok.com/signup
    echo 2. Crie uma conta gratuita
    echo 3. Confirme seu email
    echo 4. Acesse: https://dashboard.ngrok.com/get-started/your-authtoken
    echo 5. Copie seu authtoken
    echo.
    echo ========================================
    echo.
    set /p AUTHTOKEN="Cole seu authtoken aqui: "
    
    if "%AUTHTOKEN%"=="" (
        echo [ERRO] Authtoken nao fornecido
        pause
        exit
    )
    
    echo Configurando authtoken...
    ngrok config add-authtoken %AUTHTOKEN%
    
    if %errorlevel% equ 0 (
        echo [OK] Authtoken configurado com sucesso
    ) else (
        echo [ERRO] Falha ao configurar authtoken
        pause
        exit
    )
) else (
    echo [OK] ngrok ja configurado
)

echo.
echo [3/4] Testando configuracao...
ngrok version >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] ngrok funcionando
) else (
    echo [ERRO] ngrok nao funciona
    pause
    exit
)

echo.
echo [4/4] Iniciando ngrok...
echo.
echo ========================================
echo    NGROK CONFIGURADO E FUNCIONANDO
echo ========================================
echo.
echo IMPORTANTE:
echo 1. Copie a URL HTTPS gerada pelo ngrok
echo 2. Atualize: app/adms/Config/MercadoPagoConfig.php
echo 3. Substitua: WEBHOOK_URL_SANDBOX
echo.
echo Exemplo:
echo WEBHOOK_URL_SANDBOX = 'https://abc123.ngrok.io/webhook/mercadopago.php'
echo.
echo Pressione qualquer tecla para iniciar ngrok...
pause >nul

ngrok http 80

pause

