-- Tabelas de segurança para o sistema de login
-- Execute este script no banco de dados nixcom

-- Tabela para registrar tentativas de login
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_agent TEXT,
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_time (attempt_time)
);

-- Tabela para tokens de sessão (opcional, para maior segurança)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_user_token (user_id, session_token),
    INDEX idx_expires (expires_at)
);

-- Tabela para logs de segurança
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_created (created_at)
);

-- Tabela para configurações de segurança
CREATE TABLE IF NOT EXISTS security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir configurações padrão de segurança
INSERT INTO security_settings (setting_key, setting_value, description) VALUES
('session_timeout', '1800', 'Timeout da sessão em segundos (30 minutos)'),
('max_login_attempts', '5', 'Máximo de tentativas de login por IP'),
('lockout_time', '900', 'Tempo de bloqueio em segundos (15 minutos)'),
('require_https', '0', 'Requer HTTPS para login (0=desabilitado, 1=habilitado)'),
('session_regenerate', '1', 'Regenerar ID da sessão a cada login (0=desabilitado, 1=habilitado)'),
('log_security_events', '1', 'Registrar eventos de segurança (0=desabilitado, 1=habilitado)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Limpar tentativas antigas (executar periodicamente)
-- DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);
-- DELETE FROM user_sessions WHERE expires_at < NOW();
-- DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
