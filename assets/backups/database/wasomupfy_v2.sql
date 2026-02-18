-- ============================================================
-- WASOM UPFY v2.0 - BANCO DE DADOS COMPLETO
-- Gerado em: 2025 | Engine: InnoDB | Charset: utf8mb4
-- ============================================================
-- MÓDULOS COBERTOS:
--   [1] Plataforma & CMS
--   [2] Utilizadores & Segurança
--   [3] Funcionários (Admin)
--   [4] Planos & Pagamentos
--   [5] Artistas & Colaboradores
--   [6] Lançamentos (Álbuns & Faixas)
--   [7] Distribuição & Lojas Digitais
--   [8] Análises & Streams
--   [9] Finanças (Royalties, Saques, Transações)
--  [10] Notificações & Mensagens
--  [11] Blog & Conteúdo
--  [12] Integrações (YouTube)
--  [13] Suporte & FAQ
--  [14] Auditoria & Logs
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `wasomupfy` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `wasomupfy`;


-- ============================================================
-- [1] PLATAFORMA & CMS
-- ============================================================

-- Status e configuração global da plataforma (editável pelo admin CMS)
CREATE TABLE `_platform` (
  `id_platform`         INT(11)       NOT NULL AUTO_INCREMENT,
  `id_employees`        INT(11)       DEFAULT NULL COMMENT 'Último admin que alterou',
  `status`              ENUM('active','maintenance','blocked','unauthorized') NOT NULL DEFAULT 'active',
  `maintenance_msg`     TEXT          DEFAULT NULL COMMENT 'Mensagem exibida em manutenção',
  `allow_register`      TINYINT(1)    NOT NULL DEFAULT 1 COMMENT 'Abrir/fechar cadastros',
  `allow_login`         TINYINT(1)    NOT NULL DEFAULT 1,
  `royalty_percentage`  DECIMAL(5,2)  NOT NULL DEFAULT 90.00 COMMENT '% retido pelo artista',
  `platform_fee`        DECIMAL(5,2)  NOT NULL DEFAULT 10.00 COMMENT '% retido pela Wasom',
  `currency_default`    ENUM('AOA','USD','EUR') NOT NULL DEFAULT 'AOA',
  `usd_to_aoa_rate`     DECIMAL(10,2) NOT NULL DEFAULT 900.00 COMMENT 'Taxa de câmbio USD→AOA',
  `contact_email`       VARCHAR(255)  DEFAULT 'suporte@wasomupfy.com',
  `stores_count`        INT(11)       NOT NULL DEFAULT 150 COMMENT 'Qtd lojas exibida no site',
  `version`             VARCHAR(10)   NOT NULL DEFAULT '2.0',
  `creat_platform`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_platform`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `_platform` (`id_employees`, `status`, `allow_register`, `allow_login`, `royalty_percentage`, `platform_fee`, `currency_default`, `usd_to_aoa_rate`, `contact_email`, `stores_count`, `version`)
VALUES (1, 'active', 1, 1, 90.00, 10.00, 'AOA', 900.00, 'suporte@wasomupfy.com', 157, '2.0');


-- Configurações gerais CMS (textos, redes sociais, SEO)
CREATE TABLE `_site_config` (
  `id_config`     INT(11)       NOT NULL AUTO_INCREMENT,
  `config_key`    VARCHAR(100)  NOT NULL UNIQUE COMMENT 'Ex: site_name, facebook_url, hero_title',
  `config_value`  TEXT          DEFAULT NULL,
  `config_group`  VARCHAR(50)   DEFAULT 'general' COMMENT 'general | social | seo | email | payment',
  `is_public`     TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = visível no frontend via API',
  `modif_config`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `_site_config` (`config_key`, `config_value`, `config_group`, `is_public`) VALUES
('site_name',           'Wasom Upfy',                              'general', 1),
('site_tagline',        'Distribua sua música para o mundo',        'general', 1),
('facebook_url',        'https://facebook.com/wasomupfy',           'social',  1),
('instagram_url',       'https://instagram.com/wasomupfy',          'social',  1),
('youtube_url',         '',                                          'social',  1),
('tiktok_url',          '',                                          'social',  1),
('whatsapp_number',     '+244 922 000 000',                          'social',  1),
('support_email',       'suporte@wasomupfy.com',                    'email',   1),
('noreply_email',       'noreply@wasomupfy.com',                    'email',   0),
('smtp_host',           '',                                          'email',   0),
('smtp_port',           '587',                                       'email',   0),
('smtp_user',           '',                                          'email',   0),
('smtp_pass',           '',                                          'email',   0),
('payment_gateway',     'manual',                                    'payment', 0),
('min_withdrawal',      '5000',                                      'payment', 1),
('max_login_attempts',  '5',                                         'general', 0);


-- Lojas digitais (editável pelo admin, base para relatórios)
CREATE TABLE `_store` (
  `id_store`        INT(11)       NOT NULL AUTO_INCREMENT,
  `name_store`      VARCHAR(100)  NOT NULL,
  `slug_store`      VARCHAR(100)  NOT NULL UNIQUE COMMENT 'spotify, apple-music, etc.',
  `logo_store`      VARCHAR(255)  DEFAULT NULL,
  `url_store`       VARCHAR(255)  DEFAULT NULL,
  `type_store`      ENUM('streaming','download','social','video') NOT NULL DEFAULT 'streaming',
  `region_store`    VARCHAR(100)  DEFAULT 'Global',
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `display_order`   INT(11)       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_store`),
  UNIQUE KEY `slug_store` (`slug_store`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `_store` (`name_store`, `slug_store`, `type_store`, `region_store`, `display_order`) VALUES
('Spotify',        'spotify',        'streaming', 'Global', 1),
('Apple Music',    'apple-music',    'streaming', 'Global', 2),
('Amazon Music',   'amazon-music',   'streaming', 'Global', 3),
('Deezer',         'deezer',         'streaming', 'Global', 4),
('Tidal',          'tidal',          'streaming', 'Global', 5),
('Boomplay',       'boomplay',       'streaming', 'Africa', 6),
('YouTube Music',  'youtube-music',  'streaming', 'Global', 7),
('iTunes',         'itunes',         'download',  'Global', 8),
('TikTok',         'tiktok',         'social',    'Global', 9),
('Facebook',       'facebook',       'social',    'Global', 10),
('Snapchat',       'snapchat',       'social',    'Global', 11),
('Pandora',        'pandora',        'streaming', 'USA',    12),
('Resso',          'resso',          'streaming', 'Asia',   13),
('Claro Music',    'claro-music',    'streaming', 'LATAM',  14),
('YouTube',        'youtube',        'video',     'Global', 15);


-- ============================================================
-- [2] UTILIZADORES & SEGURANÇA
-- ============================================================

-- Tabela principal de utilizadores
CREATE TABLE `_users` (
  `id_users`            INT(11)       NOT NULL AUTO_INCREMENT,
  `ip_register`         VARCHAR(45)   DEFAULT NULL COMMENT 'IP no momento do cadastro',
  `first_name`          VARCHAR(50)   NOT NULL,
  `second_name`         VARCHAR(80)   DEFAULT NULL,
  `user_name`           VARCHAR(60)   DEFAULT NULL UNIQUE,
  `name_artist_band`    VARCHAR(100)  DEFAULT NULL COMMENT 'Nome artístico ou da banda',
  `gender`              ENUM('M','F','Outro') DEFAULT NULL,
  `birth_date`          DATE          DEFAULT NULL,
  `tel_user`            VARCHAR(20)   DEFAULT NULL,
  `country_user`        VARCHAR(60)   DEFAULT NULL,
  `city_user`           VARCHAR(60)   DEFAULT NULL,
  `email_user`          VARCHAR(255)  NOT NULL UNIQUE,
  `email_verified`      TINYINT(1)    NOT NULL DEFAULT 0,
  `email_verified_at`   DATETIME      DEFAULT NULL,
  `email_user_other`    VARCHAR(255)  DEFAULT NULL UNIQUE,
  `url_user`            VARCHAR(255)  DEFAULT NULL,
  `photo_user`          VARCHAR(255)  DEFAULT NULL,
  `password_user`       VARCHAR(255)  NOT NULL,
  `about_user`          TEXT          DEFAULT NULL,
  `plan_selected`       INT(11)       DEFAULT NULL COMMENT 'FK para _plans — plano escolhido no cadastro',
  `status_user`         ENUM('active','inactive','blocked','processing','suspended','fraud','pending_plan') NOT NULL DEFAULT 'processing'
                        COMMENT 'pending_plan = cadastrado mas sem plano pago',
  `deactivation_user`   DATETIME      DEFAULT NULL COMMENT 'Data de desativação voluntária',
  `creat_user`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_user`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_users`),
  KEY `idx_email_user` (`email_user`),
  KEY `idx_status_user` (`status_user`),
  KEY `idx_plan_selected` (`plan_selected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Segurança de sessão e controlo de login dos utilizadores
CREATE TABLE `_users_security` (
  `id_security`             INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`                INT(11)       NOT NULL,
  `recovery_key`            VARCHAR(60)   NOT NULL UNIQUE COMMENT 'Chave de recuperação de conta',
  `remember_token`          VARCHAR(255)  DEFAULT NULL,
  `email_verify_token`      VARCHAR(100)  DEFAULT NULL,
  `email_verify_expires`    DATETIME      DEFAULT NULL,
  `reset_password_token`    VARCHAR(100)  DEFAULT NULL,
  `reset_password_expires`  DATETIME      DEFAULT NULL,
  `login_attempts`          INT(11)       NOT NULL DEFAULT 0,
  `block_until`             DATETIME      DEFAULT NULL COMMENT 'Bloqueio temporário até esta data/hora',
  `block_level`             TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '0=livre, 1=5min, 2=15min, 3=30min',
  `is_fraud_blocked`        TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Bloqueio permanente por fraude',
  `last_login_at`           DATETIME      DEFAULT NULL,
  `last_login_ip`           VARCHAR(45)   DEFAULT NULL,
  `last_failed_at`          DATETIME      DEFAULT NULL,
  `two_factor_enabled`      TINYINT(1)    NOT NULL DEFAULT 0,
  `two_factor_secret`       VARCHAR(100)  DEFAULT NULL,
  `lockscreen`              TINYINT(1)    NOT NULL DEFAULT 0,
  `access_code`             VARCHAR(6)    DEFAULT NULL COMMENT 'PIN de lockscreen',
  `creat_security`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_security`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_security`),
  UNIQUE KEY `id_users` (`id_users`),
  KEY `idx_recovery_key` (`recovery_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Histórico de sessões/acessos dos utilizadores
CREATE TABLE `_users_sessions` (
  `id_session`      INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`        INT(11)       NOT NULL,
  `session_token`   VARCHAR(255)  NOT NULL UNIQUE,
  `ip_address`      VARCHAR(45)   DEFAULT NULL,
  `user_agent`      TEXT          DEFAULT NULL,
  `country`         VARCHAR(60)   DEFAULT NULL,
  `city`            VARCHAR(60)   DEFAULT NULL,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  `last_activity`   DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `creat_session`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_session`),
  KEY `id_users` (`id_users`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Verificação de e-mail e reset de senha (tokens com expiração)
CREATE TABLE `_users_tokens` (
  `id_token`        INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`        INT(11)       NOT NULL,
  `token`           VARCHAR(128)  NOT NULL UNIQUE,
  `type`            ENUM('email_verify','password_reset','plan_redirect') NOT NULL,
  `extra_data`      VARCHAR(255)  DEFAULT NULL COMMENT 'Ex: id do plano para redirecionamento pós-cadastro',
  `is_used`         TINYINT(1)    NOT NULL DEFAULT 0,
  `expires_at`      DATETIME      NOT NULL,
  `creat_token`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_token`),
  KEY `id_users` (`id_users`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [3] FUNCIONÁRIOS (ADMIN)
-- ============================================================

CREATE TABLE `_employees` (
  `id_employees`          INT(11)       NOT NULL AUTO_INCREMENT,
  `first_name`            VARCHAR(50)   NOT NULL,
  `second_name`           VARCHAR(80)   DEFAULT NULL,
  `user_employees`        VARCHAR(60)   DEFAULT NULL UNIQUE,
  `gender`                ENUM('M','F') NOT NULL,
  `tel_employees`         VARCHAR(20)   DEFAULT NULL,
  `email_employees`       VARCHAR(255)  NOT NULL UNIQUE,
  `email_employees_other` VARCHAR(255)  DEFAULT NULL,
  `url_employees`         VARCHAR(255)  DEFAULT NULL,
  `photo_employees`       VARCHAR(255)  DEFAULT NULL,
  `password_employees`    VARCHAR(255)  NOT NULL,
  `about_employees`       TEXT          DEFAULT NULL,
  `role`                  ENUM('super_admin','admin','editor','support') NOT NULL DEFAULT 'editor'
                          COMMENT 'super_admin = acesso total, support = só visualizar',
  `status_employees`      ENUM('active','inactive','blocked','processing','suspended') NOT NULL DEFAULT 'processing',
  `deactivation_at`       DATETIME      DEFAULT NULL,
  `creat_employees`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_employees`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_employees`),
  UNIQUE KEY `email_employees` (`email_employees`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `_employees` (`id_employees`, `first_name`, `second_name`, `user_employees`, `gender`, `tel_employees`, `email_employees`, `photo_employees`, `password_employees`, `role`, `status_employees`)
VALUES (1, 'José Mbenga', 'da Costa', 'josmbengadacosta942', 'M', '+244 922030116', 'josembengadacosta@gmail.com', '8e656915cb98372eeb70103773f2c1fa.jpg', '$2y$10$Kz6ZzfW6DdrMU9z0r83yR.ycnJPfqDl9AZgbxTXYQVXAXJXppUAGm', 'super_admin', 'active');


-- Segurança de login dos funcionários
CREATE TABLE `_employees_security` (
  `id_sec_emp`              INT(11)       NOT NULL AUTO_INCREMENT,
  `id_employees`            INT(11)       NOT NULL,
  `recovery_key`            VARCHAR(60)   NOT NULL UNIQUE,
  `remember_token`          VARCHAR(255)  DEFAULT NULL,
  `reset_password_token`    VARCHAR(100)  DEFAULT NULL,
  `reset_password_expires`  DATETIME      DEFAULT NULL,
  `login_attempts`          INT(11)       NOT NULL DEFAULT 0,
  `block_until`             DATETIME      DEFAULT NULL,
  `block_level`             TINYINT(1)    NOT NULL DEFAULT 0,
  `is_fraud_blocked`        TINYINT(1)    NOT NULL DEFAULT 0,
  `last_login_at`           DATETIME      DEFAULT NULL,
  `last_login_ip`           VARCHAR(45)   DEFAULT NULL,
  `lockscreen`              TINYINT(1)    NOT NULL DEFAULT 0,
  `access_code`             VARCHAR(6)    DEFAULT NULL,
  `creat_sec_emp`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_sec_emp`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_sec_emp`),
  UNIQUE KEY `id_employees` (`id_employees`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `_employees_security` (`id_employees`, `recovery_key`, `login_attempts`, `access_code`)
VALUES (1, 'kazRFeUVPkL83OOK25Rswj', 0, '295088');


-- Permissões granulares por funcionário (para escalabilidade futura)
CREATE TABLE `_employees_permissions` (
  `id_permission`   INT(11)       NOT NULL AUTO_INCREMENT,
  `id_employees`    INT(11)       NOT NULL,
  `permission`      VARCHAR(100)  NOT NULL COMMENT 'Ex: users.edit, finances.view, music.approve',
  `granted`         TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_permission`),
  UNIQUE KEY `emp_perm` (`id_employees`, `permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [4] PLANOS & PAGAMENTOS
-- ============================================================

-- Definição dos planos (100% editável pelo CMS)
CREATE TABLE `_plans` (
  `id_plan`             INT(11)         NOT NULL AUTO_INCREMENT,
  `slug_plan`           VARCHAR(50)     NOT NULL UNIQUE COMMENT 'single, album, artist, label',
  `name_plan`           VARCHAR(100)    NOT NULL,
  `description_plan`    TEXT            DEFAULT NULL,
  `type_plan`           ENUM('per_release','subscription') NOT NULL DEFAULT 'per_release'
                        COMMENT 'per_release=paga por lançamento, subscription=anual/recorrente',
  `price_plan`          DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT 'Preço base em AOA',
  `price_usd`           DECIMAL(10,2)   DEFAULT NULL COMMENT 'Preço base em USD (opcional)',
  `price_annual`        DECIMAL(10,2)   DEFAULT NULL COMMENT 'Preço pacote 10 releases (se per_release)',
  `annual_qty`          INT(11)         DEFAULT NULL COMMENT 'Qtd de releases incluídos no pacote anual',
  `validity_days`       INT(11)         DEFAULT NULL COMMENT 'Validade em dias (NULL = sem expiração)',
  `max_artists`         INT(11)         DEFAULT 1 COMMENT 'Número máximo de artistas/perfis',
  `max_releases`        INT(11)         DEFAULT NULL COMMENT 'NULL = ilimitado',
  `max_tracks_per_release` INT(11)      DEFAULT NULL COMMENT 'NULL = ilimitado',
  `royalty_rate`        DECIMAL(5,2)    NOT NULL DEFAULT 90.00 COMMENT '% dos royalties para o artista',
  `img_plan`            VARCHAR(255)    DEFAULT NULL COMMENT 'Imagem do card do plano',
  `badge_text`          VARCHAR(50)     DEFAULT NULL COMMENT 'Ex: Mais Popular, Recomendado',
  `is_featured`         TINYINT(1)      NOT NULL DEFAULT 0,
  `is_active`           TINYINT(1)      NOT NULL DEFAULT 1,
  `display_order`       INT(11)         NOT NULL DEFAULT 0,
  `creat_plan`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_plan`          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_plan`),
  UNIQUE KEY `slug_plan` (`slug_plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `_plans` (`slug_plan`, `name_plan`, `description_plan`, `type_plan`, `price_plan`, `price_annual`, `annual_qty`, `validity_days`, `max_artists`, `max_releases`, `max_tracks_per_release`, `royalty_rate`, `img_plan`, `badge_text`, `is_featured`, `is_active`, `display_order`) VALUES
('single',  'Single',  'Distribua 1 single para mais de 150 lojas digitais',     'per_release',   2000.00,  11500.00, 10, NULL, 1, 1,    2,  90.00, 'assets/img/theme/plan_single.png',  NULL,           0, 1, 1),
('album',   'Álbum',   'Distribua 1 álbum completo para mais de 150 lojas',      'per_release',   5000.00,  40500.00, 10, NULL, 1, 1,    30, 90.00, 'assets/img/theme/plan_album.png',   NULL,           0, 1, 2),
('artist',  'Artista', 'Plano anual para artistas em crescimento',               'subscription', 11400.00,  NULL,     NULL, 365, 1, NULL, NULL,90.00, 'assets/img/theme/plan_artist.png',  'Mais Popular', 1, 1, 3),
('label',   'Label',   'Gestão completa de múltiplos artistas e gravadoras',     'subscription', 70000.00,  NULL,     NULL, 365, 10,NULL, NULL,90.00, 'assets/img/theme/plan_label.png',   'Profissional', 0, 1, 4);


-- Benefícios/recursos de cada plano (editável pelo CMS)
CREATE TABLE `_plan_features` (
  `id_feature`      INT(11)       NOT NULL AUTO_INCREMENT,
  `id_plan`         INT(11)       NOT NULL,
  `feature_text`    VARCHAR(255)  NOT NULL COMMENT 'Ex: Distribuição em 157 lojas',
  `is_included`     TINYINT(1)    NOT NULL DEFAULT 1 COMMENT '0 = não incluído (exibido como riscado)',
  `display_order`   INT(11)       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_feature`),
  KEY `id_plan` (`id_plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `_plan_features` (`id_plan`, `feature_text`, `is_included`, `display_order`) VALUES
-- Single
(1, 'Distribuição em 157+ lojas digitais', 1, 1),
(1, '90% dos royalties para você',         1, 2),
(1, 'ISRC automático',                     1, 3),
(1, 'UPC automático',                      1, 4),
(1, 'Smartlink do lançamento',             1, 5),
(1, 'Análises básicas',                    1, 6),
(1, 'Vários artistas',                     0, 7),
(1, 'Relatórios avançados',                0, 8),
-- Album
(2, 'Distribuição em 157+ lojas digitais', 1, 1),
(2, '90% dos royalties para você',         1, 2),
(2, 'ISRC automático por faixa',           1, 3),
(2, 'UPC automático',                      1, 4),
(2, 'Smartlink do lançamento',             1, 5),
(2, 'Análises básicas',                    1, 6),
(2, 'Até 30 faixas por álbum',             1, 7),
(2, 'Relatórios avançados',                0, 8),
-- Artist
(3, 'Distribuição em 157+ lojas digitais', 1, 1),
(3, '90% dos royalties para você',         1, 2),
(3, 'Singles e álbuns ilimitados',         1, 3),
(3, 'ISRC e UPC automáticos',              1, 4),
(3, 'Análises avançadas',                  1, 5),
(3, 'Relatórios mensais',                  1, 6),
(3, 'Smartlink personalizado',             1, 7),
(3, 'Vários artistas',                     0, 8),
-- Label
(4, 'Distribuição em 157+ lojas digitais', 1, 1),
(4, '90% dos royalties para você',         1, 2),
(4, 'Até 10 artistas/perfis',              1, 3),
(4, 'Lançamentos ilimitados',              1, 4),
(4, 'Análises avançadas completas',        1, 5),
(4, 'Relatórios mensais por artista',      1, 6),
(4, 'Smartlink personalizado',             1, 7),
(4, 'Suporte prioritário',                 1, 8);


-- Assinatura/plano ativo do utilizador
CREATE TABLE `_user_plan` (
  `id_user_plan`      INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`          INT(11)       NOT NULL,
  `id_plan`           INT(11)       NOT NULL,
  `id_payment`        INT(11)       DEFAULT NULL COMMENT 'FK para pagamento aprovado',
  `status_plan`       ENUM('active','expired','cancelled','pending_payment') NOT NULL DEFAULT 'pending_payment',
  `releases_used`     INT(11)       NOT NULL DEFAULT 0 COMMENT 'Para planos per_release com pacote',
  `releases_limit`    INT(11)       DEFAULT NULL COMMENT 'NULL = ilimitado',
  `started_at`        DATETIME      DEFAULT NULL COMMENT 'Data de ativação após pagamento',
  `expires_at`        DATETIME      DEFAULT NULL COMMENT 'NULL = sem expiração (per_release)',
  `auto_renew`        TINYINT(1)    NOT NULL DEFAULT 0,
  `creat_user_plan`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_user_plan`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_user_plan`),
  KEY `id_users` (`id_users`),
  KEY `id_plan` (`id_plan`),
  KEY `idx_status_plan` (`status_plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Pagamentos de planos (comprovante enviado pelo utilizador, aprovado pelo admin)
CREATE TABLE `_payment` (
  `id_payment`          INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       NOT NULL,
  `id_plan`             INT(11)       NOT NULL,
  `payment_ref`         VARCHAR(20)   NOT NULL UNIQUE COMMENT 'Código único de referência do pagamento',
  `amount`              DECIMAL(10,2) NOT NULL,
  `currency`            ENUM('AOA','USD','EUR') NOT NULL DEFAULT 'AOA',
  `payment_method`      ENUM('bank_transfer','multicaixa','paypal','card','other') NOT NULL DEFAULT 'bank_transfer',
  `comprovante`         VARCHAR(255)  DEFAULT NULL COMMENT 'Caminho do ficheiro de comprovante',
  `status_payment`      ENUM('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
  `rejection_reason`    TEXT          DEFAULT NULL,
  `reviewed_by`         INT(11)       DEFAULT NULL COMMENT 'id_employees que aprovou/rejeitou',
  `reviewed_at`         DATETIME      DEFAULT NULL,
  `is_renewal`          TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = renovação de plano existente',
  `notes`               TEXT          DEFAULT NULL COMMENT 'Observações internas do admin',
  `creat_payment`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_payment`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_payment`),
  KEY `id_users` (`id_users`),
  KEY `id_plan` (`id_plan`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_status_payment` (`status_payment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Faturas geradas automaticamente
CREATE TABLE `_invoice` (
  `id_invoice`          INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       NOT NULL,
  `id_payment`          INT(11)       DEFAULT NULL,
  `invoice_number`      VARCHAR(20)   NOT NULL UNIQUE COMMENT 'WU-2025-00001',
  `description`         VARCHAR(255)  NOT NULL,
  `amount`              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax`                 DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total`               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency`            ENUM('AOA','USD','EUR') NOT NULL DEFAULT 'AOA',
  `status_invoice`      ENUM('draft','issued','paid','cancelled') NOT NULL DEFAULT 'issued',
  `pdf_path`            VARCHAR(255)  DEFAULT NULL,
  `creat_invoice`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_invoice`),
  KEY `id_users` (`id_users`),
  KEY `id_payment` (`id_payment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [5] ARTISTAS & COLABORADORES
-- ============================================================

-- Perfis de artistas (um utilizador pode ter vários no plano Label)
CREATE TABLE `_artist` (
  `id_artist`           INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       NOT NULL COMMENT 'Dono/responsável',
  `stage_name`          VARCHAR(100)  NOT NULL COMMENT 'Nome artístico',
  `real_name`           VARCHAR(150)  DEFAULT NULL,
  `genre_main`          VARCHAR(50)   DEFAULT NULL,
  `genre_secondary`     VARCHAR(50)   DEFAULT NULL,
  `bio`                 TEXT          DEFAULT NULL,
  `country`             VARCHAR(60)   DEFAULT NULL,
  `city`                VARCHAR(60)   DEFAULT NULL,
  `photo_artist`        VARCHAR(255)  DEFAULT NULL,
  `cover_artist`        VARCHAR(255)  DEFAULT NULL,
  `facebook_url`        VARCHAR(255)  DEFAULT NULL,
  `instagram_url`       VARCHAR(255)  DEFAULT NULL,
  `youtube_url`         VARCHAR(255)  DEFAULT NULL,
  `spotify_url`         VARCHAR(255)  DEFAULT NULL,
  `tiktok_url`          VARCHAR(255)  DEFAULT NULL,
  `website_url`         VARCHAR(255)  DEFAULT NULL,
  `status_artist`       ENUM('active','inactive','blocked','processing') NOT NULL DEFAULT 'processing',
  `creat_artist`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_artist`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_artist`),
  KEY `id_users` (`id_users`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Colaboradores de um artista (feat., produtores, compositores com acesso)
CREATE TABLE `_artist_collaborator` (
  `id_collab`       INT(11)       NOT NULL AUTO_INCREMENT,
  `id_artist`       INT(11)       NOT NULL,
  `id_users`        INT(11)       DEFAULT NULL COMMENT 'Se o colaborador também for utilizador da plataforma',
  `name_collab`     VARCHAR(150)  NOT NULL,
  `role_collab`     ENUM('feat','producer','composer','lyricist','manager','label','other') NOT NULL DEFAULT 'feat',
  `email_collab`    VARCHAR(255)  DEFAULT NULL,
  `royalty_share`   DECIMAL(5,2)  DEFAULT NULL COMMENT '% de royalties destinados a este colaborador',
  `creat_collab`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_collab`),
  KEY `id_artist` (`id_artist`),
  KEY `id_users` (`id_users`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Integração com canal YouTube
CREATE TABLE `_youtube_channel` (
  `id_youtube`          INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       NOT NULL,
  `id_artist`           INT(11)       DEFAULT NULL,
  `channel_id`          VARCHAR(100)  NOT NULL UNIQUE COMMENT 'ID do canal no YouTube',
  `channel_name`        VARCHAR(255)  DEFAULT NULL,
  `channel_url`         VARCHAR(255)  DEFAULT NULL,
  `verified_code`       VARCHAR(30)   DEFAULT NULL COMMENT 'Código de verificação enviado',
  `status_youtube`      ENUM('pending','verified','rejected','removed') NOT NULL DEFAULT 'pending',
  `verified_at`         DATETIME      DEFAULT NULL,
  `creat_youtube`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_youtube`),
  KEY `id_users` (`id_users`),
  KEY `id_artist` (`id_artist`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Conta bancária / carteira de pagamento do utilizador
CREATE TABLE `_account` (
  `id_account`          INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       NOT NULL,
  `full_name_account`   VARCHAR(255)  NOT NULL,
  `tel_account`         VARCHAR(20)   DEFAULT NULL,
  `email_account`       VARCHAR(255)  DEFAULT NULL,
  `iban`                VARCHAR(34)   DEFAULT NULL,
  `type_account`        ENUM('PayPal','Express','IBAN','Multicaixa','TPA') NOT NULL DEFAULT 'IBAN',
  `is_default`          TINYINT(1)    NOT NULL DEFAULT 1 COMMENT '1 = conta principal',
  `creat_account`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_account`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_account`),
  KEY `id_users` (`id_users`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [6] LANÇAMENTOS (ÁLBUNS & FAIXAS)
-- ============================================================

-- Álbuns/EPs/Singles (unidade de distribuição)
CREATE TABLE `_album` (
  `id_album`            INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       NOT NULL,
  `id_artist`           INT(11)       DEFAULT NULL,
  `id_plan`             INT(11)       NOT NULL COMMENT 'Plano usado para este lançamento',
  `upc`                 VARCHAR(15)   DEFAULT NULL UNIQUE COMMENT 'Universal Product Code gerado',
  `name_album`          VARCHAR(150)  NOT NULL,
  `type_album`          ENUM('single','EP','album','mixtape') NOT NULL DEFAULT 'single',
  `name_author_band`    VARCHAR(150)  NOT NULL,
  `genre_main`          VARCHAR(50)   NOT NULL,
  `genre_secondary`     VARCHAR(50)   DEFAULT NULL,
  `language`            VARCHAR(50)   DEFAULT NULL,
  `label_name`          VARCHAR(100)  DEFAULT NULL COMMENT 'Nome da gravadora (se houver)',
  `smartlink`           VARCHAR(255)  DEFAULT NULL COMMENT 'Link agregador gerado após aprovação',
  `release_date`        DATE          DEFAULT NULL COMMENT 'Data pretendida de lançamento',
  `recording_date`      DATE          DEFAULT NULL,
  `territory`           VARCHAR(100)  NOT NULL DEFAULT 'Worldwide' COMMENT 'Regiões de distribuição',
  `copyright_c`         VARCHAR(255)  DEFAULT NULL COMMENT '© Copyright (gravação)',
  `copyright_p`         VARCHAR(255)  DEFAULT NULL COMMENT '℗ Copyright (fonograma)',
  `img_cover`           VARCHAR(255)  DEFAULT NULL,
  `status_album`        ENUM('processing','approved','active','inactive','blocked','takedown_requested','taken_down') NOT NULL DEFAULT 'processing',
  `rejection_reason`    TEXT          DEFAULT NULL,
  `approved_by`         INT(11)       DEFAULT NULL COMMENT 'id_employees que aprovou',
  `approved_at`         DATETIME      DEFAULT NULL,
  `creat_album`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_album`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_album`),
  KEY `id_users` (`id_users`),
  KEY `id_artist` (`id_artist`),
  KEY `id_plan` (`id_plan`),
  KEY `approved_by` (`approved_by`),
  KEY `idx_status_album` (`status_album`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Faixas (tracks) de cada álbum/single
CREATE TABLE `_track` (
  `id_track`            INT(11)       NOT NULL AUTO_INCREMENT,
  `id_album`            INT(11)       NOT NULL,
  `id_users`            INT(11)       NOT NULL,
  `isrc`                VARCHAR(15)   DEFAULT NULL UNIQUE COMMENT 'International Standard Recording Code',
  `title_track`         VARCHAR(150)  NOT NULL,
  `track_number`        INT(11)       NOT NULL DEFAULT 1,
  `name_author`         VARCHAR(150)  DEFAULT NULL COMMENT 'Intérprete principal',
  `name_author_feat`    TEXT          DEFAULT NULL COMMENT 'Featuring (múltiplos, separados por vírgula)',
  `name_composer`       VARCHAR(255)  DEFAULT NULL,
  `name_producer`       VARCHAR(255)  NOT NULL,
  `language`            VARCHAR(50)   DEFAULT NULL,
  `duration_seconds`    INT(11)       DEFAULT NULL COMMENT 'Duração em segundos',
  `explicit`            ENUM('NO','YES') NOT NULL DEFAULT 'NO',
  `audio_file`          VARCHAR(255)  DEFAULT NULL COMMENT 'Caminho do ficheiro de áudio (.wav/.flac)',
  `preview_start`       INT(11)       DEFAULT NULL COMMENT 'Início do preview em segundos',
  `status_track`        ENUM('processing','approved','active','inactive','blocked') NOT NULL DEFAULT 'processing',
  `creat_track`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_track`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_track`),
  KEY `id_album` (`id_album`),
  KEY `id_users` (`id_users`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Pedidos de takedown de lançamento
CREATE TABLE `_takedown_request` (
  `id_takedown`         INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       NOT NULL,
  `id_album`            INT(11)       NOT NULL,
  `reason`              TEXT          NOT NULL,
  `status_takedown`     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by`         INT(11)       DEFAULT NULL,
  `reviewed_at`         DATETIME      DEFAULT NULL,
  `creat_takedown`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_takedown`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_takedown`),
  KEY `id_users` (`id_users`),
  KEY `id_album` (`id_album`),
  KEY `reviewed_by` (`reviewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Lojas onde cada álbum foi distribuído
CREATE TABLE `_album_store` (
  `id_album_store`      INT(11)       NOT NULL AUTO_INCREMENT,
  `id_album`            INT(11)       NOT NULL,
  `id_store`            INT(11)       NOT NULL,
  `store_release_url`   VARCHAR(255)  DEFAULT NULL COMMENT 'URL da música na loja',
  `store_track_id`      VARCHAR(100)  DEFAULT NULL COMMENT 'ID interno da música na loja',
  `distributed_at`      DATETIME      DEFAULT NULL,
  `status`              ENUM('pending','live','removed') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id_album_store`),
  UNIQUE KEY `album_store` (`id_album`, `id_store`),
  KEY `id_store` (`id_store`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [8] ANÁLISES & STREAMS
-- ============================================================

-- Streams por faixa + loja + período
CREATE TABLE `_stream` (
  `id_stream`       INT(11)         NOT NULL AUTO_INCREMENT,
  `id_track`        INT(11)         NOT NULL,
  `id_store`        INT(11)         NOT NULL,
  `year_stream`     YEAR            NOT NULL,
  `month_stream`    TINYINT(2)      NOT NULL COMMENT '1-12',
  `streams`         BIGINT(20)      NOT NULL DEFAULT 0,
  `downloads`       BIGINT(20)      NOT NULL DEFAULT 0,
  `revenue`         DECIMAL(12,4)   NOT NULL DEFAULT 0.0000 COMMENT 'Receita bruta em USD',
  `creat_stream`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_stream`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_stream`),
  UNIQUE KEY `track_store_period` (`id_track`, `id_store`, `year_stream`, `month_stream`),
  KEY `id_store` (`id_store`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Streams por país (para análises de território)
CREATE TABLE `_stream_country` (
  `id_stream_country`   INT(11)       NOT NULL AUTO_INCREMENT,
  `id_track`            INT(11)       NOT NULL,
  `year_stream`         YEAR          NOT NULL,
  `month_stream`        TINYINT(2)    NOT NULL,
  `country_code`        CHAR(2)       NOT NULL COMMENT 'Código ISO 3166-1 alpha-2',
  `country_name`        VARCHAR(100)  DEFAULT NULL,
  `streams`             BIGINT(20)    NOT NULL DEFAULT 0,
  `revenue`             DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id_stream_country`),
  UNIQUE KEY `track_country_period` (`id_track`, `country_code`, `year_stream`, `month_stream`),
  KEY `id_track` (`id_track`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [9] FINANÇAS (ROYALTIES, SAQUES, TRANSAÇÕES)
-- ============================================================

-- Saldo e carteira do utilizador
CREATE TABLE `_wallet` (
  `id_wallet`       INT(11)         NOT NULL AUTO_INCREMENT,
  `id_users`        INT(11)         NOT NULL UNIQUE,
  `balance_aoa`     DECIMAL(14,2)   NOT NULL DEFAULT 0.00,
  `balance_usd`     DECIMAL(14,4)   NOT NULL DEFAULT 0.0000,
  `total_earned`    DECIMAL(14,4)   NOT NULL DEFAULT 0.0000 COMMENT 'Total histórico recebido',
  `total_withdrawn` DECIMAL(14,2)   NOT NULL DEFAULT 0.00  COMMENT 'Total histórico sacado',
  `modif_wallet`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_wallet`),
  UNIQUE KEY `id_users` (`id_users`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Transações financeiras (crédito, débito, saque, royalties)
CREATE TABLE `_transaction` (
  `id_transaction`      INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       DEFAULT NULL,
  `id_employees`        INT(11)       DEFAULT NULL COMMENT 'Admin que processou (se aplicável)',
  `type_transaction`    ENUM('royalty_credit','withdrawal','plan_payment','refund','adjustment','fee') NOT NULL,
  `amount`              DECIMAL(12,4) NOT NULL,
  `currency`            ENUM('AOA','USD','EUR') NOT NULL DEFAULT 'USD',
  `balance_before`      DECIMAL(12,4) DEFAULT NULL,
  `balance_after`       DECIMAL(12,4) DEFAULT NULL,
  `reference`           VARCHAR(100)  DEFAULT NULL COMMENT 'Ref. cruzada (id do saque, id royalty, etc.)',
  `description`         TEXT          DEFAULT NULL,
  `is_blocked`          TINYINT(1)    NOT NULL DEFAULT 0,
  `creat_transaction`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaction`),
  KEY `id_users` (`id_users`),
  KEY `id_employees` (`id_employees`),
  KEY `idx_type_transaction` (`type_transaction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Royalties calculados por período
CREATE TABLE `_royalty` (
  `id_royalty`          INT(11)         NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)         NOT NULL,
  `id_track`            INT(11)         NOT NULL,
  `year_royalty`        YEAR            NOT NULL,
  `month_royalty`       TINYINT(2)      NOT NULL,
  `gross_revenue`       DECIMAL(12,4)   NOT NULL DEFAULT 0.0000 COMMENT 'Receita bruta nas lojas',
  `platform_fee`        DECIMAL(12,4)   NOT NULL DEFAULT 0.0000 COMMENT '% da Wasom',
  `net_royalty`         DECIMAL(12,4)   NOT NULL DEFAULT 0.0000 COMMENT 'Valor líquido para o artista',
  `currency`            ENUM('USD','AOA') NOT NULL DEFAULT 'USD',
  `exchange_rate`       DECIMAL(10,4)   DEFAULT NULL COMMENT 'Taxa usada na conversão',
  `net_royalty_aoa`     DECIMAL(14,2)   DEFAULT NULL COMMENT 'Valor em AOA após conversão',
  `status_royalty`      ENUM('pending','processing','paid','cancelled') NOT NULL DEFAULT 'pending',
  `report_file`         VARCHAR(255)    DEFAULT NULL COMMENT 'Ficheiro de relatório PDF',
  `id_transaction`      INT(11)         DEFAULT NULL COMMENT 'Transação gerada ao creditar',
  `paid_by`             INT(11)         DEFAULT NULL COMMENT 'id_employees que processou o pagamento',
  `paid_at`             DATETIME        DEFAULT NULL,
  `creat_royalty`       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_royalty`),
  UNIQUE KEY `user_track_period` (`id_users`, `id_track`, `year_royalty`, `month_royalty`),
  KEY `id_track` (`id_track`),
  KEY `id_transaction` (`id_transaction`),
  KEY `paid_by` (`paid_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Pedidos de saque/levantamento
CREATE TABLE `_withdrawal` (
  `id_withdrawal`       INT(11)         NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)         NOT NULL,
  `id_account`          INT(11)         NOT NULL COMMENT 'Conta bancária de destino',
  `id_transaction`      INT(11)         DEFAULT NULL,
  `amount_requested`    DECIMAL(12,2)   NOT NULL,
  `amount_fee`          DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT 'Taxa de levantamento',
  `amount_net`          DECIMAL(12,2)   NOT NULL COMMENT 'Valor efetivamente pago',
  `currency`            ENUM('AOA','USD') NOT NULL DEFAULT 'AOA',
  `comprovante`         VARCHAR(255)    DEFAULT NULL COMMENT 'Comprovante de pagamento (enviado pelo admin)',
  `status_withdrawal`   ENUM('pending','processing','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `rejection_reason`    TEXT            DEFAULT NULL,
  `reviewed_by`         INT(11)         DEFAULT NULL,
  `reviewed_at`         DATETIME        DEFAULT NULL,
  `paid_at`             DATETIME        DEFAULT NULL,
  `notes`               TEXT            DEFAULT NULL,
  `creat_withdrawal`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_withdrawal`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_withdrawal`),
  KEY `id_users` (`id_users`),
  KEY `id_account` (`id_account`),
  KEY `id_transaction` (`id_transaction`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_status_withdrawal` (`status_withdrawal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Relatório financeiro mensal (resumo por utilizador)
CREATE TABLE `_financial_report` (
  `id_report`           INT(11)         NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)         NOT NULL,
  `year_report`         YEAR            NOT NULL,
  `month_report`        TINYINT(2)      NOT NULL,
  `total_streams`       BIGINT(20)      NOT NULL DEFAULT 0,
  `gross_revenue`       DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
  `net_royalty`         DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
  `net_royalty_aoa`     DECIMAL(14,2)   DEFAULT NULL,
  `status_report`       ENUM('draft','published','sent') NOT NULL DEFAULT 'draft',
  `report_file`         VARCHAR(255)    DEFAULT NULL,
  `generated_by`        INT(11)         DEFAULT NULL,
  `sent_at`             DATETIME        DEFAULT NULL,
  `creat_report`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_report`),
  UNIQUE KEY `user_period` (`id_users`, `year_report`, `month_report`),
  KEY `generated_by` (`generated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Testemunhos de utilizadores (para exibir no site)
CREATE TABLE `_review` (
  `id_review`       INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`        INT(11)       DEFAULT NULL,
  `author_name`     VARCHAR(150)  DEFAULT NULL COMMENT 'Se não for utilizador registado',
  `comment_review`  TEXT          NOT NULL,
  `rating`          TINYINT(1)    DEFAULT NULL COMMENT '1-5 estrelas',
  `status_review`   ENUM('active','inactive','pending') NOT NULL DEFAULT 'pending',
  `creat_review`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_review`),
  KEY `id_users` (`id_users`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [10] NOTIFICAÇÕES & MENSAGENS
-- ============================================================

CREATE TABLE `_notification` (
  `id_notification`     INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       DEFAULT NULL COMMENT 'NULL = broadcast para todos',
  `id_employees`        INT(11)       DEFAULT NULL COMMENT 'Se enviada por um admin',
  `type`                ENUM('info','success','warning','error','payment','music','system') NOT NULL DEFAULT 'info',
  `title`               VARCHAR(255)  NOT NULL,
  `body`                TEXT          NOT NULL,
  `action_url`          VARCHAR(255)  DEFAULT NULL COMMENT 'Link de ação ao clicar',
  `is_read`             TINYINT(1)    NOT NULL DEFAULT 0,
  `read_at`             DATETIME      DEFAULT NULL,
  `is_broadcast`        TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = enviada para todos',
  `creat_notification`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notification`),
  KEY `id_users` (`id_users`),
  KEY `id_employees` (`id_employees`),
  KEY `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Mensagens internas (inbox)
CREATE TABLE `_message` (
  `id_message`      INT(11)       NOT NULL AUTO_INCREMENT,
  `from_user`       INT(11)       DEFAULT NULL COMMENT 'id_users (se for utilizador)',
  `from_employee`   INT(11)       DEFAULT NULL COMMENT 'id_employees (se for admin)',
  `to_user`         INT(11)       DEFAULT NULL COMMENT 'Destinatário utilizador',
  `to_employee`     INT(11)       DEFAULT NULL COMMENT 'Destinatário admin',
  `subject`         VARCHAR(255)  NOT NULL,
  `body`            TEXT          NOT NULL,
  `is_read`         TINYINT(1)    NOT NULL DEFAULT 0,
  `read_at`         DATETIME      DEFAULT NULL,
  `parent_message`  INT(11)       DEFAULT NULL COMMENT 'FK para resposta em thread',
  `status_message`  ENUM('sent','archived','deleted') NOT NULL DEFAULT 'sent',
  `creat_message`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_message`),
  KEY `from_user` (`from_user`),
  KEY `from_employee` (`from_employee`),
  KEY `to_user` (`to_user`),
  KEY `to_employee` (`to_employee`),
  KEY `parent_message` (`parent_message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [11] BLOG & CONTEÚDO
-- ============================================================

CREATE TABLE `_blog_category` (
  `id_category`     INT(11)       NOT NULL AUTO_INCREMENT,
  `name_category`   VARCHAR(100)  NOT NULL,
  `slug_category`   VARCHAR(100)  NOT NULL UNIQUE,
  `description`     TEXT          DEFAULT NULL,
  `is_active`       TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `_blog_category` (`name_category`, `slug_category`) VALUES
('Notícias',     'noticias'),
('Tutoriais',    'tutoriais'),
('Marketing',    'marketing'),
('Royalties',    'royalties'),
('Distribuição', 'distribuicao');


CREATE TABLE `_blog_post` (
  `id_post`         INT(11)       NOT NULL AUTO_INCREMENT,
  `id_category`     INT(11)       DEFAULT NULL,
  `id_employees`    INT(11)       DEFAULT NULL COMMENT 'Autor (admin)',
  `title_post`      VARCHAR(255)  NOT NULL,
  `slug_post`       VARCHAR(255)  NOT NULL UNIQUE,
  `excerpt`         TEXT          DEFAULT NULL,
  `body_post`       LONGTEXT      NOT NULL,
  `img_post`        VARCHAR(255)  DEFAULT NULL,
  `meta_title`      VARCHAR(255)  DEFAULT NULL,
  `meta_desc`       TEXT          DEFAULT NULL,
  `status_post`     ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  `views_post`      INT(11)       NOT NULL DEFAULT 0,
  `published_at`    DATETIME      DEFAULT NULL,
  `creat_post`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_post`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_post`),
  KEY `id_category` (`id_category`),
  KEY `id_employees` (`id_employees`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [13] SUPORTE & FAQ
-- ============================================================

-- FAQ (editável pelo CMS)
CREATE TABLE `_faq` (
  `id_faq`        INT(11)       NOT NULL AUTO_INCREMENT,
  `category_faq`  VARCHAR(100)  DEFAULT 'Geral',
  `question`      VARCHAR(500)  NOT NULL,
  `answer`        LONGTEXT      NOT NULL,
  `status_faq`    ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  `display_order` INT(11)       NOT NULL DEFAULT 0,
  `creat_faq`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_faq`     TIMESTAMP     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_faq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrar FAQs antigas
INSERT INTO `_faq` (`category_faq`, `question`, `answer`, `status_faq`, `display_order`) VALUES
('Conta',        'O que faço para recuperar a minha conta caso esqueça da chave de recuperação?', 'Para recuperar a chave ou solicitar uma nova chave, basta entrar em contacto connosco explicando o que aconteceu e fornecendo informações sobre você, como nome completo, e-mail, ou documento de identidade.', 'visible', 1),
('Financeiro',   'Quanto tempo dura a solicitação de pedido de transferência?', 'Quando é feito o pedido de solicitação de transferência, normalmente levamos 30min ou menos, porque é feita uma análise profunda para aprovar o seu pedido.', 'visible', 2),
('Geral',        'O que é o Wasom Upfy?', 'O Wasom Upfy é uma plataforma de distribuição digital e gestão de direitos musicais, focada em ajudar artistas independentes a promover e distribuir suas músicas em mais de 157 plataformas de música.', 'visible', 3),
('Geral',        'Quem pode usar o Wasom Upfy?', 'A plataforma é destinada a todos os artistas que desejam distribuir suas músicas globalmente, sejam novos no mercado ou já estabelecidos.', 'visible', 4),
('Financeiro',   'Como funcionam os pagamentos de royalties?', 'Os royalties são gerenciados pela equipe administrativa. Quando um pagamento é processado, o usuário recebe uma notificação na plataforma e por e-mail com o comprovante.', 'visible', 5),
('Distribuição', 'Como posso ver o desempenho das minhas músicas?', 'Você pode solicitar relatórios detalhados diretamente na plataforma, que mostrarão o desempenho das suas músicas, incluindo estatísticas de streams e finanças.', 'visible', 6),
('Conta',        'O que acontece se minha conta for suspensa?', 'As contas podem ser suspensas se o usuário não fornecer informações corretas ou se forem detectadas atividades suspeitas.', 'visible', 7),
('Conta',        'Como funciona a exclusão de contas?', 'As contas podem ser permanentemente excluídas se forem identificadas como duplicadas, clonadas, ou se houver fraude ou outras violações graves das políticas da plataforma.', 'visible', 8),
('Conta',        'Como faço para recuperar minha conta após a desativação?', 'Se sua conta for desativada, você pode solicitar a reativação dentro de 29 dias úteis. Após esse período, a conta será excluída permanentemente.', 'visible', 9),
('Geral',        'O Wasom Upfy utiliza cookies?', 'Sim, utilizamos cookies para melhorar a experiência do usuário e coletar dados sobre o uso da plataforma.', 'visible', 10);


-- Tickets de suporte
CREATE TABLE `_support_ticket` (
  `id_ticket`       INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`        INT(11)       DEFAULT NULL,
  `name_contact`    VARCHAR(150)  DEFAULT NULL COMMENT 'Se não for utilizador registado',
  `email_contact`   VARCHAR(255)  DEFAULT NULL,
  `subject`         VARCHAR(255)  NOT NULL,
  `body`            TEXT          NOT NULL,
  `priority`        ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status_ticket`   ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `assigned_to`     INT(11)       DEFAULT NULL COMMENT 'id_employees',
  `resolved_at`     DATETIME      DEFAULT NULL,
  `creat_ticket`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_ticket`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_ticket`),
  KEY `id_users` (`id_users`),
  KEY `assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Respostas ao ticket de suporte
CREATE TABLE `_support_reply` (
  `id_reply`        INT(11)       NOT NULL AUTO_INCREMENT,
  `id_ticket`       INT(11)       NOT NULL,
  `from_user`       INT(11)       DEFAULT NULL,
  `from_employee`   INT(11)       DEFAULT NULL,
  `body`            TEXT          NOT NULL,
  `creat_reply`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_reply`),
  KEY `id_ticket` (`id_ticket`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [14] AUDITORIA, LOGS & VISITAS
-- ============================================================

-- Log de auditoria de ações do admin
CREATE TABLE `_audit_log` (
  `id_log`          BIGINT(20)    NOT NULL AUTO_INCREMENT,
  `id_employees`    INT(11)       DEFAULT NULL,
  `id_users`        INT(11)       DEFAULT NULL COMMENT 'Utilizador afetado (se houver)',
  `action`          VARCHAR(100)  NOT NULL COMMENT 'Ex: user.block, music.approve, plan.price_update',
  `entity`          VARCHAR(50)   DEFAULT NULL COMMENT 'Tabela/entidade afetada',
  `entity_id`       INT(11)       DEFAULT NULL,
  `old_value`       JSON          DEFAULT NULL COMMENT 'Valor anterior (para diff)',
  `new_value`       JSON          DEFAULT NULL COMMENT 'Novo valor',
  `ip_address`      VARCHAR(45)   DEFAULT NULL,
  `user_agent`      TEXT          DEFAULT NULL,
  `creat_log`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `id_employees` (`id_employees`),
  KEY `id_users` (`id_users`),
  KEY `idx_action` (`action`),
  KEY `idx_creat_log` (`creat_log`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Visitas ao site
CREATE TABLE `_visit` (
  `id_visit`            INT(11)       NOT NULL AUTO_INCREMENT,
  `ip_visit`            VARCHAR(45)   DEFAULT NULL,
  `browser_visit`       TEXT          DEFAULT NULL,
  `country_visit`       VARCHAR(60)   DEFAULT NULL,
  `city_visit`          VARCHAR(60)   DEFAULT NULL,
  `page_visit`          VARCHAR(255)  DEFAULT NULL COMMENT 'URL da página visitada',
  `views_visit`         INT(11)       NOT NULL DEFAULT 0,
  `session_visit`       VARCHAR(100)  DEFAULT NULL,
  `is_blocked`          TINYINT(1)    NOT NULL DEFAULT 0,
  `creat_visit`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_visit`),
  KEY `idx_ip_visit` (`ip_visit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- FOREIGN KEYS
-- ============================================================

-- _platform
ALTER TABLE `_platform`
  ADD CONSTRAINT `fk_platform_emp` FOREIGN KEY (`id_employees`) REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _users
ALTER TABLE `_users`
  ADD CONSTRAINT `fk_users_plan` FOREIGN KEY (`plan_selected`) REFERENCES `_plans` (`id_plan`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _users_security
ALTER TABLE `_users_security`
  ADD CONSTRAINT `fk_usec_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _users_sessions
ALTER TABLE `_users_sessions`
  ADD CONSTRAINT `fk_usess_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _users_tokens
ALTER TABLE `_users_tokens`
  ADD CONSTRAINT `fk_utok_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _employees_security
ALTER TABLE `_employees_security`
  ADD CONSTRAINT `fk_esec_emp` FOREIGN KEY (`id_employees`) REFERENCES `_employees` (`id_employees`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _employees_permissions
ALTER TABLE `_employees_permissions`
  ADD CONSTRAINT `fk_eperm_emp` FOREIGN KEY (`id_employees`) REFERENCES `_employees` (`id_employees`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _plan_features
ALTER TABLE `_plan_features`
  ADD CONSTRAINT `fk_pfeat_plan` FOREIGN KEY (`id_plan`) REFERENCES `_plans` (`id_plan`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _user_plan
ALTER TABLE `_user_plan`
  ADD CONSTRAINT `fk_uplan_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uplan_plan`  FOREIGN KEY (`id_plan`)  REFERENCES `_plans` (`id_plan`)  ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_uplan_pay`   FOREIGN KEY (`id_payment`) REFERENCES `_payment` (`id_payment`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _payment
ALTER TABLE `_payment`
  ADD CONSTRAINT `fk_pay_users`   FOREIGN KEY (`id_users`)      REFERENCES `_users` (`id_users`)         ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pay_plan`    FOREIGN KEY (`id_plan`)       REFERENCES `_plans` (`id_plan`)          ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pay_emp`     FOREIGN KEY (`reviewed_by`)   REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _invoice
ALTER TABLE `_invoice`
  ADD CONSTRAINT `fk_inv_users`   FOREIGN KEY (`id_users`)   REFERENCES `_users` (`id_users`)   ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inv_pay`     FOREIGN KEY (`id_payment`) REFERENCES `_payment` (`id_payment`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _artist
ALTER TABLE `_artist`
  ADD CONSTRAINT `fk_art_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _artist_collaborator
ALTER TABLE `_artist_collaborator`
  ADD CONSTRAINT `fk_col_artist` FOREIGN KEY (`id_artist`) REFERENCES `_artist`  (`id_artist`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_col_users`  FOREIGN KEY (`id_users`)  REFERENCES `_users`   (`id_users`)  ON DELETE SET NULL ON UPDATE CASCADE;

-- _youtube_channel
ALTER TABLE `_youtube_channel`
  ADD CONSTRAINT `fk_yt_users`  FOREIGN KEY (`id_users`)  REFERENCES `_users`  (`id_users`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_yt_artist` FOREIGN KEY (`id_artist`) REFERENCES `_artist` (`id_artist`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _account
ALTER TABLE `_account`
  ADD CONSTRAINT `fk_acc_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _album
ALTER TABLE `_album`
  ADD CONSTRAINT `fk_alb_users`   FOREIGN KEY (`id_users`)    REFERENCES `_users`       (`id_users`)    ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alb_artist`  FOREIGN KEY (`id_artist`)   REFERENCES `_artist`      (`id_artist`)   ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alb_plan`    FOREIGN KEY (`id_plan`)     REFERENCES `_plans`       (`id_plan`)     ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_alb_emp`     FOREIGN KEY (`approved_by`) REFERENCES `_employees`   (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _track
ALTER TABLE `_track`
  ADD CONSTRAINT `fk_trk_album` FOREIGN KEY (`id_album`) REFERENCES `_album` (`id_album`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_trk_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _takedown_request
ALTER TABLE `_takedown_request`
  ADD CONSTRAINT `fk_td_users`   FOREIGN KEY (`id_users`)     REFERENCES `_users`       (`id_users`)     ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_td_album`   FOREIGN KEY (`id_album`)     REFERENCES `_album`       (`id_album`)     ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_td_emp`     FOREIGN KEY (`reviewed_by`)  REFERENCES `_employees`   (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _album_store
ALTER TABLE `_album_store`
  ADD CONSTRAINT `fk_as_album` FOREIGN KEY (`id_album`) REFERENCES `_album` (`id_album`) ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_as_store` FOREIGN KEY (`id_store`) REFERENCES `_store` (`id_store`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- _stream
ALTER TABLE `_stream`
  ADD CONSTRAINT `fk_str_track` FOREIGN KEY (`id_track`) REFERENCES `_track` (`id_track`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_str_store` FOREIGN KEY (`id_store`) REFERENCES `_store` (`id_store`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- _stream_country
ALTER TABLE `_stream_country`
  ADD CONSTRAINT `fk_strc_track` FOREIGN KEY (`id_track`) REFERENCES `_track` (`id_track`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _wallet
ALTER TABLE `_wallet`
  ADD CONSTRAINT `fk_wal_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE;

-- _transaction
ALTER TABLE `_transaction`
  ADD CONSTRAINT `fk_tx_users` FOREIGN KEY (`id_users`)       REFERENCES `_users`     (`id_users`)     ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tx_emp`   FOREIGN KEY (`id_employees`)   REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _royalty
ALTER TABLE `_royalty`
  ADD CONSTRAINT `fk_roy_users` FOREIGN KEY (`id_users`)    REFERENCES `_users`     (`id_users`)     ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_roy_track` FOREIGN KEY (`id_track`)    REFERENCES `_track`     (`id_track`)     ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_roy_tx`    FOREIGN KEY (`id_transaction`) REFERENCES `_transaction` (`id_transaction`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_roy_emp`   FOREIGN KEY (`paid_by`)     REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _withdrawal
ALTER TABLE `_withdrawal`
  ADD CONSTRAINT `fk_wd_users` FOREIGN KEY (`id_users`)    REFERENCES `_users`     (`id_users`)     ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wd_acc`   FOREIGN KEY (`id_account`)  REFERENCES `_account`   (`id_account`)   ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wd_tx`    FOREIGN KEY (`id_transaction`) REFERENCES `_transaction` (`id_transaction`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_wd_emp`   FOREIGN KEY (`reviewed_by`) REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _financial_report
ALTER TABLE `_financial_report`
  ADD CONSTRAINT `fk_frep_users` FOREIGN KEY (`id_users`)      REFERENCES `_users`     (`id_users`)     ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_frep_emp`   FOREIGN KEY (`generated_by`)  REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _review
ALTER TABLE `_review`
  ADD CONSTRAINT `fk_rev_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _notification
ALTER TABLE `_notification`
  ADD CONSTRAINT `fk_notif_users` FOREIGN KEY (`id_users`)     REFERENCES `_users`     (`id_users`)     ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_emp`   FOREIGN KEY (`id_employees`) REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _message
ALTER TABLE `_message`
  ADD CONSTRAINT `fk_msg_fu`  FOREIGN KEY (`from_user`)     REFERENCES `_users`     (`id_users`)     ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_fe`  FOREIGN KEY (`from_employee`) REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_tu`  FOREIGN KEY (`to_user`)       REFERENCES `_users`     (`id_users`)     ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_te`  FOREIGN KEY (`to_employee`)   REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_msg_par` FOREIGN KEY (`parent_message`) REFERENCES `_message`  (`id_message`)   ON DELETE SET NULL ON UPDATE CASCADE;

-- _blog_post
ALTER TABLE `_blog_post`
  ADD CONSTRAINT `fk_bp_cat` FOREIGN KEY (`id_category`) REFERENCES `_blog_category` (`id_category`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bp_emp` FOREIGN KEY (`id_employees`) REFERENCES `_employees`   (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _support_ticket
ALTER TABLE `_support_ticket`
  ADD CONSTRAINT `fk_st_users`  FOREIGN KEY (`id_users`)     REFERENCES `_users`     (`id_users`)     ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_st_emp`    FOREIGN KEY (`assigned_to`)  REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

-- _support_reply
ALTER TABLE `_support_reply`
  ADD CONSTRAINT `fk_sr_ticket`  FOREIGN KEY (`id_ticket`)      REFERENCES `_support_ticket` (`id_ticket`)      ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sr_user`    FOREIGN KEY (`from_user`)      REFERENCES `_users`          (`id_users`)       ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sr_emp`     FOREIGN KEY (`from_employee`)  REFERENCES `_employees`      (`id_employees`)   ON DELETE SET NULL ON UPDATE CASCADE;

-- _audit_log
ALTER TABLE `_audit_log`
  ADD CONSTRAINT `fk_al_emp`   FOREIGN KEY (`id_employees`) REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_al_users` FOREIGN KEY (`id_users`)     REFERENCES `_users`     (`id_users`)     ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;

-- ============================================================
-- FIM DO BANCO DE DADOS WASOM UPFY v2.0
-- Tabelas: 38 | Relacionamentos: 45+ | Índices: 60+
-- ============================================================
