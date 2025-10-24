-- Script para criar tabela de assinaturas
-- Execute este script no banco de dados nixcom

-- Tabela de assinaturas (subscriptions)
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `plano_id` int(11) NOT NULL,
  `status` enum('pending','paid_awaiting_admin','active','suspended','cancelled','failed') DEFAULT 'pending',
  `provider` enum('mercadopago','stripe','paypal') DEFAULT 'mercadopago',
  `provider_ref` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'BRL',
  `period` enum('1_mes','6_meses','12_meses') NOT NULL,
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `qr_code_base64` text,
  `qr_text` text,
  `webhook_data` text,
  `metadata` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `plano_id` (`plano_id`),
  KEY `status` (`status`),
  KEY `provider_ref` (`provider_ref`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_subscriptions_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adicionar coluna can_create_ads na tabela usuarios
ALTER TABLE `usuarios` 
ADD COLUMN IF NOT EXISTS `can_create_ads` tinyint(1) DEFAULT 1;

-- Adicionar índice para can_create_ads
ALTER TABLE `usuarios` 
ADD KEY IF NOT EXISTS `can_create_ads` (`can_create_ads`);


