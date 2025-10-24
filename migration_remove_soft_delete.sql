-- Script de Migração: Remoção de Soft Delete e Implementação de Exclusão Definitiva
-- Data: 2025-01-03
-- Descrição: Remove campos deleted_at e implementa exclusão definitiva

-- 1. Remover campo deleted_at da tabela anuncios
ALTER TABLE `anuncios` DROP COLUMN `deleted_at`;

-- 2. Remover campo deleted_at da tabela usuarios
ALTER TABLE `usuarios` DROP COLUMN `deleted_at`;

-- 3. Verificar se as foreign keys estão configuradas corretamente para CASCADE
-- (As foreign keys já devem estar configuradas com ON DELETE CASCADE)

-- 4. Verificar se o UNIQUE KEY uk_user_id está presente
-- (Deve estar presente para garantir 1:1 user-to-ad)

-- 5. Comentário: A partir de agora, exclusão de usuário = exclusão definitiva
-- O CASCADE irá excluir automaticamente todos os anúncios relacionados

COMMIT;

