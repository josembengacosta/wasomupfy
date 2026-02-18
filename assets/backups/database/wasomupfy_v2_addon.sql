-- ============================================================
-- WASOM UPFY v2.0 - TABELAS COMPLEMENTARES
-- Arquivo: wasomupfy_v2_addon.sql
-- Executar DEPOIS de: wasomupfy_v2.sql
-- ============================================================
-- MÓDULOS COBERTOS:
--   [A] Visitantes Avançado (substitui _visit básico)
--   [B] Utilizadores Online
--   [C] Relatórios Personalizados & Agendados
-- ============================================================

USE `wasomupfy`;

-- ============================================================
-- [A] VISITANTES AVANÇADO
-- (A tabela _visit básica continua existindo para compatibilidade
--  Esta expande com todos os campos da página de gestão)
-- ============================================================

-- Substituição/expansão da _visit com todos os dados da página
-- ATENÇÃO: se já importou wasomupfy_v2.sql, a _visit já existe.
-- Esta tabela é a versão completa. Pode renomear _visit para
-- _visit_legacy e usar esta como _visit, ou usar como _visit_detail.

CREATE TABLE IF NOT EXISTS `_visitor` (
  `id_visitor`          BIGINT(20)    NOT NULL AUTO_INCREMENT,
  `ip_address`          VARCHAR(45)   NOT NULL COMMENT 'IPv4 ou IPv6',
  `ip_version`          ENUM('v4','v6') NOT NULL DEFAULT 'v4',

  -- Localização
  `country_code`        CHAR(2)       DEFAULT NULL COMMENT 'ISO 3166-1 alpha-2',
  `country_name`        VARCHAR(100)  DEFAULT NULL,
  `city`                VARCHAR(100)  DEFAULT NULL,
  `region`              VARCHAR(100)  DEFAULT NULL,
  `latitude`            DECIMAL(10,7) DEFAULT NULL,
  `longitude`           DECIMAL(10,7) DEFAULT NULL,
  `timezone`            VARCHAR(50)   DEFAULT NULL,
  `isp`                 VARCHAR(255)  DEFAULT NULL COMMENT 'Internet Service Provider',

  -- Dispositivo & Navegador
  `user_agent`          TEXT          DEFAULT NULL,
  `browser`             VARCHAR(50)   DEFAULT NULL COMMENT 'chrome, firefox, safari, edge, opera',
  `browser_version`     VARCHAR(20)   DEFAULT NULL,
  `os`                  VARCHAR(50)   DEFAULT NULL COMMENT 'Windows, macOS, Android, iOS',
  `os_version`          VARCHAR(20)   DEFAULT NULL,
  `device_type`         ENUM('desktop','mobile','tablet','bot','unknown') NOT NULL DEFAULT 'unknown',
  `device_brand`        VARCHAR(50)   DEFAULT NULL COMMENT 'Apple, Samsung, etc.',
  `screen_resolution`   VARCHAR(20)   DEFAULT NULL COMMENT '1920x1080',
  `is_bot`              TINYINT(1)    NOT NULL DEFAULT 0,
  `bot_name`            VARCHAR(100)  DEFAULT NULL COMMENT 'Googlebot, etc.',

  -- Atividade
  `page_entry`          VARCHAR(500)  DEFAULT NULL COMMENT 'Primeira página visitada',
  `page_exit`           VARCHAR(500)  DEFAULT NULL COMMENT 'Última página antes de sair',
  `pages_viewed`        INT(11)       NOT NULL DEFAULT 1,
  `session_duration`    INT(11)       DEFAULT NULL COMMENT 'Duração da sessão em segundos',
  `referrer`            VARCHAR(500)  DEFAULT NULL COMMENT 'URL de origem',
  `utm_source`          VARCHAR(100)  DEFAULT NULL,
  `utm_medium`          VARCHAR(100)  DEFAULT NULL,
  `utm_campaign`        VARCHAR(100)  DEFAULT NULL,

  -- Sessão
  `session_id`          VARCHAR(128)  DEFAULT NULL UNIQUE,
  `is_online`           TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = sessão ativa agora',
  `last_seen`           DATETIME      DEFAULT CURRENT_TIMESTAMP,
  `visit_count`         INT(11)       NOT NULL DEFAULT 1 COMMENT 'Nº total de visitas deste IP',

  -- Status & Bloqueio
  `status_visitor`      ENUM('active','blocked','suspicious') NOT NULL DEFAULT 'active',
  `block_type`          ENUM('temporary','permanent','custom') DEFAULT NULL,
  `block_reason`        ENUM('spam','security','bot','multiple_failures','suspicious','other') DEFAULT NULL,
  `block_notes`         TEXT          DEFAULT NULL,
  `block_until`         DATETIME      DEFAULT NULL COMMENT 'NULL = permanente',
  `blocked_by`          INT(11)       DEFAULT NULL COMMENT 'id_employees',
  `blocked_at`          DATETIME      DEFAULT NULL,

  `creat_visitor`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_visitor`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_visitor`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_status_visitor` (`status_visitor`),
  KEY `idx_country_code` (`country_code`),
  KEY `idx_device_type` (`device_type`),
  KEY `idx_is_online` (`is_online`),
  KEY `idx_last_seen` (`last_seen`),
  KEY `blocked_by` (`blocked_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Log de páginas visitadas por sessão (para activity timeline)
CREATE TABLE IF NOT EXISTS `_visitor_pageview` (
  `id_pageview`     BIGINT(20)    NOT NULL AUTO_INCREMENT,
  `id_visitor`      BIGINT(20)    NOT NULL,
  `page_url`        VARCHAR(500)  NOT NULL,
  `page_title`      VARCHAR(255)  DEFAULT NULL,
  `time_on_page`    INT(11)       DEFAULT NULL COMMENT 'Segundos na página',
  `creat_pageview`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pageview`),
  KEY `id_visitor` (`id_visitor`),
  KEY `idx_creat_pageview` (`creat_pageview`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [B] UTILIZADORES ONLINE
-- ============================================================

-- Presença online dos utilizadores (atualizado em tempo real / polling)
CREATE TABLE IF NOT EXISTS `_user_presence` (
  `id_presence`         INT(11)       NOT NULL AUTO_INCREMENT,
  `id_users`            INT(11)       NOT NULL,

  -- Status de presença
  `online_status`       ENUM('online','away','busy','invisible','offline') NOT NULL DEFAULT 'offline',
  `last_activity`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity_type`  VARCHAR(100)  DEFAULT NULL
                        COMMENT 'Ex: listening, uploading, dashboard, releases, finances',
  `last_page`           VARCHAR(255)  DEFAULT NULL,

  -- Sessão atual
  `session_token`       VARCHAR(255)  DEFAULT NULL,
  `ip_address`          VARCHAR(45)   DEFAULT NULL,
  `user_agent`          TEXT          DEFAULT NULL,
  `device_type`         ENUM('desktop','mobile','tablet','unknown') NOT NULL DEFAULT 'unknown',
  `browser`             VARCHAR(50)   DEFAULT NULL,
  `country_code`        CHAR(2)       DEFAULT NULL,
  `country_name`        VARCHAR(100)  DEFAULT NULL,
  `city`                VARCHAR(100)  DEFAULT NULL,

  -- Estatísticas da sessão atual
  `session_start`       DATETIME      DEFAULT NULL,
  `session_duration`    INT(11)       NOT NULL DEFAULT 0 COMMENT 'Segundos online nesta sessão',

  `modif_presence`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id_presence`),
  UNIQUE KEY `id_users` (`id_users`) COMMENT 'Um registo por utilizador',
  KEY `idx_online_status` (`online_status`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Log de atividades do utilizador no painel (para activity stream)
CREATE TABLE IF NOT EXISTS `_user_activity_log` (
  `id_activity`     BIGINT(20)    NOT NULL AUTO_INCREMENT,
  `id_users`        INT(11)       NOT NULL,
  `activity_type`   VARCHAR(50)   NOT NULL
                    COMMENT 'login, logout, upload, release_create, withdrawal_request, profile_update, etc.',
  `description`     VARCHAR(255)  DEFAULT NULL COMMENT 'Texto legível para exibir no feed',
  `entity`          VARCHAR(50)   DEFAULT NULL COMMENT 'album, track, withdrawal, profile...',
  `entity_id`       INT(11)       DEFAULT NULL,
  `ip_address`      VARCHAR(45)   DEFAULT NULL,
  `device_type`     ENUM('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
  `creat_activity`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_activity`),
  KEY `id_users` (`id_users`),
  KEY `idx_activity_type` (`activity_type`),
  KEY `idx_creat_activity` (`creat_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Broadcasts/Anúncios enviados pelo admin para utilizadores online
CREATE TABLE IF NOT EXISTS `_broadcast` (
  `id_broadcast`        INT(11)       NOT NULL AUTO_INCREMENT,
  `id_employees`        INT(11)       NOT NULL COMMENT 'Admin que enviou',
  `type`                ENUM('info','warning','success','event') NOT NULL DEFAULT 'info',
  `audience`            ENUM('all','selected','role','country') NOT NULL DEFAULT 'all',
  `audience_value`      VARCHAR(100)  DEFAULT NULL
                        COMMENT 'Valor do filtro: nome da role ou código do país',
  `message`             TEXT          NOT NULL,
  `recipients_count`    INT(11)       NOT NULL DEFAULT 0,
  `creat_broadcast`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_broadcast`),
  KEY `id_employees` (`id_employees`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Registro de quem recebeu/leu cada broadcast
CREATE TABLE IF NOT EXISTS `_broadcast_receipt` (
  `id_receipt`          INT(11)       NOT NULL AUTO_INCREMENT,
  `id_broadcast`        INT(11)       NOT NULL,
  `id_users`            INT(11)       NOT NULL,
  `is_read`             TINYINT(1)    NOT NULL DEFAULT 0,
  `read_at`             DATETIME      DEFAULT NULL,
  PRIMARY KEY (`id_receipt`),
  UNIQUE KEY `broadcast_user` (`id_broadcast`, `id_users`),
  KEY `id_users` (`id_users`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- [C] RELATÓRIOS PERSONALIZADOS & AGENDADOS
-- ============================================================

-- Modelos/templates de relatórios criados pelos admins
CREATE TABLE IF NOT EXISTS `_report_template` (
  `id_template`         INT(11)       NOT NULL AUTO_INCREMENT,
  `id_employees`        INT(11)       DEFAULT NULL COMMENT 'Criado por',
  `name_template`       VARCHAR(150)  NOT NULL,
  `description`         TEXT          DEFAULT NULL,
  `report_type`         ENUM('financial','performance','analytics','users','music','royalties','custom') NOT NULL,
  `parameters`          JSON          NOT NULL COMMENT 'Configuração completa dos parâmetros',
  `format`              ENUM('pdf','excel','csv','json') NOT NULL DEFAULT 'excel',
  `visualization`       ENUM('table','chart','mixed') NOT NULL DEFAULT 'table',
  `is_public`           TINYINT(1)    NOT NULL DEFAULT 0
                        COMMENT '1 = disponível para todos os admins, 0 = apenas o criador',
  `use_count`           INT(11)       NOT NULL DEFAULT 0 COMMENT 'Qtd de vezes usado',
  `creat_template`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_template`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_template`),
  KEY `id_employees` (`id_employees`),
  KEY `idx_report_type` (`report_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Templates padrão do sistema
INSERT INTO `_report_template` (`id_employees`, `name_template`, `description`, `report_type`, `parameters`, `format`, `visualization`, `is_public`) VALUES
(1, 'Receita por Artista',      'Total de royalties por artista no período',          'financial',    '{"period":"month","group_by":"artist","metrics":["streams","revenue","royalty"]}',         'excel', 'mixed',  1),
(1, 'Desempenho por Loja',      'Streams e receita agrupados por plataforma digital', 'performance',  '{"period":"month","group_by":"store","metrics":["streams","downloads","revenue"]}',         'excel', 'chart',  1),
(1, 'Crescimento Mensal',       'Evolução de utilizadores, lançamentos e receita',    'analytics',    '{"period":"year","group_by":"month","metrics":["new_users","releases","total_revenue"]}',   'pdf',   'mixed',  1),
(1, 'Royalties Detalhados',     'Relatório completo de royalties por faixa/mês',      'royalties',    '{"period":"month","group_by":"track","metrics":["streams","gross","fee","net"]}',           'excel', 'table',  1),
(1, 'Relatório de Utilizadores','Lista completa com status, plano e atividade',       'users',        '{"period":"month","group_by":"status","metrics":["plan","last_login","total_releases"]}',   'excel', 'table',  1),
(1, 'Top Músicas',              'Faixas mais ouvidas no período',                     'performance',  '{"period":"month","limit":50,"order_by":"streams","metrics":["streams","revenue"]}',        'pdf',   'mixed',  1);


-- Histórico de relatórios gerados
CREATE TABLE IF NOT EXISTS `_report_history` (
  `id_history`          INT(11)       NOT NULL AUTO_INCREMENT,
  `id_employees`        INT(11)       DEFAULT NULL COMMENT 'Admin que gerou',
  `id_template`         INT(11)       DEFAULT NULL COMMENT 'Modelo usado (NULL = criado do zero)',
  `name_report`         VARCHAR(150)  NOT NULL,
  `report_type`         ENUM('financial','performance','analytics','users','music','royalties','custom') NOT NULL,
  `parameters`          JSON          NOT NULL COMMENT 'Parâmetros usados na geração',
  `format`              ENUM('pdf','excel','csv','json') NOT NULL DEFAULT 'excel',
  `visualization`       ENUM('table','chart','mixed') NOT NULL DEFAULT 'table',
  `file_path`           VARCHAR(255)  DEFAULT NULL COMMENT 'Caminho do ficheiro gerado',
  `file_size_kb`        INT(11)       DEFAULT NULL,
  `status`              ENUM('processing','success','error') NOT NULL DEFAULT 'processing',
  `error_message`       TEXT          DEFAULT NULL,
  `rows_count`          INT(11)       DEFAULT NULL COMMENT 'Nº de linhas/registos no relatório',
  `send_email`          TINYINT(1)    NOT NULL DEFAULT 0,
  `save_dashboard`      TINYINT(1)    NOT NULL DEFAULT 1,
  `downloaded`          TINYINT(1)    NOT NULL DEFAULT 0,
  `downloaded_at`       DATETIME      DEFAULT NULL,
  `generated_at`        DATETIME      DEFAULT NULL,
  `creat_history`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_history`),
  KEY `id_employees` (`id_employees`),
  KEY `id_template` (`id_template`),
  KEY `idx_status` (`status`),
  KEY `idx_report_type` (`report_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Relatórios agendados (geração automática recorrente)
CREATE TABLE IF NOT EXISTS `_report_schedule` (
  `id_schedule`         INT(11)       NOT NULL AUTO_INCREMENT,
  `id_employees`        INT(11)       NOT NULL COMMENT 'Admin que criou o agendamento',
  `id_template`         INT(11)       DEFAULT NULL,
  `name_schedule`       VARCHAR(150)  NOT NULL,
  `report_type`         ENUM('financial','performance','analytics','users','music','royalties','custom') NOT NULL,
  `parameters`          JSON          NOT NULL,
  `format`              ENUM('pdf','excel','csv','json') NOT NULL DEFAULT 'excel',
  `frequency`           ENUM('daily','weekly','monthly','quarterly','yearly','custom') NOT NULL DEFAULT 'monthly',
  `custom_cron`         VARCHAR(50)   DEFAULT NULL COMMENT 'Expressão cron para frequência custom',
  `email_to`            VARCHAR(255)  DEFAULT NULL COMMENT 'E-mail(s) de destino, separados por vírgula',
  `started_at`          DATE          NOT NULL COMMENT 'Data de início do agendamento',
  `next_run`            DATETIME      DEFAULT NULL COMMENT 'Próxima execução calculada',
  `last_run`            DATETIME      DEFAULT NULL,
  `run_count`           INT(11)       NOT NULL DEFAULT 0 COMMENT 'Nº de execuções realizadas',
  `status_schedule`     ENUM('active','paused','cancelled') NOT NULL DEFAULT 'active',
  `creat_schedule`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modif_schedule`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_schedule`),
  KEY `id_employees` (`id_employees`),
  KEY `id_template` (`id_template`),
  KEY `idx_next_run` (`next_run`),
  KEY `idx_status_schedule` (`status_schedule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- FOREIGN KEYS DAS TABELAS COMPLEMENTARES
-- ============================================================

ALTER TABLE `_visitor`
  ADD CONSTRAINT `fk_vis_emp` FOREIGN KEY (`blocked_by`) REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `_visitor_pageview`
  ADD CONSTRAINT `fk_vp_visitor` FOREIGN KEY (`id_visitor`) REFERENCES `_visitor` (`id_visitor`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `_user_presence`
  ADD CONSTRAINT `fk_up_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `_user_activity_log`
  ADD CONSTRAINT `fk_ual_users` FOREIGN KEY (`id_users`) REFERENCES `_users` (`id_users`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `_broadcast`
  ADD CONSTRAINT `fk_bc_emp` FOREIGN KEY (`id_employees`) REFERENCES `_employees` (`id_employees`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `_broadcast_receipt`
  ADD CONSTRAINT `fk_bcr_broadcast` FOREIGN KEY (`id_broadcast`) REFERENCES `_broadcast` (`id_broadcast`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bcr_users`     FOREIGN KEY (`id_users`)     REFERENCES `_users`     (`id_users`)     ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `_report_template`
  ADD CONSTRAINT `fk_rt_emp` FOREIGN KEY (`id_employees`) REFERENCES `_employees` (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `_report_history`
  ADD CONSTRAINT `fk_rh_emp`      FOREIGN KEY (`id_employees`) REFERENCES `_employees`       (`id_employees`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rh_template` FOREIGN KEY (`id_template`)  REFERENCES `_report_template` (`id_template`)  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `_report_schedule`
  ADD CONSTRAINT `fk_rs_emp`      FOREIGN KEY (`id_employees`) REFERENCES `_employees`       (`id_employees`) ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rs_template` FOREIGN KEY (`id_template`)  REFERENCES `_report_template` (`id_template`)  ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================
-- FIM DAS TABELAS COMPLEMENTARES
-- Novas tabelas: 10 | Total do projeto: 50 tabelas
-- ============================================================
