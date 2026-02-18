# Wasom Upfy v2.0 — Documentação do Banco de Dados

**Versão:** 2.0  
**Motor:** MySQL / MariaDB (InnoDB)  
**Charset:** utf8mb4 / utf8mb4_unicode_ci  
**Total de tabelas:** 50  
**Arquivos SQL:** `wasomupfy_v2.sql` + `wasomupfy_v2_addon.sql`

---

## Como importar

```bash
# 1. Primeiro arquivo (base completa)
mysql -u root -p wasomupfy < wasomupfy_v2.sql

# 2. Segundo arquivo (módulos complementares)
mysql -u root -p wasomupfy < wasomupfy_v2_addon.sql
```

Ou via phpMyAdmin: importar os dois arquivos nessa ordem.

---

## Ordem de importação obrigatória

O banco tem dependências entre tabelas. A ordem correta de criação já está garantida dentro dos arquivos, mas se precisar recriar tabelas individualmente, siga esta sequência:

```
_employees → _employees_security → _employees_permissions
_plans → _plan_features
_users → _users_security → _users_sessions → _users_tokens → _user_plan
_store
_artist → _artist_collaborator → _youtube_channel
_account
_album → _track → _album_store → _takedown_request
_stream → _stream_country
_wallet → _transaction → _royalty → _withdrawal → _financial_report
_notification → _message
_invoice → _payment
_platform → _site_config
_blog_category → _blog_post
_faq → _support_ticket → _support_reply
_review
_audit_log → _visit
--- addon ---
_visitor → _visitor_pageview
_user_presence → _user_activity_log
_broadcast → _broadcast_receipt
_report_template → _report_history → _report_schedule
```

---

## Diagrama de Módulos

```
┌─────────────────────────────────────────────────────────┐
│                    WASOM UPFY v2.0                       │
├──────────────┬──────────────┬──────────────┬────────────┤
│  PLATAFORMA  │  UTILIZADORES│  FUNCIONÁRIOS│   PLANOS   │
│  _platform   │  _users      │  _employees  │  _plans    │
│  _site_config│  _users_sec  │  _emp_sec    │  _plan_feat│
│  _store      │  _users_sess │  _emp_perm   │  _user_plan│
│              │  _users_tok  │              │  _payment  │
│              │              │              │  _invoice  │
├──────────────┼──────────────┼──────────────┼────────────┤
│   ARTISTAS   │  LANÇAMENTOS │  DISTRIBUIÇÃO│  FINANÇAS  │
│  _artist     │  _album      │  _album_store│  _wallet   │
│  _artist_col │  _track      │  _store      │  _transaction│
│  _youtube    │  _takedown   │              │  _royalty  │
│  _account    │              │              │  _withdrawal│
│              │              │              │  _fin_report│
├──────────────┼──────────────┼──────────────┼────────────┤
│  ANÁLISES    │  COMUNICAÇÃO │  CONTEÚDO    │  SUPORTE   │
│  _stream     │  _notification│ _blog_cat   │  _faq      │
│  _stream_c   │  _message    │ _blog_post   │  _sup_tick │
│              │  _broadcast  │ _review      │  _sup_reply│
├──────────────┼──────────────┼──────────────┼────────────┤
│  SEGURANÇA   │  VISITANTES  │  ONLINE      │  RELATÓRIOS│
│  _audit_log  │  _visitor    │  _user_pres  │  _rep_tmpl │
│  _visit      │  _vis_page   │  _user_act   │  _rep_hist │
│              │              │  _broadcast  │  _rep_sched│
└──────────────┴──────────────┴──────────────┴────────────┘
```

---

## Referência Completa das Tabelas

### [1] Plataforma & CMS

#### `_platform`
Controla o estado global da plataforma. Só deve existir **uma linha** (id = 1).

