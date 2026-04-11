<?php

/**
 * WASOM UPFY v2.0 — Modal de Saque (include partilhado)
 * Arquivo: dashboard/finances/_modal_withdrawal.php
 *
 * Pré-requisitos (devem existir antes do include):
 *   $id_users      (int)
 *   $balance_aoa   (float)
 *   $bank_account  (array|null)  — linha de _account verificada
 *   $can_withdraw  (bool)
 *   $min_withdrawal (float)      — default 10000
 *
 * Uso:
 *   <?php include __DIR__ . '/../finances/_modal_withdrawal.php'; ?>
* ou
* <?php include __DIR__ . '/finances/_modal_withdrawal.php'; ?>
*/

// Calcular taxa e valor líquido (taxa 0% na fase actual — ajustar depois)
$withdrawal_fee_pct = 0.00; // percentagem — alterar aqui quando necessário
$fee_amount = round($balance_aoa * $withdrawal_fee_pct / 100, 2);
$net_amount = round($balance_aoa - $fee_amount, 2);

// Verificar saque já pendente (bloquear duplicado)
$pending_stmt = getDB()->prepare("
SELECT id_withdrawal FROM _withdrawal
WHERE id_users = ? AND status_withdrawal IN ('pending','processing')
LIMIT 1
");
$pending_stmt->execute([$id_users]);
$has_pending_withdrawal = (bool)$pending_stmt->fetch();
?>

<!-- ════════════════════════════════════════════════════
     MODAL DE SAQUE — partilhado entre painel e overview
═════════════════════════════════════════════════════ -->
<div class="modal fade" id="sake" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="sakeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title text-dark" id="sakeLabel">
                    <i class="bi bi-wallet2 me-2" style="color:#FF0089"></i>Solicitar Saque
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <?php if ($has_pending_withdrawal): ?>
                <!-- ─ Já tem saque pendente ─ -->
                <div class="text-center py-4">
                    <i class="bi bi-hourglass-split fs-1 mb-3 d-block" style="color:#f59e0b"></i>
                    <h6 class="fw-bold">Saque em processamento</h6>
                    <p class="text-muted small">
                        Já tens um pedido de saque pendente. Aguarda a conclusão antes de solicitar um novo.
                        Receberás uma notificação por e-mail quando estiver processado.
                    </p>
                    <a href="overview" class="btn btn-sm btn-outline-secondary mt-2">
                        <i class="bi bi-bar-chart me-1"></i>Ver estado em Finanças
                    </a>
                </div>

                <?php elseif (!$plan_paid): ?>
                <!-- ─ Sem plano activo ─ -->
                <div class="text-center py-4">
                    <i class="bi bi-lock fs-1 text-muted mb-3 d-block"></i>
                    <h6>Plano não activo</h6>
                    <p class="text-muted small">Activa o teu plano para começar a receber royalties e sacar.</p>
                    <a href="payment" class="btn btn-pink btn-sm">Activar Plano</a>
                </div>

                <?php elseif (!$bank_account): ?>
                <!-- ─ Sem conta bancária ─ -->
                <div class="text-center py-4">
                    <i class="bi bi-bank fs-1 text-muted mb-3 d-block"></i>
                    <h6>Sem conta bancária registada</h6>
                    <p class="text-muted small">
                        Para sacar os teus royalties, primeiro regista uma conta bancária verificada.
                    </p>
                    <a href="withdraw" class="btn btn-pink btn-sm">
                        <i class="bi bi-plus me-1"></i>Registar Conta Bancária
                    </a>
                </div>

                <?php elseif ($balance_aoa < $min_withdrawal): ?>
                <!-- ─ Saldo insuficiente ─ -->
                <div class="text-center py-4">
                    <i class="bi bi-piggy-bank fs-1 text-muted mb-3 d-block" style="opacity:.5"></i>
                    <h6>Saldo insuficiente</h6>
                    <p class="text-muted small">
                        O mínimo para saque é <strong><?php echo number_format($min_withdrawal, 0, ',', '.'); ?>
                            Kz</strong>.<br>
                        O teu saldo actual é <strong><?php echo number_format($balance_aoa, 2, ',', '.'); ?>
                            AOA</strong>.
                    </p>
                    <div class="progress mt-3" style="height:8px;max-width:220px;margin:auto">
                        <div class="progress-bar"
                            style="width:<?php echo min(100, round($balance_aoa / $min_withdrawal * 100)); ?>%;background:#FF0089">
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">
                        <?php echo number_format($balance_aoa, 0, ',', '.'); ?> /
                        <?php echo number_format($min_withdrawal, 0, ',', '.'); ?> Kz
                    </small>
                </div>

                <?php else: ?>
                <!-- ─ Pode sacar ─ -->
                <p class="text-muted small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    O valor total disponível será transferido para a conta registada. O processamento demora até 48
                    horas.
                </p>

                <!-- Resumo da operação -->
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <div class="card text-center py-3 px-2">
                            <div class="small text-muted mb-1">Saldo disponível</div>
                            <div class="fw-bold fs-5"><?php echo number_format($balance_aoa, 2, ',', '.'); ?> Kz</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center py-3 px-2">
                            <div class="small text-muted mb-1">Taxa (<?php echo $withdrawal_fee_pct; ?>%)</div>
                            <div class="fw-bold fs-5 text-muted">
                                <?php echo $withdrawal_fee_pct > 0 ? '- ' . number_format($fee_amount, 2, ',', '.') . ' Kz' : 'Gratuito'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center py-3 px-2" style="border-color:#FF0089">
                            <div class="small text-muted mb-1">Recebes</div>
                            <div class="fw-bold fs-5" style="color:#FF0089">
                                <?php echo number_format($net_amount, 2, ',', '.'); ?> Kz</div>
                        </div>
                    </div>
                </div>

                <!-- Conta destino -->
                <div class="card mb-3 p-3 d-flex flex-row align-items-center gap-3">
                    <?php if (in_array($bank_account['type_account'], ['IBAN', 'Multicaixa'])): ?>
                    <i class="bi bi-bank fs-4 text-primary flex-shrink-0"></i>
                    <div>
                        <div class="fw-semibold small">
                            <?php echo htmlspecialchars($bank_account['full_name_account']); ?></div>
                        <div class="text-muted" style="font-size:.78rem">
                            IBAN &middot; <?php echo htmlspecialchars(substr($bank_account['iban'] ?? '', -8)); ?>
                            <?php if ($bank_account['bank_name'] ?? ''): ?>
                            &middot; <?php echo htmlspecialchars($bank_account['bank_name']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <i class="bi bi-phone-fill fs-4 text-success flex-shrink-0"></i>
                    <div>
                        <div class="fw-semibold small">
                            <?php echo htmlspecialchars($bank_account['full_name_account']); ?></div>
                        <div class="text-muted" style="font-size:.78rem">
                            Multicaixa Express &middot;
                            <?php echo htmlspecialchars($bank_account['tel_account'] ?? ''); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <a href="withdraw" class="btn btn-sm btn-outline-secondary ms-auto">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>

                <!-- Formulário de confirmação -->
                <form id="withdrawal-form">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="id_account" value="<?php echo $bank_account['id_account']; ?>">
                    <input type="hidden" name="amount_aoa" value="<?php echo $balance_aoa; ?>">
                    <input type="hidden" name="amount_fee" value="<?php echo $fee_amount; ?>">
                    <input type="hidden" name="amount_net" value="<?php echo $net_amount; ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Confirmar com a tua senha <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control" name="password" id="withdrawal-pwd"
                            placeholder="Senha da tua conta Wasom Upfy" autocomplete="current-password" required>
                    </div>

                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-shield-check me-1"></i>
                        Ao confirmar, autorizes a transferência de
                        <strong><?php echo number_format($net_amount, 2, ',', '.'); ?> AOA</strong>
                        para a conta acima. Esta operação não pode ser revertida.
                    </div>
                </form>
                <?php endif; ?>

            </div><!-- /modal-body -->

            <?php if (!$has_pending_withdrawal && $can_withdraw): ?>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-pink" id="btn-confirm-withdrawal" onclick="confirmWithdrawal()">
                    <i class="bi bi-send me-2"></i>Confirmar Saque
                </button>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- ── Dependências do modal (carrega apenas se ainda não estiverem na página) ── -->
<script>
if (typeof Swal === 'undefined') {
    document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"><\/script>');
}
if (typeof toastr === 'undefined') {
    document.write(
        '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">');
    document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"><\/script>');
}
</script>

<!-- ── Script do modal (injecto inline para funcionar em qualquer página) ── -->
<script>
function confirmWithdrawal() {
    const pwd = document.getElementById('withdrawal-pwd');
    if (!pwd || !pwd.value.trim()) {
        // Toastr para erro de campo vazio — não precisa bloquear
        if (typeof toastr !== 'undefined') {
            toastr.warning('Insere a tua senha para confirmar o saque.', 'Campo obrigatório');
        }
        pwd && pwd.focus();
        return;
    }

    const net = '<?php echo number_format($net_amount, 2, ',', '.'); ?>';
    const dest =
        '<?php echo addslashes(in_array($bank_account['type_account'] ?? '', ['IBAN', 'Multicaixa']) ? 'IBAN' : 'Express'); ?>';

    // SweetAlert2 — confirmação crítica antes de processar
    Swal.fire({
        title: 'Confirmar Saque?',
        html: `
      <p style="font-size:.9rem;color:#555">
        Vais transferir <strong style="color:#FF0089">${net} AOA</strong>
        para a tua conta <strong>${dest}</strong>.
      </p>
      <p style="font-size:.82rem;color:#aaa">Esta operação não pode ser revertida.</p>
    `,
        icon: 'warning',
        iconColor: '#FF0089',
        showCancelButton: true,
        confirmButtonColor: '#FF0089',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-send me-1"></i>Sim, sacar agora',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => {
            const fd = new FormData(document.getElementById('withdrawal-form'));
            return fetch('finances/withdrawal_process', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) {
                        Swal.showValidationMessage(data.message || 'Erro ao processar.');
                        return false;
                    }
                    return data;
                })
                .catch(() => {
                    Swal.showValidationMessage('Erro de ligação. Tenta novamente.');
                    return false;
                });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then(result => {
        if (result.isConfirmed) {
            bootstrap.Modal.getInstance(document.getElementById('sake')).hide();
            // SweetAlert de sucesso — momento importante, merece destaque
            Swal.fire({
                title: 'Saque solicitado!',
                html: `
          <p>O teu pedido de <strong style="color:#FF0089">${net} AOA</strong> foi registado com sucesso.</p>
          <p style="font-size:.85rem;color:#888">Receberás uma notificação por e-mail quando a transferência for processada (até 48 horas).</p>
        `,
                icon: 'success',
                confirmButtonColor: '#FF0089',
                confirmButtonText: 'Fechar'
            }).then(() => location.reload());
        }
    });
}
</script>