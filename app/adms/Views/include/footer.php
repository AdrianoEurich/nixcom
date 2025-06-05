<?php
// Verificação de segurança
if (!defined('C7E3L8K9E5')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
?>

<div class="login-footer text-center">
    <p>&copy; <?php echo date('Y'); ?> Nixcom. Todos os direitos reservados.</p>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?php echo URLADM; ?>assets/js/general-utils.js"></script>
<!-- JS Personalizado -->
<script src="<?php echo URLADM; ?>assets/js/login.js"></script>
<script src="<?php echo URLADM; ?>assets/js/cadastro.js"></script>

</body>

</html>