| Campo | Tipo | Descrição |
|---|---|---|
| `id_platform` | INT PK | |
| `id_employees` | FK | Admin que fez a última alteração |
| `status` | ENUM | `active` / `maintenance` / `blocked` / `unauthorized` |
| `maintenance_msg` | TEXT | Mensagem exibida ao utilizador em manutenção |
| `allow_register` | TINYINT | 0 = cadastros fechados |
| `allow_login` | TINYINT | 0 = login desativado |
| `royalty_percentage` | DECIMAL | % dos royalties para o artista (padrão: 90) |
| `platform_fee` | DECIMAL | % retido pela Wasom (padrão: 10) |
| `usd_to_aoa_rate` | DECIMAL | Taxa de câmbio usada nas conversões |

**Como editar no CMS:** UPDATE direto na linha id=1.

---

#### `_site_config`
Pares chave-valor para configurações editáveis. Nunca apagar linhas, apenas fazer UPDATE em `config_value`.

| `config_key` | Descrição |
|---|---|
| `site_name` | Nome exibido no site |
| `facebook_url`, `instagram_url`, etc. | Redes sociais |
| `support_email` | E-mail de suporte exibido no site |
| `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass` | Configurações de e-mail (nunca exibir no frontend — `is_public = 0`) |
| `max_login_attempts` | Limite de tentativas antes do bloqueio |
| `min_withdrawal` | Valor mínimo para pedido de saque (em AOA) |

---

#### `_store`
Lojas digitais para distribuição. Editável pelo admin. Adicionar novas lojas com INSERT.

---

### [2] Utilizadores & Segurança

#### `_users`
Tabela principal dos utilizadores registados.

| Campo importante | Descrição |
|---|---|
| `status_user` | `processing` = aguarda verificação de e-mail; `pending_plan` = cadastrado mas sem plano pago; `active` = plano ativo; `suspended` = suspenso temporariamente; `fraud` = bloqueio permanente por fraude |
| `plan_selected` | FK para `_plans` — plano escolhido durante o cadastro (pode vir via URL) |

**Fluxo de cadastro (tipo Netflix):**
```
1. Utilizador preenche o formulário
   → status_user = 'processing'
   → plan_selected = NULL (ou ID do plano se veio via URL)

2. Verificação de e-mail
   → status_user = 'processing' até verificar
   → após verificar: status_user = 'pending_plan'

3. Escolha / confirmação do plano
   → INSERT em _user_plan com status = 'pending_payment'

4. Utilizador envia comprovante
   → INSERT em _payment com status = 'pending'

5. Admin aprova o pagamento
   → UPDATE _payment SET status = 'approved'
   → UPDATE _user_plan SET status = 'active', started_at = NOW()
   → UPDATE _users SET status_user = 'active'
```

---

#### `_users_security`
Sistema de bloqueio progressivo de login:

| `block_level` | Duração | Descrição |
|---|---|---|
| 0 | — | Livre |
| 1 | 5 minutos | Após 3 tentativas falhas |
| 2 | 15 minutos | Após 5 tentativas |
| 3 | 30 minutos | Após 7 tentativas |
| `is_fraud_blocked = 1` | Permanente | Bloqueio por fraude (manual pelo admin) |

**Lógica PHP de exemplo:**
```php
// Ao falhar o login
$attempts = $security->login_attempts + 1;
if ($attempts >= 7) {
    $block_level = 3; $block_until = now() + 30min;
} elseif ($attempts >= 5) {
    $block_level = 2; $block_until = now() + 15min;
} elseif ($attempts >= 3) {
    $block_level = 1; $block_until = now() + 5min;
}
```

---

#### `_users_tokens`
Tokens de uso único com expiração. Tipos:

| `type` | Uso |
|---|---|
| `email_verify` | Verificação de e-mail no cadastro |
| `password_reset` | Reset de senha via link |
| `plan_redirect` | Redirecionar para pagamento após cadastro com plano na URL |

---

### [3] Funcionários (Admin)

#### `_employees`
Roles disponíveis:

| `role` | Descrição |
|---|---|
| `super_admin` | Acesso total a tudo |
| `admin` | Acesso total exceto criar outros super_admins |
| `editor` | Pode editar conteúdo, aprovar músicas |
| `support` | Apenas visualização + resposta a tickets |

