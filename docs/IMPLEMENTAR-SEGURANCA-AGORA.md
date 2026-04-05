# 🚀 GUIA RÁPIDO — IMPLEMENTAR SEGURANÇA AGORA

## ⚡ 5 MINUTOS — Ações Imediatas

### 1. ✅ Melhorar `.gitignore` (JÁ FEITO)
```bash
✓ .gitignore atualizado com padrões de segurança
✓ Inclui: .htaccess, .env, config.php, certificados, etc.
```

### 2. ✅ Criar `.env.example` (JÁ FEITO)
```bash
✓ Template seguro criado
✓ Pronto para copiar e configurar localmente
```

### 3. ⚡ AGORA: Fazer commit das mudanças
```bash
git add .gitignore .env.example SECURITY-CHECKLIST.md SECURITY-CLEANUP.md
git commit -m "chore: improve security — update gitignore and add env template"
git push origin main
```

---

## 🔥 20-30 MINUTOS — Limpeza do Histórico Git

### ⚠️ CRÍTICO: `.htaccess` foi commitado
O ficheiro `.htaccess` expõe as suas rotas de admin. Precisa remover do histórico.

**Opção 1: Rápido (Recomendado)**

```bash
# 1. Instalar BFG (Windows)
# Opção A: Via chocolatey
choco install bfg

# Opção B: Descarregar manual
# https://rclone.org/downloads/ → Adicionar ao PATH

# 2. Criar mirror clone
git clone --mirror https://github.com/SEU_USER/wasomupfy.git wasomupfy.git

# 3. Entrar na pasta
cd wasomupfy.git

# 4. Remover ficheiros sensíveis
bfg --delete-files .htaccess
bfg --delete-files config.php
bfg --delete-files .env

# 5. Limpar
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# 6. Push forçado
git push --mirror

# 7. Apagar clone
cd ..
rm -rf wasomupfy.git
```

**Opção 2: Manual (Git Nativo)**

```bash
# Remover .htaccess do histórico completo
git filter-branch --tree-filter 'rm -f .htaccess' HEAD

# Remover config.php
git filter-branch --tree-filter 'rm -f config.php' HEAD

# Limpar referências
rm -rf .git/refs/original/
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# Push forçado
git push --force --all origin
git push --force --tags origin
```

### Verificar que foi removido

```bash
# Não deve mostrar nada
git log -p --all -- .htaccess
git log -p --all -- config.php

# Se mostrar algo, a limpeza falhou — refazer
```

---

## 🛡️ AGORA: Rotação de Credenciais

### 1. BD — Alterar Password
```sql
-- SSH para servidor / phpMyAdmin
ALTER USER 'wasomupfy_app'@'localhost' IDENTIFIED BY 'NewSecurePass123!@#$%';
FLUSH PRIVILEGES;
```

Depois atualizar `config.php` ou `.env` localmente.

### 2. Chaves de API
Se `.env` foi exposto com valores reais:

- [ ] Regenerar VAPID keys (Web Push)
- [ ] Regenerar JWT_SECRET
- [ ] Regenerar APP_KEY
- [ ] Revogar tokens Stripe/PayPal antigos e gerar novos

### 3. Atualizar BD
```php
// Executar uma única vez via admin panel ou script
php -r "
require_once 'configuration.php';
\$db = getDB();

// Rodar isto manualmente no painel de admin settings
// Ou via script:
\$db->exec(\"UPDATE _admin_config SET config_value='NEW_VAPID_KEY' WHERE config_key='vapid_public_key'\");
\$db->exec(\"UPDATE _admin_config SET config_value='NEW_JWT_SECRET' WHERE config_key='jwt_secret'\");
"
```

---

## 📋 CHECKLIST DE HOJE

Marcar conforme completa:

- [ ] `.gitignore` melhorado (automático ✓)
- [ ] `.env.example` criado (automático ✓)
- [ ] Commit com mudanças feito (`git push`)
- [ ] Histórico do Git limpo (BFG ou git filter-branch)
- [ ] Credenciais de BD alteradas
- [ ] Chaves de API regeneradas
- [ ] Atualizar valores em `.env` localmente

**Tempo total:** ~30-45 minutos

---

## 🔒 FASE 2 — Esta Semana

### 1. SSL/HTTPS
```bash
# Verificar certificado atual
openssl s_client -connect wasomupfy.com:443

# Renovar com Let's Encrypt (gratuito)
# Via cPanel: AutoSSL
# Via terminal: certbot auto-renewal
```

### 2. Ativar Rate Limiting
```apache
# Adicionar ao .htaccess
# Bloquear IP após 60 requests/minuto
<IfModule mod_ratelimit.c>
    SetOutputFilter RATE_LIMIT
    SetEnv rate-limit 60
</IfModule>
```

### 3. Headers de Segurança
```apache
# .htaccess
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
```

### 4. Teste de Penetration Online (Gratuito)
- [ ] Correr: https://www.ssllabs.com/ssltest/ (SSL/TLS)
- [ ] Correr: https://securityheaders.com/ (Response headers)
- [ ] Correr: https://www.owasp.org/index.php/OWASP_ZAP (vulnerabilidades)

---

## 📞 SE ALGO ESTÁ ERRADO

### "Erro ao fazer push forçado"

```bash
# GitHub pode ter branch protection rules
# Solução: https://github.com/SEU_USER/wasomupfy → Settings → Branches
# Desativar "Require pull request reviews before merging" temporariamente
# Fazer push forçado
# Reativar proteção
```

### ".htaccess ainda aparece no Git log"

```bash
# Significa a limpeza não funcionou completamente
# Opções:
# 1. Refazer com BFG (mais confiável)
# 2. Criar repositório novo (último recurso)
git clone https://github.com/SEU_USER/wasomupfy.git wasomupfy-new
# ... remover ficheiros manualmente ...
git push origin main
```

### "Perdi configurações locais"

```bash
# Se fez checkout do histórico limpo e perdeu .htaccess local:
git checkout HEAD -- .htaccess
# Recupera a versão local
```

---

## 📊 STATUS SEGURANÇA

| Item | Status | Data | Próximo Passo |
|------|--------|------|---------------|
| `.gitignore` | ✅ | 2026-04-04 | Commit |
| `.env.example` | ✅ | 2026-04-04 | Usar como template |
| Histórico Git | ⏳ | TODO | Executar limpeza |
| Credenciais | ⏳ | TODO | Rotação de passwords |
| SSL/HTTPS | 🔍 | TODO | Verificar certificado |
| Rate Limiting | ❌ | TODO | Implementar esta semana |
| Backup BD | ❌ | TODO | Configurar cron |

---

## 🎯 CONCLUSÃO

Acabaste de:
1. ✅ Melhorar proteção de ficheiros sensíveis no Git
2. ✅ Criar template seguro de configuração
3. ⏳ Próximo: Limpar histórico exposto (30 min)
4. ⏳ Depois: Rotação de credenciais (10 min)

**Resultado:** Projeto consideravelmente mais seguro em menos de 1 hora.

---

**Documento:** IMPLEMENTAR-SEGURANCA-AGORA.md  
**Data:** 2026-04-04  
**Prioridade:** 🔴 CRÍTICA  
**Tempo Estimado:** 46 minutos
