# 🔐 LIMPEZA DE SEGURANÇA — Remover Arquivos Sensíveis do Histórico Git

## ⚠️ PROBLEMA IDENTIFICADO
Os seguintes arquivos sensíveis foram commitados no GitHub:
- `.htaccess` (expõe rotas e regras de segurança)
- Possivelmente `config.php`, `.env`, ou outras credenciais

## ✅ SOLUÇÃO PASSO-A-PASSO

### **Passo 1: Fazer Backup Completo (IMPORTANTE)**
```bash
# Antes de fazer qualquer coisa, faz backup local
cd c:\xampp\htdocs\wasomupfy
git bundle create wasomupfy-backup.bundle --all
# Guarda este arquivo num local seguro
```

### **Passo 2: Remover Arquivos Sensíveis do Histórico Git**

#### **Opção A: Usando BFG Repo-Cleaner (RECOMENDADO — mais rápido)**

1. Descarregar BFG:
   ```bash
   # Windows — via Chocolatey (se tiver instalado)
   choco install bfg
   
   # Ou descarregar manualmente em https://rclone.org/downloads/
   # E adicionar ao PATH
   ```

2. Criar ficheiro com padrões a remover (`sensitive-files.txt`):
   ```txt
   .htaccess
   config.php
   config.local.php
   database.php
   connection.php
   secrets.php
   ```

3. Executar BFG (em clone espelho — jamais no repositório original):
   ```bash
   # Mirror clone
   git clone --mirror https://github.com/SEU_USER/REPO_NAME.git wasomupfy.git
   
   # Remover ficheiros do histórico
   cd wasomupfy.git
   bfg --delete-files sensitive-files.txt
   
   # Limpar e fazer push
   git reflog expire --expire=now --all
   git gc --prune=now --aggressive
   git push --mirror
   ```

#### **Opção B: Usando Git Filter-Branch (compatível com Windows)**

```bash
# Remover .htaccess do histórico
git filter-branch --tree-filter 'rm -f .htaccess' HEAD

# Remover config.php
git filter-branch --tree-filter 'rm -f config.php' HEAD

# Remover .env
git filter-branch --tree-filter 'rm -f .env .env.local' HEAD

# Fazer push forçado
git push --force --all origin
git push --force --tags origin
```

### **Passo 3: Limpar Referências Locais**

```bash
# Remover todas as referências ao histórico antigo
rm -rf .git/refs/original/
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# Verificar que foi limpo
git log --all --full-history -- .htaccess
# Não deve mostrar nada
```

### **Passo 4: Rotação de Credenciais (CRÍTICO!)**

Se `.env` ou `config.php` com credenciais reais foram expostos:

1. **Credenciais de BD:**
   - Alterar password do utilizador MySQL
   - Atualizar `config.php` localmente

2. **Chaves de API/Integração:**
   - Regenerar tokens (VAPID, Mailgun, etc.)
   - Atualizar no `_admin_config` na BD

3. **Certificados SSL:**
   - Se `.key` foi exposto, revogar e gerar novo certificado

### **Passo 5: Proteger o Repositório no GitHub**

1. Ir a **Settings → Security & Analysis → Secret scanning**
   - ✅ Ativar "Push protection"
   - Impede novos commits com segredos

2. Ir a **Settings → Branch protection rules**
   - Requerer revisão de PRs
   - Bloquear push direto

3. Ir a **Settings → Webhooks**
   - Remover qualquer webhook desnecessário

### **Passo 6: Documentar a Mudança**

Adicionar nota ao repositório (para equipa):

```bash
git commit --allow-empty -m "chore: cleanup sensitive files from history [skip ci]"
git push origin main
```

## 📋 VERIFICAÇÃO FINAL

Confirmar que arquivos sensíveis foram removidos:

```bash
# Procurar por .htaccess em TODO o histórico
git log -p --all -- .htaccess
# Resultado esperado: nenhum output

# Procurar por linhas com credenciais
git log -p --all | grep -i "password\|api_key\|secret"
# Resultado esperado: nenhum output relevante
```

## 🛡️ BOAS PRÁTICAS DAQUI EM DIANTE

1. **Usar `.env` para ALL credenciais** (já está no novo `.gitignore`)
2. **Revisar commits antes de push:**
   ```bash
   git diff origin/main..HEAD
   ```
3. **Usar git hooks para validação:**
   - Instalar `pre-commit` framework
   - Previne commits acidentais de senhas

4. **Monitorizar repositório:**
   - GitHub: Settings → Secret scanning notifications

---

## ❌ O QUE NÃO FAZER

- ❌ Simplesmente remover arquivo e fazer novo commit (fica no histórico)
- ❌ Fazer rebase/squash (não remove do histórico)
- ❌ Confiar em `git rm` (fica no histórico + rebase)
- ❌ Ignorar a rotação de credenciais (ainda estão expostas)

---

**Data:** 2026-04-04  
**Status:** ✅ PLANO PRONTO PARA EXECUÇÃO  
**Risco:** Alto (dados sensíveis expostos) → Média (após limpeza)