---

#### `_employees_permissions`
Para controlo granular futuro. Exemplos de `permission`:
- `users.view`, `users.edit`, `users.block`, `users.delete`
- `music.approve`, `music.reject`, `music.takedown`
- `finances.view`, `finances.process_withdrawal`
- `platform.settings`, `platform.maintenance`

---

### [4] Planos & Pagamentos

#### `_plans`
Planos editáveis pelo admin. Dados inseridos por padrão:

| Slug | Nome | Preço | Tipo |
|---|---|---|---|
| `single` | Single | 2.000 Kz / unidade | per_release |
| `album` | Álbum | 5.000 Kz / unidade | per_release |
| `artist` | Artista | 11.400 Kz / ano | subscription |
| `label` | Label | 70.000 Kz / ano | subscription |

Para editar preço: `UPDATE _plans SET price_plan = X WHERE slug_plan = 'single'`  
O histórico de preço antigo é salvo automaticamente no `_audit_log` (campo `old_value` em JSON).

---

#### `_user_plan`
Status possíveis:

| `status_plan` | Significa |
|---|---|
| `pending_payment` | Plano escolhido, aguardando pagamento |
| `active` | Plano ativo e em vigor |
| `expired` | Plano expirado (subscription vencida) |
| `cancelled` | Cancelado manualmente |

---

#### `_payment`
Fluxo de aprovação de pagamento:

```
Utilizador → envia comprovante → status = 'pending'
Admin → analisa → aprova → status = 'approved' → ativa _user_plan
Admin → rejeita → status = 'rejected' → preenche rejection_reason
```

---

### [5] Artistas & Colaboradores

#### `_artist`
Um utilizador pode ter múltiplos artistas (necessário para plano Label). Cada artista tem perfil independente com redes sociais, foto, etc.

---

#### `_account`
Conta bancária / carteira de pagamento para receber royalties. Tipos suportados: `PayPal`, `Express`, `IBAN`, `Multicaixa`, `TPA`.

---

### [6] Lançamentos

#### `_album`
Status do ciclo de vida de um lançamento:

| `status_album` | Significa |
|---|---|
| `processing` | Submetido, aguarda revisão |
| `approved` | Aprovado pelo admin, em distribuição |
| `active` | Disponível nas lojas |
| `inactive` | Desativado temporariamente |
| `blocked` | Bloqueado por violação |
| `takedown_requested` | Pedido de remoção pendente |
| `taken_down` | Removido das lojas |

---

#### `_track`
Cada faixa tem ISRC único gerado. Pertence a um `_album`. Uma música "single" = álbum com 1 faixa.

---

### [7] Distribuição

#### `_album_store`
Tabela de relação álbum ↔ loja. Quando um álbum é aprovado, inserir uma linha por loja ativa (`_store` com `is_active = 1`).

---

### [8] Análises & Streams

#### `_stream`
Streams por faixa + loja + mês. Inserir via importação de relatórios das lojas (upload de CSV/relatório). Cada linha é única por combinação `(id_track, id_store, year, month)`.

#### `_stream_country`
Mesma lógica mas com granularidade de país. Alimenta os mapas de visitantes da analytics.

---

### [9] Finanças

#### `_wallet`
Saldo do utilizador. Atualizar SEMPRE via transaction + trigger/procedure para evitar inconsistências.

**Fluxo de crédito de royalties:**
```
1. Admin processa relatório mensal
2. INSERT em _royalty (status = 'processing')
3. INSERT em _transaction (type = 'royalty_credit')
4. UPDATE _wallet SET balance_usd = balance_usd + net_royalty
5. UPDATE _royalty SET status = 'paid', id_transaction = X
6. INSERT em _notification para o utilizador
```

#### `_withdrawal`
Fluxo de saque:
```
Utilizador solicita → status = 'pending'
Admin analisa → 'processing'
Admin paga e envia comprovante → 'approved'
UPDATE _wallet SET balance_aoa = balance_aoa - amount_net
INSERT _transaction (type = 'withdrawal')
```

---

### [A] Visitantes Avançado

