-- Script para adicionar rastreamento de estornos ao sistema
-- Execute este script no banco de dados nixcom

-- Adicionar colunas para rastreamento de estornos na tabela pagamentos
ALTER TABLE `pagamentos` 
ADD COLUMN `refund_id` varchar(255) DEFAULT NULL COMMENT 'ID do estorno no Mercado Pago',
ADD COLUMN `refund_status` enum('none','pending','approved','rejected') DEFAULT 'none' COMMENT 'Status do estorno',
ADD COLUMN `refund_amount` decimal(10,2) DEFAULT NULL COMMENT 'Valor do estorno',
ADD COLUMN `refund_reason` text DEFAULT NULL COMMENT 'Motivo do estorno',
ADD COLUMN `refund_requested_at` datetime DEFAULT NULL COMMENT 'Data da solicitação de estorno',
ADD COLUMN `refund_processed_at` datetime DEFAULT NULL COMMENT 'Data do processamento do estorno',
ADD COLUMN `refund_processed_by` int(11) DEFAULT NULL COMMENT 'ID do administrador que processou o estorno',
ADD COLUMN `refund_notes` text DEFAULT NULL COMMENT 'Observações sobre o estorno';

-- Adicionar índices para melhor performance
ALTER TABLE `pagamentos` 
ADD INDEX `idx_refund_status` (`refund_status`),
ADD INDEX `idx_refund_id` (`refund_id`);

-- Criar tabela para histórico de estornos
CREATE TABLE IF NOT EXISTS `refund_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `refund_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_by` enum('user','admin') NOT NULL DEFAULT 'user',
  `processed_by` int(11) DEFAULT NULL,
  `mercadopago_response` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`),
  KEY `user_id` (`user_id`),
  KEY `refund_id` (`refund_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_refund_history_payment` FOREIGN KEY (`payment_id`) REFERENCES `pagamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_refund_history_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Criar tabela para solicitações de estorno dos usuários
CREATE TABLE IF NOT EXISTS `refund_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `payment_id` (`payment_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_refund_requests_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_refund_requests_payment` FOREIGN KEY (`payment_id`) REFERENCES `pagamentos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
