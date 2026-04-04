# 🔒 CHECKLIST DE SEGURANÇA — WASOMUPFY v2.0

## ✅ FASE 1: CONFIGURAÇÃO IMEDIATA

### 1. Variáveis de Ambiente
- [ ] Criar `.env.example` com placeholders (SEM valores reais)
- [ ] Adicionar instrução de copy: `cp .env.example .env`
- [ ] Verificar que `.env` NÃO está no Git

```php
// .env.example — MODELO SEGURO
DB_HOST=localhost
DB_NAME=wasomupfy_db
DB_USER=root
DB_PASS=your_password_here  # ← PLACEHOLDER, não valor real
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USER=your_email@example.com
MAIL_PASS=your_app_password_here
APP_KEY=change_me_in_production
JWT_SECRET=change_me_in_production
```

### 2. Chaves de Criptografia
- [ ] Gerar APP_KEY e JWT_SECRET únicos para cada ambiente
- [ ] Guardar em variáveis de ambiente, NÃO em código
- [ ] Rotar periodicament (p.ex., a cada 6 meses)

```bash
# Gerar uma chave segura (64 caracteres)
php -r "echo bin2hex(random_bytes(32));"
```

### 3. Credenciais de BD
- [ ] Criar utilizador MySQL específico com permissões mínimas (NÃO root)
- [ ] Password forte (mínimo 20 caracteres, maiúsc + minúsc + números + símbolos)
- [ ] Guardar APENAS em `.env`

```sql
-- Exemplo de utilizador seguro
CREATE USER 'wasomupfy_app'@'localhost' IDENTIFIED BY 'SuperSecurePassword123!@#';
GRANT SELECT, INSERT, UPDATE, DELETE ON wasomupfy_db.* TO 'wasomupfy_app'@'localhost';
FLUSH PRIVILEGES;
```

