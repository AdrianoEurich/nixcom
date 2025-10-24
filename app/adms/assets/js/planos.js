// Planos JavaScript
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página de planos carregada');
    
    // Definir URLADM se não estiver definida
    if (typeof window.URLADM === 'undefined') {
        window.URLADM = window.URL_ADM || '/nixcom/adms/';
    }
    
    // Elementos principais
    const planCards = document.querySelectorAll('.plan-card');
    
    // Adicionar efeitos hover aos cards
    planCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Função para selecionar plano (será chamada pelo HTML)
    window.selectPlan = function(planType) {
        console.log('Plano selecionado:', planType);
        
        // Salvar plano na sessão
        fetch(window.URLADM + 'planos/setSelectedPlan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ plan: planType })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Plano salvo com sucesso');
                // Redirecionar para cadastro
                window.location.href = window.URLADM + 'cadastro';
            } else {
                console.error('Erro ao salvar plano:', data.message);
                alert('Erro ao selecionar plano: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            alert('Erro ao selecionar plano. Tente novamente.');
        });
    };
    
    // Adicionar animação de entrada aos cards
    planCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 200);
    });
});
