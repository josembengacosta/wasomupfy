/**
 * WASOM UPFY v2.0 — employees.js
 * Arquivo: admin/pages/employees/js/employees.js
 *
 * Trabalha com window.__EMPLOYEES__ (JSON gerado pelo PHP)
 * e window.__BASE_URL__, __CAN_EDIT__, __MY_ID__
 */

(function () {
  "use strict";

  // ── Helpers ──────────────────────────────────────────────

  function roleBadge(role) {
    const map = {
      super_admin: '<span class="badge bg-danger">Super Admin</span>',
      admin: '<span class="badge bg-primary">Admin</span>',
      editor: '<span class="badge bg-info text-dark">Editor</span>',
      support: '<span class="badge bg-secondary">Suporte</span>',
    };
    return map[role] || `<span class="badge bg-dark">${role}</span>`;
  }

  function statusBadge(status) {
    const map = {
      active: '<span class="badge emp-status-active">Activo</span>',
      inactive: '<span class="badge emp-status-inactive">Inactivo</span>',
      blocked: '<span class="badge emp-status-blocked">Bloqueado</span>',
      suspended: '<span class="badge emp-status-suspended">Suspenso</span>',
      processing:
        '<span class="badge emp-status-processing">Em processo</span>',
    };
    return map[status] || `<span class="badge bg-secondary">${status}</span>`;
  }

  function avatarColor(name) {
    const colors = [
      "#FF0089",
      "#f97316",
      "#8b5cf6",
      "#06b6d4",
      "#22c55e",
      "#eab308",
      "#ec4899",
      "#14b8a6",
      "#3b82f6",
      "#ef4444",
    ];
    let hash = 0;
    for (let i = 0; i < name.length; i++)
      hash = name.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
  }

  function initials(first, second) {
    return (
      (first[0] || "").toUpperCase() +
      (second ? (second[0] || "").toUpperCase() : "")
    );
  }

  function avatarHtml(emp, size = 36) {
    const full = `${emp.first_name} ${emp.second_name}`.trim();
    const ini = initials(emp.first_name, emp.second_name);
    const color = avatarColor(full);
    const style = `width:${size}px;height:${size}px;border-radius:50%`;

    if (emp.photo) {
      return `<img src="${window.__BASE_URL__}/assets/comprovantes/uploads/employees/${emp.photo}"
                        alt="" style="${style};object-fit:cover;border:2px solid rgba(255,0,137,.2)"
                        onerror="this.style.display='none';this.nextElementSibling.style.display='flex'" />
                    <div style="${style};background:${color};display:none;align-items:center;justify-content:center;font-weight:700;font-size:.7rem;color:#fff">
                        ${ini}
                    </div>`;
    }
    return `<div style="${style};background:${color};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.7rem;color:#fff">
                    ${ini}
                </div>`;
  }

  function fmtDate(dt) {
    if (!dt) return "—";
    return new Date(dt).toLocaleDateString("pt-AO", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    });
  }

  function fmtDatetime(dt) {
    if (!dt) return "—";
    return new Date(dt).toLocaleString("pt-AO", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function genderLabel(g) {
    return g === "M" ? "Masculino" : g === "F" ? "Feminino" : "—";
  }

  // ── Ordenação da tabela ───────────────────────────────────

  let sortCol = null;
  let sortDir = "asc";

  document
    .querySelectorAll("#employees-table thead th[data-col]")
    .forEach((th) => {
      th.addEventListener("click", function () {
        const col = this.dataset.col;

        if (sortCol === col) {
          sortDir = sortDir === "asc" ? "desc" : "asc";
        } else {
          sortCol = col;
          sortDir = "asc";
        }

        // Atualizar classes visuais
        document.querySelectorAll("#employees-table thead th").forEach((h) => {
          h.classList.remove("sort-asc", "sort-desc");
        });
        this.classList.add(sortDir === "asc" ? "sort-asc" : "sort-desc");

        sortTable(col, sortDir);
      });
    });

  function sortTable(col, dir) {
    const tbody = document.querySelector("#employees-table tbody");
    const rows = Array.from(tbody.querySelectorAll("tr[data-id]"));

    rows.sort((a, b) => {
      const idA = parseInt(a.dataset.id);
      const idB = parseInt(b.dataset.id);
      const empA = window.__EMPLOYEES__.find((e) => e.id === idA);
      const empB = window.__EMPLOYEES__.find((e) => e.id === idB);
      if (!empA || !empB) return 0;

      let va, vb;
      switch (col) {
        case "id":
          va = empA.id;
          vb = empB.id;
          break;
        case "name":
          va = empA.first_name;
          vb = empB.first_name;
          break;
        case "user_employees":
          va = empA.username;
          vb = empB.username;
          break;
        case "email_employees":
          va = empA.email;
          vb = empB.email;
          break;
        case "tel_employees":
          va = empA.tel;
          vb = empB.tel;
          break;
        case "status_employees":
          va = empA.status;
          vb = empB.status;
          break;
        case "creat_employees":
          va = empA.created;
          vb = empB.created;
          break;
        case "last_login":
          va = empA.last_login;
          vb = empB.last_login;
          break;
        default:
          return 0;
      }

      if (typeof va === "number") {
        return dir === "asc" ? va - vb : vb - va;
      }
      va = (va || "").toLowerCase();
      vb = (vb || "").toLowerCase();
      if (va < vb) return dir === "asc" ? -1 : 1;
      if (va > vb) return dir === "asc" ? 1 : -1;
      return 0;
    });

    rows.forEach((row) => tbody.appendChild(row));
  }

  // ── Modal — Visualizar ────────────────────────────────────

  window.viewEmployee = function (id) {
    const emp = window.__EMPLOYEES__.find((e) => e.id === id);
    const modal = bootstrap.Modal.getOrCreateInstance(
      document.getElementById("viewEmployeeModal")
    );

    if (!emp) {
      document.getElementById("view-employee-body").innerHTML =
        '<p class="text-muted text-center py-3">Funcionário não encontrado nesta página.</p>';
      modal.show();
      return;
    }

    const full = `${emp.first_name} ${emp.second_name}`.trim();

    document.getElementById("view-employee-body").innerHTML = `
            <div class="d-flex align-items-center gap-3 mb-4">
                ${avatarHtml(emp, 64)}
                <div>
                    <h5 class="mb-1">${full}</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        ${roleBadge(emp.role)}
                        ${statusBadge(emp.status)}
                    </div>
                </div>
            </div>
            <div>
                <div class="detail-row"><span class="detail-label">ID</span><span class="detail-value">#${
                  emp.id
                }</span></div>
                <div class="detail-row"><span class="detail-label">Username</span><span class="detail-value">@${
                  emp.username || "—"
                }</span></div>
                <div class="detail-row"><span class="detail-label">E-mail</span><span class="detail-value">${
                  emp.email
                }</span></div>
                <div class="detail-row"><span class="detail-label">Telefone</span><span class="detail-value">${
                  emp.tel || "—"
                }</span></div>
                <div class="detail-row"><span class="detail-label">Género</span><span class="detail-value">${genderLabel(
                  emp.gender
                )}</span></div>
                <div class="detail-row"><span class="detail-label">Role</span><span class="detail-value">${roleBadge(
                  emp.role
                )}</span></div>
                <div class="detail-row"><span class="detail-label">Estado</span><span class="detail-value">${statusBadge(
                  emp.status
                )}</span></div>
                <div class="detail-row"><span class="detail-label">Membro desde</span><span class="detail-value">${fmtDate(
                  emp.created
                )}</span></div>
                <div class="detail-row"><span class="detail-label">Último login</span><span class="detail-value">${fmtDatetime(
                  emp.last_login
                )}</span></div>
                <div class="detail-row"><span class="detail-label">Tentativas de login</span>
                    <span class="detail-value">
                        <span class="badge ${
                          emp.attempts > 0 ? "bg-danger" : "bg-success"
                        }">${emp.attempts}</span>
                    </span>
                </div>
            </div>
        `;

    // Actualizar link de editar no footer do modal
    
    const editLink = document.getElementById("modal-edit-link");
    if (editLink) {
      editLink.href = `${window.__BASE_URL__}/${window.__ADMIN_PATH__}/employees/edit?id=${emp.id}`;
    }

    modal.show();
  };

  // ── Modal — Bloquear ──────────────────────────────────────

  window.confirmBlock = function (id, name) {
    document.getElementById(
      "block-msg"
    ).textContent = `Tens a certeza de que desejas bloquear "${name}"? O funcionário não conseguirá aceder ao painel.`;
    document.getElementById("block-id").value = id;

    const modal = bootstrap.Modal.getOrCreateInstance(
      document.getElementById("blockModal")
    );
    modal.show();
  };

  document
    .getElementById("confirm-block-btn")
    ?.addEventListener("click", function () {
      document.getElementById("form-block").submit();
    });

  // ── Modal — Desbloquear ───────────────────────────────────

  window.confirmUnblock = function (id, name) {
    // Reutilizar modal de bloquear mas com acção diferente
    const form = document.getElementById("form-block");
    form.querySelector('[name="action"]').value = "unblock";
    document.getElementById(
      "block-msg"
    ).textContent = `Desbloquear "${name}"? O funcionário voltará a ter acesso ao painel.`;
    document.getElementById("block-id").value = id;
    const btn = document.getElementById("confirm-block-btn");
    btn.className = "btn btn-success btn-sm";
    btn.innerHTML = '<i class="bi bi-unlock me-1"></i>Desbloquear';

    const modal = bootstrap.Modal.getOrCreateInstance(
      document.getElementById("blockModal")
    );
    modal.show();

    // Restaurar ao fechar
    document.getElementById("blockModal").addEventListener(
      "hidden.bs.modal",
      function () {
        form.querySelector('[name="action"]').value = "block";
        btn.className = "btn btn-warning btn-sm";
        btn.innerHTML = '<i class="bi bi-lock me-1"></i>Bloquear';
      },
      { once: true }
    );
  };

  // ── Modal — Excluir ───────────────────────────────────────

  window.confirmDelete = function (id, name) {
    document.getElementById(
      "delete-msg"
    ).innerHTML = `Tens a certeza de que desejas excluir permanentemente <strong>${name}</strong>?`;
    document.getElementById("delete-id").value = id;

    const modal = bootstrap.Modal.getOrCreateInstance(
      document.getElementById("deleteModal")
    );
    modal.show();
  };

  document
    .getElementById("confirm-delete-btn")
    ?.addEventListener("click", function () {
      this.disabled = true;
      this.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1"></span>A excluir...';
      document.getElementById("form-delete").submit();
    });

  // ── Exportar CSV ──────────────────────────────────────────

  document
    .getElementById("btn-export-csv")
    ?.addEventListener("click", function () {
      const data = window.__EMPLOYEES__;
      if (!data || data.length === 0) return;

      const headers = [
        "ID",
        "Nome",
        "Username",
        "E-mail",
        "Telefone",
        "Role",
        "Estado",
        "Membro desde",
        "Último login",
      ];
      const rows = data.map((e) => [
        e.id,
        `${e.first_name} ${e.second_name}`.trim(),
        e.username || "",
        e.email,
        e.tel || "",
        e.role,
        e.status,
        e.created ? e.created.split("T")[0] : "",
        e.last_login ? e.last_login.split("T")[0] : "",
      ]);

      let csv = headers.join(";") + "\n";
      rows.forEach((r) => {
        csv +=
          r.map((v) => `"${String(v).replace(/"/g, '""')}"`).join(";") + "\n";
      });

      const blob = new Blob(["\uFEFF" + csv], {
        type: "text/csv;charset=utf-8;",
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `funcionarios_${new Date().toISOString().slice(0, 10)}.csv`;
      a.click();
      URL.revokeObjectURL(url);
    });

  // ── Submissão do filtro por Enter ─────────────────────────

  document.querySelectorAll("#filter-form input").forEach((input) => {
    input.addEventListener("keydown", function (e) {
      if (e.key === "Enter") document.getElementById("filter-form").submit();
    });
  });
})();