### 4. Certificados e Chaves SSL
- [ ] Gerar certificado SSL para HTTPS (Let's Encrypt gratuito)
- [ ] NÃO commitar `.key` ou `.pem` no Git
- [ ] Guardar num diretório protegido: `/etc/ssl/private/` ou similiar

### 5. Headers de Segurança HTTP
- [ ] Adicionar ao `.htaccess` ou `config.php`:

```apache
# .htaccess — Headers Segurança
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
```

---

## ✅ FASE 2: AUTENTICAÇÃO & SESSÃO

### 6. Autenticação de Admin
- [ ] Reforçar limite de tentativas de login (5 tentativas em 5 min → bloquear 30 min)
- [ ] Implementar 2FA/TOTP (já existe em `authentic/2fa-verify.php`?)
- [ ] Adicionar notificação de login suspeito por email

### 7. Gestão de Sessões
- [ ] Timeout de sessão: máximo 60 minutos (configurável no admin settings)
- [ ] Regenerar ID de sessão após login: `session_regenerate_id(true)`
- [ ] Marcar cookies de sessão como HTTP-only e Secure

```php
// php.ini ou config.php
session_set_cookie_params([
    'secure' => true,      // HTTPS only
    'httponly' => true,    // Não acessível via JavaScript
    'samesite' => 'Strict' // CSRF protection
]);
```

### 8. CSRF Protection
- [ ] Verificar que TODOS os forms têm `csrf_token`
- [ ] Validar token em TODOS os POST/PUT/DELETE
- [ ] Revalidar token após envio bem-sucedido

---

## ✅ FASE 3: BANCO DE DADOS

### 9. Prepared Statements
- [ ] Auditoria: verificar que NÃO há SQL injection (`$db->query()` sem placeholders)
- [ ] Usar SEMPRE `$db->prepare()` + `->execute($params)`

### 10. Encriptação de Dados Sensíveis
- [ ] Passwords: usar `password_hash()` com `PASSWORD_BCRYPT` (nunca MD5 ou SHA1)
- [ ] Tokens sensíveis: encriptar com AES-256
- [ ] PII (dados pessoais): considerar encriptação em repouso

### 11. Backups Automáticos
- [ ] Backup diário da BD (script cron)
- [ ] Guardar backups num local seguro (NÃO no servidor público)
- [ ] Testar restore 1x por mês
- [ ] Retenção: guardar últimos 30 dias

```bash
# Cron job — backup diário
0 2 * * * /usr/bin/mysqldump -u wasomupfy_app -p${DB_PASS} wasomupfy_db | gzip > /var/backups/wasomupfy-$(date +\%Y\%m\%d).sql.gz
```

---

## ✅ FASE 4: CONTROLO DE ACESSO

### 12. Permissões por Papel
- [ ] Audit: verificar tabela `_roles` e `_permissions`
- [ ] Admin: máximos privilégios
- [ ] Support: acesso a tickets, sem financeiro
- [ ] Moderador: apenas conteúdo/reportes
- [ ] Usuário: apenas própria conta

### 13. Whitelist de IPs
- [ ] Ativar whitelist para admin login (já existe em settings)
- [ ] Registar TODOS os acessos de admin em `_audit_log`

### 14. Rate Limiting
- [ ] API endpoints: máximo 60 req/min por IP
- [ ] Login: 5 tentativas/5 min
- [ ] Upload de ficheiros: 10 ficheiros/min

---

## ✅ FASE 5: LOGS & MONITORIZAÇÃO

### 15. Audit Log
- [ ] Registar: logins, alterações de config, operações sensíveis
- [ ] Retenção: 90 dias (configurável)
- [ ] Alertar admin se tentativa de acesso falhado > 10/hora

### 16. Erros & Stack Traces
- [ ] Produção: NÃO expor stack traces ao utilizador
- [ ] Logar erros internamente
- [ ] Mostrar apenas "Erro 500 — Suporte contacto"

```php
// Não fazer isto em produção:
// throw new Exception($error_details);

// Fazer isto:
error_log('[CRITICAL] ' . $error_details);
throw new Exception('Erro interno. Código: ' . $error_id);
```

### 17. Performance & DoS
- [ ] Limitar upload: máximo 100 MB por ficheiro
- [ ] Request payload: máximo 10 MB
- [ ] Query timeout: 30 segundos
- [ ] Conexões simultâneas: máximo 100 por user

---

## ✅ FASE 6: DEPENDÊNCIAS & ATUALIZAÇÃO

### 18. Composer Dependencies
- [ ] Executar: `composer audit` (detectar vulnerabilidades)
- [ ] Atualizar regularmente: `composer update`
- [ ] Não usar pacotes abandonados (verificar último update)

### 19. Versionamento
- [ ] Usar versões semanticamente: `1.2.3` (major.minor.patch)
- [ ] Changelog.md atualizado
- [ ] Tags git para cada release

### 20. Correções de Segurança
- [ ] Monitorizar alertas do GitHub automaticamente
- [ ] Patch emergências em 48h
- [ ] Manter histórico de correções

---

## ✅ FASE 7: DEPLOYMENT

### 21. Ambiente de Produção
- [ ] Desativar debug mode: `define('DEBUG', false)`
- [ ] Remover ficheiros de teste: `_test.php`, `debug.php`
- [ ] Permissions corretas: config PHP não pode ser lido pelo web server
- [ ] Separar `/var/www/html` (código público) de `/var/www/private` (BD backups, logs)

### 22. Certificado SSL/TLS
- [ ] HTTPS obrigatório
- [ ] Redirecionar HTTP → HTTPS
- [ ] Certificate renewal automático (Let's Encrypt)

### 23. Firewall & Networking
- [ ] Bloquear portos desnecessários (abrir apenas 80, 443, 22 para admin)
- [ ] Isolamento: BD num container/servidor separado
- [ ] VPN para admin access se possível

---

## ✅ FASE 8: COMPLIANCE & LEGAL

### 24. Privacy & RGPD
- [ ] Política de Privacidade atualizada
- [ ] Consentimento explícito para cookies/tracking
- [ ] Direito ao esquecimento: `DELETE FROM _users WHERE id = ?`
- [ ] Data export: permitir download de dados próprios

### 25. Termos de Serviço
- [ ] Explicar retenção de dados
- [ ] Limitações de responsabilidade
- [ ] Políticas de pagamento

### 26. Auditorias
- [ ] Teste de penetration 1x por ano
- [ ] Revisão de código por terceiros
- [ ] Scanning de vulnerabilidades (OWASP ZAP, etc.)

---

## 📊 STATUS ATUAL — CHECKLIST RÁPIDO

| Área | Status | Prioridade | ação |
|------|--------|----------|------|
| `.env` Seguro | ⚠️ Investigar | CRÍTICA | Criar `.env.example` |
| `.htaccess` Exposto | ⚠️ SIM | CRÍTICA | Correr limpeza histórico Git |
| 2FA Admin | ⚠️ Investigar | ALTA | Verificar `authentic/2fa-*.php` |
| Audit Log | ✅ Existe | MÉDIA | Confirmar que está a registar |
| Whitelist IP | ✅ Existe | ALTA | Testar em settings |
| Rate Limiting | ❌ Não | ALTA | Implementar middleware |
| SSL/HTTPS | ⚠️ Investigar | CRÍTICA | Verificar certificado |
| Backup BD | ⚠️ Investigar | CRÍTICA | Criar script cron |
| Composer Audit | ❌ Não | ALTA | Rodar `composer audit` |

---

## 🚀 PRÓXIMOS PASSOS (Ordem de Prioridade)

1. **HOJE:** Executar limpeza do Git (ver `SECURITY-CLEANUP.md`)
2. **HOJE:** Criar `.env.example` e validar que `.env` real NÃO está no Git
3. **AMANHÃ:** Rotação de credenciais (BD, API keys)
4. **ESTA SEMANA:** Implementar rate limiting e melhorar audit logs
5. **ESTE MÊS:** Certificado SSL, backup automático, teste de penetration

---

**Documento:** SECURITY-CHECKLIST.md  
**Data:** 2026-04-04  
**Versão:** 1.0  
**Status:** 🔴 AÇÃO REQUERIDA (dados sensíveis expostos)
