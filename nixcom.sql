-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 23/10/2025 às 05:19
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `nixcom`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `anuncios`
--

CREATE TABLE `anuncios` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `state_id` char(2) NOT NULL COMMENT 'ID do estado (UF)',
  `city_id` char(10) NOT NULL COMMENT 'ID da cidade',
  `neighborhood_id` varchar(255) NOT NULL COMMENT 'ID do bairro',
  `work_name` varchar(255) DEFAULT NULL,
  `age` tinyint(3) UNSIGNED NOT NULL,
  `height_m` decimal(3,2) NOT NULL,
  `weight_kg` smallint(5) UNSIGNED NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) NOT NULL,
  `ethnicity` varchar(100) DEFAULT NULL,
  `eye_color` varchar(50) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `price_15min` decimal(10,2) DEFAULT NULL,
  `price_30min` decimal(10,2) DEFAULT NULL,
  `price_1h` decimal(10,2) DEFAULT NULL,
  `cover_photo_path` varchar(255) DEFAULT NULL,
  `plan_type` enum('free','premium') NOT NULL DEFAULT 'free',
  `confirmation_video_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','pending','rejected','deleted','pausado') NOT NULL DEFAULT 'pending',
  `categoria` enum('mulher','homem','trans') DEFAULT 'mulher',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `fixed_confirmation_video_path` varchar(255) DEFAULT NULL,
  `visits` int(11) DEFAULT 0,
  `service_name` varchar(255) NOT NULL DEFAULT '',
  `neighborhood_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anuncio_aparencias`
--

CREATE TABLE `anuncio_aparencias` (
  `anuncio_id` int(11) NOT NULL,
  `aparencia_item` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anuncio_audios`
--

