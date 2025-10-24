@echo off
chcp 65001 >nul
echo ========================================
echo    TESTE DO SISTEMA
echo ========================================
echo.

echo [1/6] Verificando XAMPP...
if exist "C:\xampp\apache\bin\httpd.exe" (
    echo [OK] Apache encontrado
) else (
    echo [ERRO] Apache nao encontrado
    echo Execute: start-dev.bat
    pause
    exit
)

if exist "C:\xampp\mysql\bin\mysqld.exe" (
    echo [OK] MySQL encontrado
) else (
    echo [ERRO] MySQL nao encontrado
    echo Execute: start-dev.bat
    pause
    exit
)

echo.
echo [2/6] Verificando servicos...
echo Testando Apache...
curl -s -o nul -w "%%{http_code}" http://localhost/nixcom/ | findstr "200" >nul
if %errorlevel% equ 0 (
    echo [OK] Apache funcionando
) else (
    echo [ERRO] Apache nao responde
    echo Execute: start-dev.bat
    pause
    exit
)

echo Testando MySQL...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT 1;" >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] MySQL funcionando
) else (
    echo [AVISO] MySQL pode precisar de senha
)

echo.
echo [3/6] Verificando arquivos do projeto...
if exist "index.php" (
    echo [OK] index.php encontrado
) else (
    echo [ERRO] index.php nao encontrado
    pause
    exit
)

if exist "app\adms\Config\MercadoPagoConfig.php" (
    echo [OK] Configuracao Mercado Pago encontrada
) else (
    echo [ERRO] Configuracao Mercado Pago nao encontrada
    pause
    exit
)

echo.
echo [4/6] Verificando ngrok...
if exist "C:\ngrok\ngrok.exe" (
    echo [OK] ngrok encontrado
) else (
    echo [AVISO] ngrok nao encontrado
    echo Execute: setup-ngrok.bat
)

echo.
echo [5/6] Testando URLs...
echo Testando aplicacao principal...
curl -s -o nul -w "%%{http_code}" http://localhost/nixcom/ | findstr "200" >nul
if %errorlevel% equ 0 (
    echo [OK] Aplicacao principal acessivel
) else (
    echo [ERRO] Aplicacao principal nao acessivel
)

echo Testando area administrativa...
curl -s -o nul -w "%%{http_code}" http://localhost/nixcom/adms/ | findstr "200\|302" >nul
if %errorlevel% equ 0 (
    echo [OK] Area administrativa acessivel
) else (
    echo [ERRO] Area administrativa nao acessivel
)

echo.
echo [6/6] Resumo dos testes...
echo.
echo ========================================
echo    RESULTADO DOS TESTES
echo ========================================
echo.
echo URLs para testar:
echo - Aplicacao: http://localhost/nixcom/
echo - Admin: http://localhost/nixcom/adms/
echo - Login: http://localhost/nixcom/adms/login
echo - Planos: http://localhost/nixcom/adms/planos
echo - Pagamento: http://localhost/nixcom/adms/pagamento
echo.
echo Proximos passos:
echo 1. Configure ngrok: setup-ngrok.bat
echo 2. Teste o fluxo de pagamento
echo 3. Configure webhook no Mercado Pago
echo.
echo Pressione qualquer tecla para abrir o navegador...
pause >nul

start http://localhost/nixcom/adms/

echo.
pause