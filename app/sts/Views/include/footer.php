<?php
// Verificação de segurança para evitar acesso direto
if (!defined('C7E3L8K9E5')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}

?>

  <!-- 
  =============================================
  FOOTER - Só será renderizado em requisições normais
  =============================================
-->
  <footer class="footer py-5">
    <div class="container">
      <div class="row">
        <!-- Coluna 1: Sobre -->
        <div class="col-lg-4 mb-4 mb-lg-0">
          <h3 class="footer-brand"><span class="brand-highlight">GP</span>HUB</h3>
          <p class="footer-about">Plataforma premium para divulgação de anúncios elegantes e discretos. Conectando profissionais de alto padrão com clientes exclusivos.</p>
        </div>

        <!-- Coluna 2: Links rápidos -->
        <div class="col-md-6 col-lg-2 mb-4 mb-md-0">
          <h4 class="footer-title">Links</h4>
          <div class="linha bg-primary mb-3" style="height: 2px; width: 50px;"></div>
          <ul class="footer-links">
            <li><a href="<?= URL ?>">Home</a></li>
            <li><a href="<?= URL ?>#acompanhantes">Acompanhantes</a></li>
            <li><a href="<?= URL ?>#contato">Contato</a></li>
            <li><a href="<?= URLADM ?>login">Login</a></li>
          </ul>
        </div>

        <!-- Coluna 3: Acompanhantes -->
        <div class="col-md-6 col-lg-3 mb-4 mb-md-0">
          <h4 class="footer-title">Acompanhantes</h4>
          <div class="linha bg-primary mb-3" style="height: 2px; width: 50px;"></div>
          <ul class="footer-links">
            <li><a href="<?= URL ?>categorias/mulher">Mulheres</a></li>
            <li><a href="<?= URL ?>categorias/homem">Homens</a></li>
            <li><a href="<?= URL ?>categorias/trans">Trans</a></li>
          </ul>
        </div>

        <!-- Coluna 4: Redes sociais -->
        <div class="col-12 col-md-6 col-lg-3">
          <h5 class="footer-title mb-3">Rede Social</h5>
          <div class="linha bg-primary mb-3" style="height: 2px; width: 50px;"></div>
          <div class="social-links d-flex flex-wrap gap-3">
            <a href="#" class="text-white twitter" title="Twitter (X)">
              <i class="fa-brands fa-x-twitter"></i>
            </a>
            <a href="#" class="text-white facebook" title="Facebook">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="text-white instagram" title="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
            <a href="#" class="text-white whatsapp" title="WhatsApp">
              <i class="fab fa-whatsapp"></i>
            </a>
            <a href="#" class="text-white youtube" title="YouTube">
              <i class="fab fa-youtube"></i>
            </a>
            <a href="#" class="text-white telegram" title="Telegram">
              <i class="fab fa-telegram"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Divisor e copyright -->
      <hr class="footer-divider">
      <div class="footer-bottom text-center">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> GPHub. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>

  <!-- 
  =============================================
  SCRIPTS - Só carrega em requisições normais
  =============================================
-->
  <!-- Bootstrap JS com Popper -->
  <script src="<?php echo URL; ?>app/sts/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- JS Personalizado -->
  <script src="<?php echo URL; ?>app/sts/assets/js/modalManager.js"></script>
  <script src="<?php echo URL; ?>app/sts/assets/js/personalizado.js"></script>

  <!-- Fechamento das tags body e html -->
  </body>

  </html>