#### `_visitor`
Versão avançada da `_visit` básica. Registar uma linha por sessão única (baseado em `session_id`). Atualizar `last_seen` e `pages_viewed` a cada request.

**Lógica de bloqueio de IP:**
```php
// Verificar antes de processar qualquer request
SELECT status_visitor, block_until FROM _visitor WHERE ip_address = ?
if status = 'blocked' AND (block_until IS NULL OR block_until > NOW()):
    → mostrar página de bloqueio
```

**Limpeza automática recomendada (cronjob):**
```sql
-- Limpar registos com mais de 90 dias (não bloqueados)
DELETE FROM _visitor 
WHERE status_visitor = 'active' 
AND creat_visitor < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Limpar pageviews antigos
DELETE FROM _visitor_pageview 
WHERE creat_pageview < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

### [B] Utilizadores Online

#### `_user_presence`
Uma linha por utilizador (UNIQUE em `id_users`). Atualizar via heartbeat JavaScript a cada 30-60 segundos.

**Lógica de "online agora":**
```sql
-- Considerar online quem teve atividade nos últimos 3 minutos
SELECT COUNT(*) FROM _user_presence 
WHERE last_activity > DATE_SUB(NOW(), INTERVAL 3 MINUTE)
AND online_status != 'invisible';
```

**Valores de `last_activity_type`** (para filtro "Atividade" da página):
- `dashboard`, `releases`, `analytics`, `finances`, `profile`
- `uploading` (durante upload de faixa), `listening` (preview)

---

#### `_user_activity_log`
Feed de atividades visível no activity stream do admin. Inserir após cada ação relevante do utilizador.

Exemplos de `activity_type` a registar:
- `login` / `logout`
- `release_submitted` — novo lançamento submetido
- `withdrawal_requested` — pedido de saque
- `profile_updated` — perfil atualizado
- `plan_purchased` — plano comprado
- `artist_added` — novo artista criado

---

#### `_broadcast`
Anúncios enviados pelo admin para utilizadores online. Ao enviar, inserir em `_broadcast_receipt` uma linha por destinatário para rastrear quem leu.

---

### [C] Relatórios

#### `_report_template`
O campo `parameters` é JSON flexível. Exemplos:

```json
{
  "period": "month",
  "year": 2025,
  "month": 6,
  "group_by": "artist",
  "filters": {
    "country": "AO",
    "plan": "artist"
  },
  "metrics": ["streams", "revenue", "royalty"],
  "limit": 100,
  "order_by": "revenue"
}
```

---

#### `_report_schedule`
A coluna `next_run` deve ser recalculada a cada execução. Usar cronjob PHP/Node que verifica a cada hora:

```sql
SELECT * FROM _report_schedule 
WHERE status_schedule = 'active' 
AND next_run <= NOW();
```

Após executar: `UPDATE _report_schedule SET last_run = NOW(), next_run = [calcular próxima], run_count = run_count + 1`

---

## Índices criados (performance)

Os índices mais importantes para queries frequentes:

| Tabela | Campo(s) indexado(s) | Usado em |
|---|---|---|
| `_users` | `email_user`, `status_user` | Login, listagem |
| `_users_security` | `recovery_key` | Recuperação de conta |
| `_album` | `status_album`, `id_users` | Painel de lançamentos |
| `_stream` | `(id_track, id_store, year, month)` UNIQUE | Importação de relatórios |
| `_transaction` | `type_transaction`, `id_users` | Extratos financeiros |
| `_royalty` | `(id_users, id_track, year, month)` UNIQUE | Evitar duplicatas |
| `_visitor` | `ip_address`, `status_visitor`, `is_online` | Gestão de visitantes |
| `_user_presence` | `online_status`, `last_activity` | Utilizadores online |
| `_audit_log` | `action`, `creat_log` | Histórico de ações |
| `_report_schedule` | `next_run`, `status_schedule` | Cronjob de relatórios |

---

## Tabelas com dados padrão inseridos

| Tabela | Dados inseridos |
|---|---|
| `_platform` | 1 linha com configuração padrão |
| `_site_config` | 15 configurações padrão |
| `_store` | 15 lojas digitais |
| `_plans` | 4 planos (single, album, artist, label) |
| `_plan_features` | 8 features por plano |
| `_employees` | 1 super admin (José Mbenga) |
| `_employees_security` | Segurança do admin inicial |
| `_faq` | 10 FAQs migradas do banco antigo |
| `_report_template` | 6 modelos de relatório padrão |

---

## Migrações do banco antigo → v2.0

| Tabela antiga | Tabela nova | O que mudou |
|---|---|---|
| `_users` | `_users` | Adicionados: `gender`, `birth_date`, `city_user`, `email_verified`, `plan_selected`, status expandido |
| `_extrausers` | `_users_security` | Renomeada + adicionado sistema de bloqueio progressivo `block_level` |
| `_employees` | `_employees` | `business_employees` → `role` com mais opções |
| `_extraemployees` | `_employees_security` | Mesma lógica da `_users_security` |
| `_album` | `_album` | Adicionados: `id_artist`, `type_album`, `territory`, `copyright_c`, `copyright_p`, `rejection_reason`, `approved_by` |
| `_track` | `_track` | Adicionados: `track_number`, `duration_seconds`, `audio_file`, `preview_start` |
| `_listened` | `_stream` + `_store` | Reestruturação completa — colunas enum por plataforma → tabelas relacionais |
| `_analytics` | `_stream` + `_royalty` | Separação de responsabilidades |
| `_pricing` | `_plans` | Preços agora por plano individual com histórico via audit_log |
| `_payment` | `_payment` | Expandido com `payment_method`, `rejection_reason`, `reviewed_by` |
| `_sake` | `_withdrawal` | Renomeada e expandida com fluxo completo |
| `_royalites` | `_royalty` | Expandida com conversão de moeda e status |
| `_transaction` | `_transaction` | `type_transaction` expandido + `balance_before/after` |
| `_asked` | `_faq` | Renomeada + categoria + display_order |
| `_wasomupfy` | `_platform` | Expandida com todas as configurações CMS |
| `_visit` | `_visit` (básico) + `_visitor` (completo) | `_visitor` é a versão avançada no addon |

---

## Convenções usadas

- **Prefixo `_`** em todas as tabelas (padrão herdado do banco original)
- **`creat_*`** — campo de criação (não muda após INSERT)
- **`modif_*`** — campo de modificação (atualizado via `ON UPDATE CURRENT_TIMESTAMP`)
- **`status_*`** — campos de estado sempre como ENUM
- **`id_*`** — chaves estrangeiras com o mesmo nome da PK da tabela de origem
- **JSON** para configurações flexíveis (`parameters`, `old_value`, `new_value`)
- **`is_*`** — campos booleanos (TINYINT 0/1)
- **`slug_*`** — identificadores amigáveis para URL (UNIQUE, lowercase, hifenizados)

---

## Notas importantes para desenvolvimento

1. **Nunca apagar linhas da `_platform` e `_site_config`** — fazer apenas UPDATE
2. **Sempre inserir em `_audit_log`** ao editar dados sensíveis (preços, status de utilizador, aprovações)
3. **Atualizar `_wallet` apenas via transação** — nunca UPDATE direto sem criar `_transaction`
4. **O campo `parameters` em `_report_*`** usa JSON — garantir que o PHP/backend serialize corretamente
5. **Limpeza periódica recomendada:** `_visitor_pageview` (30 dias), `_visitor` não bloqueados (90 dias), `_users_sessions` expiradas (30 dias), `_users_tokens` usados/expirados (7 dias)
6. **Para o mapa de visitantes em tempo real:** usar `_visitor` com `is_online = 1` + `latitude/longitude`
7. **Heartbeat de presença:** o frontend deve fazer POST a cada 60s para atualizar `_user_presence.last_activity`

---

*Documentação gerada para Wasom Upfy v2.0 — atualizar este ficheiro sempre que novas tabelas forem adicionadas ao projeto.*
