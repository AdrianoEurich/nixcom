# nixcom
Site simples de anúncios, desenvolvido para fins de demonstração e prática.
Data 23-05-2025

<-------------------------------------------------------------------------------->

Passos para Atualizar seu Projeto no GitHub
Siga estes passos no seu terminal ou linha de comando:

1. Verifique o Status dos Arquivos
Primeiro, é bom ver quais arquivos foram modificados, adicionados ou excluídos.

Bash

git status
Este comando vai te mostrar uma lista dos arquivos que estão "untracked" (novos e não rastreados), "modified" (modificados) e "deleted" (excluídos).

2. Adicione as Alterações à "Staging Area"
Agora você precisa dizer ao Git quais alterações você quer incluir no próximo commit.

Para adicionar todos os arquivos modificados e novos:
Bash

git add .
Para adicionar arquivos específicos:
Bash

git add assets/js/perfil.js
git add index.html # Exemplo, se você alterou o HTML
Você pode listar múltiplos arquivos ou usar um diretório, como git add assets/.
Depois de adicionar, você pode rodar git status novamente para ver que os arquivos agora estão na "staging area" (prontos para o commit).

3. Crie um Commit
Um commit é como um "ponto de salvamento" no histórico do seu projeto. Cada commit deve ter uma mensagem clara que descreva o que foi alterado.

Bash

git commit -m "Atualiza script perfil.js com melhorias de modais e lógica de formulário"
Substitua a mensagem entre aspas com uma descrição concisa e relevante das suas alterações.

4. Envie as Alterações para o GitHub (Push)
Finalmente, você envia seus commits locais para o repositório remoto no GitHub.

Bash

git push origin main
origin é o nome padrão do seu repositório remoto no GitHub.
main (ou master) é o nome da sua branch principal. Se sua branch principal tiver outro nome (como master em projetos mais antigos), use esse nome.
Se for a primeira vez que você faz um push para uma branch (por exemplo, se você criou uma nova branch localmente e quer enviá-la para o GitHub), você pode precisar usar:

Bash

git push -u origin main
O -u (ou --set-upstream) define a branch remota padrão para sua branch local, então nas próximas vezes você pode usar apenas git push.

Resumo dos Comandos:
Bash

git status
git add .
git commit -m "Mensagem clara da sua alteração"
git push origin main

<------------------------------------------------------------->