CREATE TABLE `anuncio_audios` (
  `id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anuncio_formas_pagamento`
--

CREATE TABLE `anuncio_formas_pagamento` (
  `anuncio_id` int(11) NOT NULL,
  `forma_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anuncio_fotos`
--

CREATE TABLE `anuncio_fotos` (
  `id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `order_index` tinyint(3) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anuncio_idiomas`
--

CREATE TABLE `anuncio_idiomas` (
  `anuncio_id` int(11) NOT NULL,
  `idioma_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anuncio_locais_atendimento`
--

CREATE TABLE `anuncio_locais_atendimento` (
  `anuncio_id` int(11) NOT NULL,
  `local_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anuncio_servicos_oferecidos`
--

CREATE TABLE `anuncio_servicos_oferecidos` (
  `anuncio_id` int(11) NOT NULL,
  `servico_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `anuncio_videos`
--

CREATE TABLE `anuncio_videos` (
  `id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `assinaturas`
--

CREATE TABLE `assinaturas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plan_type` enum('free','basic','premium') NOT NULL,
  `valor_mensal` decimal(10,2) NOT NULL,
  `status` enum('ativa','inativa','suspensa','cancelada') DEFAULT 'ativa',
  `data_inicio` date NOT NULL,
  `data_fim` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `bairro`
--

CREATE TABLE `bairro` (
  `Id` int(11) NOT NULL,
  `Codigo` char(10) NOT NULL,
  `Nome` varchar(255) NOT NULL,
  `Uf` char(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `charges`
--

CREATE TABLE `charges` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `charge_id` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `plan_type` enum('basic','premium') NOT NULL,
  `status` enum('pending','paid','expired','cancelled') DEFAULT 'pending',
  `pix_qr_code` text DEFAULT NULL,
  `pix_copy_paste` text DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cidade`
--

CREATE TABLE `cidade` (
  `Id` int(11) NOT NULL,
  `Codigo` int(11) NOT NULL,
  `Nome` varchar(255) NOT NULL,
  `Uf` char(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios_anuncios`
--

CREATE TABLE `comentarios_anuncios` (
  `id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `comentario` text NOT NULL,
  `status` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estado`
--

CREATE TABLE `estado` (
  `Id` int(11) NOT NULL,
  `CodigoUf` int(11) NOT NULL,
  `Nome` varchar(50) NOT NULL,
  `Uf` char(2) NOT NULL,
  `Regiao` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `formulario_contato`
--

CREATE TABLE `formulario_contato` (
  `id` int(11) NOT NULL,
  `nomeCompleto` varchar(220) NOT NULL,
  `email` varchar(220) NOT NULL,
  `telefone` varchar(15) NOT NULL,
  `assunto` varchar(220) NOT NULL,
  `mensagem` text NOT NULL,
  `dataCriacao` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `login_tentativas`
--

CREATE TABLE `login_tentativas` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sucesso` tinyint(1) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `data_hora` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens_diretas`
--

CREATE TABLE `mensagens_diretas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `assunto` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `resposta` text DEFAULT NULL,
  `lida_pelo_usuario` tinyint(1) DEFAULT 0,
  `status` enum('pendente','respondida','fechada') DEFAULT 'pendente',
  `prioridade` enum('baixa','normal','alta','urgente') DEFAULT 'normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `tipo` enum('anuncio_pendente','comentario_pendente','mensagem_direta','formulario_contato') NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `anuncio_id` int(11) DEFAULT NULL,
  `comentario_id` int(11) DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `metodo` varchar(50) NOT NULL,
  `gateway` varchar(50) DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado','cancelado') DEFAULT 'pendente',
  `transaction_id` varchar(255) DEFAULT NULL,
  `pix_code` text DEFAULT NULL,
  `pix_qr_code` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_id` varchar(255) NOT NULL,
  `plan_type` enum('basic','premium') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `external_reference` varchar(255) NOT NULL,
  `qr_code` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `payment_logs`
--

CREATE TABLE `payment_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_id` varchar(255) NOT NULL,
  `subscription_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `plan_type` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `external_reference` varchar(255) DEFAULT NULL,
  `mercado_pago_data` text DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('free','basic','premium') NOT NULL,
  `descricao` text DEFAULT NULL,
  `preco_mensal` decimal(10,2) DEFAULT 0.00,
  `preco_6_meses` decimal(10,2) DEFAULT 0.00,
  `preco_12_meses` decimal(10,2) DEFAULT 0.00,
  `max_fotos` int(11) DEFAULT 2,
  `max_videos` int(11) DEFAULT 0,
  `max_audios` int(11) DEFAULT 0,
  `recursos_extras` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
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
  `qr_code_base64` text DEFAULT NULL,
  `qr_text` text DEFAULT NULL,
  `webhook_data` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('usuario','administrador') NOT NULL DEFAULT 'usuario',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `status` enum('ativo','inativo','suspenso','bloqueado') NOT NULL DEFAULT 'ativo',
  `ultimo_acesso` datetime DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'usuario.png',
  `plan_type` enum('free','basic','premium') NOT NULL DEFAULT 'free',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `registration_ip` varchar(45) DEFAULT NULL,
  `has_anuncio` tinyint(1) NOT NULL DEFAULT 0,
  `anuncio_status` varchar(20) NOT NULL DEFAULT 'not_found',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_id` varchar(255) DEFAULT NULL,
  `plano_atual_id` int(11) DEFAULT NULL,
  `plano_expira_em` datetime DEFAULT NULL,
  `can_create_ads` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuario_planos`
--

CREATE TABLE `usuario_planos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `plano_id` int(11) NOT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `valor_pago` decimal(10,2) NOT NULL,
  `status` enum('ativo','expirado','cancelado') DEFAULT 'ativo',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `anuncios`
--
ALTER TABLE `anuncios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_id` (`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `anuncio_aparencias`
--
ALTER TABLE `anuncio_aparencias`
  ADD PRIMARY KEY (`anuncio_id`,`aparencia_item`);

--
-- Índices de tabela `anuncio_audios`
--
ALTER TABLE `anuncio_audios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anuncio_id` (`anuncio_id`);

--
-- Índices de tabela `anuncio_formas_pagamento`
--
ALTER TABLE `anuncio_formas_pagamento`
  ADD PRIMARY KEY (`anuncio_id`,`forma_name`);

--
-- Índices de tabela `anuncio_fotos`
--
ALTER TABLE `anuncio_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anuncio_id` (`anuncio_id`);

--
-- Índices de tabela `anuncio_idiomas`
--
ALTER TABLE `anuncio_idiomas`
  ADD PRIMARY KEY (`anuncio_id`,`idioma_name`);

--
-- Índices de tabela `anuncio_locais_atendimento`
--
ALTER TABLE `anuncio_locais_atendimento`
  ADD PRIMARY KEY (`anuncio_id`,`local_name`);

--
-- Índices de tabela `anuncio_servicos_oferecidos`
--
ALTER TABLE `anuncio_servicos_oferecidos`
  ADD PRIMARY KEY (`anuncio_id`,`servico_name`);

--
-- Índices de tabela `anuncio_videos`
--
ALTER TABLE `anuncio_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anuncio_id` (`anuncio_id`);

--
-- Índices de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Índices de tabela `bairro`
--
ALTER TABLE `bairro`
  ADD PRIMARY KEY (`Id`);

--
-- Índices de tabela `charges`
--
ALTER TABLE `charges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `charge_id` (`charge_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_charge_id` (`charge_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_plan_type` (`plan_type`);

--
-- Índices de tabela `cidade`
--
ALTER TABLE `cidade`
  ADD PRIMARY KEY (`Id`);

--
-- Índices de tabela `comentarios_anuncios`
--
ALTER TABLE `comentarios_anuncios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anuncio_id` (`anuncio_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`Id`);

--
-- Índices de tabela `formulario_contato`
--
ALTER TABLE `formulario_contato`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `login_tentativas`
--
ALTER TABLE `login_tentativas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `mensagens_diretas`
--
ALTER TABLE `mensagens_diretas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_admin_id` (`admin_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `anuncio_id` (`anuncio_id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Índices de tabela `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_id` (`payment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `external_reference` (`external_reference`);

--
-- Índices de tabela `payment_logs`
--
ALTER TABLE `payment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo` (`tipo`);

--
-- Índices de tabela `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plano_id` (`plano_id`),
  ADD KEY `status` (`status`),
  ADD KEY `provider_ref` (`provider_ref`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `plano_atual_id` (`plano_atual_id`),
  ADD KEY `plano_expira_em` (`plano_expira_em`),
  ADD KEY `payment_status` (`payment_status`),
  ADD KEY `can_create_ads` (`can_create_ads`);

--
-- Índices de tabela `usuario_planos`
--
ALTER TABLE `usuario_planos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `plano_id` (`plano_id`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `anuncios`
--
ALTER TABLE `anuncios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `anuncio_audios`
--
ALTER TABLE `anuncio_audios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `anuncio_fotos`
--
ALTER TABLE `anuncio_fotos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `anuncio_videos`
--
ALTER TABLE `anuncio_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `assinaturas`
--
ALTER TABLE `assinaturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `bairro`
--
ALTER TABLE `bairro`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `charges`
--
ALTER TABLE `charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cidade`
--
ALTER TABLE `cidade`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `comentarios_anuncios`
--
ALTER TABLE `comentarios_anuncios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estado`
--
ALTER TABLE `estado`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `formulario_contato`
--
ALTER TABLE `formulario_contato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `login_tentativas`
--
ALTER TABLE `login_tentativas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `mensagens_diretas`
--
ALTER TABLE `mensagens_diretas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `payment_logs`
--
ALTER TABLE `payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuario_planos`
--
ALTER TABLE `usuario_planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `anuncios`
--
ALTER TABLE `anuncios`
  ADD CONSTRAINT `fk_anuncios_user_id` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `anuncio_aparencias`
--
ALTER TABLE `anuncio_aparencias`
  ADD CONSTRAINT `fk_aparencias_anuncio_id` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `anuncio_audios`
--
ALTER TABLE `anuncio_audios`
  ADD CONSTRAINT `fk_audios_anuncio_id` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `anuncio_formas_pagamento`
--
ALTER TABLE `anuncio_formas_pagamento`
  ADD CONSTRAINT `fk_pagamentos_anuncio_id` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `anuncio_fotos`
--
ALTER TABLE `anuncio_fotos`
  ADD CONSTRAINT `fk_fotos_anuncio_id` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `anuncio_idiomas`
--
ALTER TABLE `anuncio_idiomas`
  ADD CONSTRAINT `fk_idiomas_anuncio_id` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `anuncio_locais_atendimento`
--
ALTER TABLE `anuncio_locais_atendimento`
  ADD CONSTRAINT `fk_locais_anuncio_id` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `anuncio_servicos_oferecidos`
--
ALTER TABLE `anuncio_servicos_oferecidos`
  ADD CONSTRAINT `fk_servicos_anuncio_id` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `anuncio_videos`
--
ALTER TABLE `anuncio_videos`
  ADD CONSTRAINT `fk_videos_anuncio_id` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `charges`
--
ALTER TABLE `charges`
  ADD CONSTRAINT `charges_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `comentarios_anuncios`
--
ALTER TABLE `comentarios_anuncios`
  ADD CONSTRAINT `comentarios_anuncios_ibfk_1` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_anuncios_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `mensagens_diretas`
--
ALTER TABLE `mensagens_diretas`
  ADD CONSTRAINT `mensagens_diretas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensagens_diretas_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `notificacoes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificacoes_ibfk_2` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_user_id` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `fk_subscriptions_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `usuario_planos`
--
ALTER TABLE `usuario_planos`
  ADD CONSTRAINT `fk_usuario_planos_plano` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_planos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
