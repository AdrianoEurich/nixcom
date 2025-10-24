-- Script para criar tabelas de planos e pagamentos
-- Execute este script no banco de dados nixcom

-- Tabela de planos
CREATE TABLE IF NOT EXISTS `planos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('free','basic','premium') NOT NULL,
  `descricao` text,
  `preco_mensal` decimal(10,2) DEFAULT 0.00,
  `preco_6_meses` decimal(10,2) DEFAULT 0.00,
  `preco_12_meses` decimal(10,2) DEFAULT 0.00,
  `max_fotos` int(11) DEFAULT 2,
  `max_videos` int(11) DEFAULT 0,
  `max_audios` int(11) DEFAULT 0,
  `recursos_extras` text,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserir planos padrão
INSERT INTO `planos` (`nome`, `tipo`, `descricao`, `preco_mensal`, `preco_6_meses`, `preco_12_meses`, `max_fotos`, `max_videos`, `max_audios`, `recursos_extras`) VALUES
('Plano Gratuito', 'free', 'Plano básico gratuito com recursos limitados', 0.00, 0.00, 0.00, 2, 0, 0, 'Painel administrativo, Criação de anúncios'),
('Plano Básico', 'basic', 'Plano intermediário com mais recursos', 29.90, 179.40, 358.80, 20, 0, 0, 'Painel administrativo, Criação de anúncios, 20 fotos na galeria'),
('Plano Premium', 'premium', 'Plano completo com todos os recursos', 49.90, 299.40, 598.80, 20, 3, 3, 'Painel administrativo, Criação de anúncios, 20 fotos na galeria, 3 vídeos, 3 áudios');

-- Tabela de pagamentos
CREATE TABLE IF NOT EXISTS `pagamentos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `plano_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `periodo` enum('1_mes','6_meses','12_meses') NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `metodo_pagamento` enum('pix','cartao','boleto') DEFAULT 'pix',
  `transaction_id` varchar(255) DEFAULT NULL,
  `mercadopago_payment_id` varchar(255) DEFAULT NULL,
  `qr_code` text,
  `qr_code_base64` text,
  `pix_copy_paste` text,
  `data_pagamento` datetime DEFAULT NULL,
  `data_expiracao` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `plano_id` (`plano_id`),
  KEY `status` (`status`),
  KEY `transaction_id` (`transaction_id`),
  CONSTRAINT `fk_pagamentos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pagamentos_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela de histórico de planos do usuário
CREATE TABLE IF NOT EXISTS `usuario_planos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `plano_id` int(11) NOT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `status` enum('ativo','expirado','cancelado') DEFAULT 'ativo',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `plano_id` (`plano_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_usuario_planos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_usuario_planos_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adicionar colunas necessárias na tabela usuarios se não existirem
ALTER TABLE `usuarios` 
ADD COLUMN IF NOT EXISTS `plano_atual_id` int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `plano_expira_em` datetime DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `payment_status` enum('pending','approved','rejected') DEFAULT 'pending';

-- Adicionar índices para melhor performance
ALTER TABLE `usuarios` 
ADD KEY IF NOT EXISTS `plano_atual_id` (`plano_atual_id`),
ADD KEY IF NOT EXISTS `plano_expira_em` (`plano_expira_em`),
ADD KEY IF NOT EXISTS `payment_status` (`payment_status`);

-- Adicionar foreign key para plano_atual_id (após criar a tabela planos)
-- ALTER TABLE `usuarios` 
-- ADD CONSTRAINT `fk_usuarios_plano_atual` 
-- FOREIGN KEY (`plano_atual_id`) REFERENCES `planos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
