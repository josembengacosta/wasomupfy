// delete-employee.js - Página de Exclusão de Funcionários
document.addEventListener("DOMContentLoaded", function () {
  // Dados simulados de funcionários
  const mockEmployees = Array.from({ length: 20 }, (_, i) => ({
    id: 1000 + i,
    avatar: `https://i.pravatar.cc/150?img=${i % 50}`,
    account: `funcionario${i}`,
    name: `Funcionário ${i}`,
    username: `func${i}`,
    email: `func${i}@wasomupfy.com`,
    roles: getRandomRoles(),
    country: getRandomCountry(),
    city: getRandomCity(),
    status: getRandomStatus(),
    createdAt: getRandomDate(),
    phone: getRandomPhone(),
    plan: getRandomPlan(),
    department: getRandomDepartment(),
    lastLogin: getRandomLastLogin(),
    isAdmin: Math.random() > 0.7,
  }));

  function getRandomRoles() {
    const allRoles = ["admin", "distributor", "analyst", "financial"];
    const count = Math.floor(Math.random() * 3) + 1;
    const shuffled = allRoles.sort(() => 0.5 - Math.random());
    return shuffled.slice(0, count);
  }

  function getRandomCountry() {
    const countries = ["AO", "PT", "BR", "MZ"];
    return countries[Math.floor(Math.random() * countries.length)];
  }

  function getRandomCity() {
    const cities = {
      AO: ["Luanda", "Huambo", "Benguela", "Lubango"],
      PT: ["Lisboa", "Porto", "Coimbra", "Braga"],
      BR: ["São Paulo", "Rio de Janeiro", "Belo Horizonte", "Salvador"],
      MZ: ["Maputo", "Beira", "Nampula", "Quelimane"],
    };
    const country = getRandomCountry();
    return cities[country][Math.floor(Math.random() * cities[country].length)];
  }

  function getRandomStatus() {
    const statuses = ["active", "suspended", "review"];
    return statuses[Math.floor(Math.random() * statuses.length)];
  }

  function getRandomDate() {
    const date = new Date();
    date.setDate(date.getDate() - Math.floor(Math.random() * 365));
    return date.toISOString().split("T")[0];
  }

  function getRandomPhone() {
    const prefixes = ["+244", "+351", "+55", "+258"];
    return (
      prefixes[Math.floor(Math.random() * prefixes.length)] +
      " 9" +
      Math.floor(Math.random() * 90000000 + 10000000)
    );
  }

  function getRandomPlan() {
    const plans = ["Single", "Profissional", "Enterprise", "Admin"];
    return plans[Math.floor(Math.random() * plans.length)];
  }

  function getRandomDepartment() {
    const departments = [
      "Financeiro",
      "TI",
      "Marketing",
      "RH",
      "Operações",
      "Suporte",
    ];
    return departments[Math.floor(Math.random() * departments.length)];
  }

  function getRandomLastLogin() {
    const date = new Date();
    date.setDate(date.getDate() - Math.floor(Math.random() * 30));
    return (
      date.toISOString().split("T")[0] +
      " " +
      Math.floor(Math.random() * 24)
        .toString()
        .padStart(2, "0") +
      ":" +
      Math.floor(Math.random() * 60)
        .toString()
        .padStart(2, "0")
    );
  }

  // Estado da aplicação
  let selectedEmployees = new Set();
  let filteredEmployees = [...mockEmployees];
  let searchTerm = "";
  let deletionReason = "";

  // Elementos DOM
  const employeesList = document.getElementById("employeesList");
  const searchEmployeeInput = document.getElementById("searchEmployee");
  const selectionCount = document.getElementById("selectionCount");
  const selectedCount = document.getElementById("selectedCount");
  const totalCount = document.getElementById("totalCount");
  const adminCount = document.getElementById("adminCount");
  const selectAllBtn = document.getElementById("selectAllBtn");
  const deselectAllBtn = document.getElementById("deselectAllBtn");
  const employeeDetails = document.getElementById("employeeDetails");
  const noSelectionMessage = document.getElementById("noSelectionMessage");
  const singleEmployeeDetails = document.getElementById(
    "singleEmployeeDetails"
  );
  const multipleEmployeesDetails = document.getElementById(
    "multipleEmployeesDetails"
  );
  const multipleCount = document.getElementById("multipleCount");
  const selectedEmployeesList = document.getElementById(
    "selectedEmployeesList"
  );
  const confirmationBox = document.getElementById("confirmationBox");
  const confirmIrreversible = document.getElementById("confirmIrreversible");
  const confirmBackup = document.getElementById("confirmBackup");
  const confirmAuthorization = document.getElementById("confirmAuthorization");
  const deletionReasonTextarea = document.getElementById("deletionReason");
  const deleteButton = document.getElementById("deleteButton");

  // Modais
  const finalConfirmModal = new bootstrap.Modal(
    document.getElementById("finalConfirmModal")
  );
  const successModal = new bootstrap.Modal(
    document.getElementById("successModal")
  );
  const finalDeleteButton = document.getElementById("finalDeleteButton");
  const finalConfirmMessage = document.getElementById("finalConfirmMessage");
  const successMessage = document.getElementById("successMessage");
  const redirectButton = document.getElementById("redirectButton");

  // Inicializar
  function init() {
    renderEmployeesList();
    updateStatistics();
    setupEventListeners();
  }

  // Renderizar lista de funcionários
  function renderEmployeesList() {
    if (filteredEmployees.length === 0) {
      employeesList.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-search"></i>
                    <h5 class="text-muted mb-2">Nenhum funcionário encontrado</h5>
                    <p class="text-muted">Tente ajustar os termos da busca</p>
                </div>
            `;
      return;
    }

    employeesList.innerHTML = filteredEmployees
      .map(
        (employee) => `
            <div class="employee-card mb-3 p-3 ${
              selectedEmployees.has(employee.id) ? "selected" : ""
            }" 
                 data-id="${employee.id}"
                 onclick="toggleEmployeeSelection(${employee.id})">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <img src="${employee.avatar}" alt="${employee.name}" 
                             class="profile-img-sm">
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 text-muted">${
                                  employee.name
                                }</h6>
                                <p class="text-muted mb-1 small">
                                    <i class="bi bi-person-badge me-1"></i>@${
                                      employee.account
                                    }
                                </p>
                            </div>
                            <div class="text-end">
                                <div class="mb-1">
                                    <span class="status-badge status-${
                                      employee.status
                                    }">
                                        ${translateStatus(employee.status)}
                                    </span>
                                </div>
                                <small class="text-muted">ID: ${
                                  employee.id
                                }</small>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap mt-2">
                            ${employee.roles
                              .map(
                                (role) => `
                                <span class="badge bg-primary badge-role">
                                    ${translateRole(role)}
                                </span>
                            `
                              )
                              .join("")}
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="bi bi-envelope me-1"></i>${
                                  employee.email
                                }
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `
      )
      .join("");
  }

  // Alternar seleção de funcionário
  window.toggleEmployeeSelection = function (employeeId) {
    if (selectedEmployees.has(employeeId)) {
      selectedEmployees.delete(employeeId);
    } else {
      selectedEmployees.add(employeeId);
    }

    updateSelection();
    renderEmployeesList();
    updateEmployeeDetails();
    updateConfirmationBox();
  };

  // Selecionar todos
  function selectAllEmployees() {
    filteredEmployees.forEach((employee) => {
      selectedEmployees.add(employee.id);
    });
    updateSelection();
    renderEmployeesList();
    updateEmployeeDetails();
    updateConfirmationBox();
  }

  // Desmarcar todos
  function deselectAllEmployees() {
    selectedEmployees.clear();
    updateSelection();
    renderEmployeesList();
    updateEmployeeDetails();
    updateConfirmationBox();
  }

  // Atualizar contadores
  function updateSelection() {
    const count = selectedEmployees.size;
    selectionCount.textContent = `${count} selecionado${
      count !== 1 ? "s" : ""
    }`;
    selectedCount.textContent = count;
  }

  // Atualizar estatísticas
  function updateStatistics() {
    totalCount.textContent = filteredEmployees.length;

    const adminEmployees = filteredEmployees.filter((emp) =>
      emp.roles.includes("admin")
    ).length;
    adminCount.textContent = adminEmployees;
  }

  // Atualizar detalhes do funcionário
  function updateEmployeeDetails() {
    if (selectedEmployees.size === 0) {
      employeeDetails.style.display = "none";
      noSelectionMessage.style.display = "block";
      confirmationBox.style.display = "none";
      return;
    }

    employeeDetails.style.display = "block";
    noSelectionMessage.style.display = "none";

    if (selectedEmployees.size === 1) {
      // Mostrar detalhes de um único funcionário
      const employeeId = Array.from(selectedEmployees)[0];
      const employee = filteredEmployees.find((emp) => emp.id === employeeId);

      singleEmployeeDetails.style.display = "block";
      multipleEmployeesDetails.style.display = "none";

      singleEmployeeDetails.innerHTML = `
                <div class="detail-row">
                    <span class="detail-label">Nome:</span>
                    <span class="detail-value">${employee.name}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">ID:</span>
                    <span class="detail-value">${employee.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Conta:</span>
                    <span class="detail-value">@${employee.account}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">E-mail:</span>
                    <span class="detail-value">${employee.email}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Departamento:</span>
                    <span class="detail-value">${employee.department}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Funções:</span>
                    <span class="detail-value">
                        ${employee.roles.map(translateRole).join(", ")}
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Último Login:</span>
                    <span class="detail-value">${employee.lastLogin}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge status-${employee.status}">
                            ${translateStatus(employee.status)}
                        </span>
                    </span>
                </div>
            `;
    } else {
      // Mostrar lista de múltiplos funcionários
      singleEmployeeDetails.style.display = "none";
      multipleEmployeesDetails.style.display = "block";

      multipleCount.textContent = selectedEmployees.size;

      const selectedNames = Array.from(selectedEmployees)
        .map((id) => {
          const emp = filteredEmployees.find((e) => e.id === id);
          return emp ? emp.name : "";
        })
        .filter((name) => name !== "");

      selectedEmployeesList.innerHTML = selectedNames
        .map((name) => `<div class="mb-1">• ${name}</div>`)
        .join("");
    }
  }

  // Atualizar caixa de confirmação
  function updateConfirmationBox() {
    if (selectedEmployees.size > 0) {
      confirmationBox.style.display = "block";
      updateDeleteButtonState();
    } else {
      confirmationBox.style.display = "none";
    }
  }

  // Atualizar estado do botão de exclusão
  function updateDeleteButtonState() {
    const isConfirmed =
      confirmIrreversible.checked &&
      confirmBackup.checked &&
      confirmAuthorization.checked;

    const hasReason = deletionReasonTextarea.value.trim().length >= 10;

    deleteButton.disabled = !(isConfirmed && hasReason);
  }

  // Filtrar funcionários
  function filterEmployees() {
    searchTerm = searchEmployeeInput.value.toLowerCase().trim();

    if (!searchTerm) {
      filteredEmployees = [...mockEmployees];
    } else {
      filteredEmployees = mockEmployees.filter(
        (employee) =>
          employee.name.toLowerCase().includes(searchTerm) ||
          employee.email.toLowerCase().includes(searchTerm) ||
          employee.account.toLowerCase().includes(searchTerm) ||
          employee.id.toString().includes(searchTerm) ||
          employee.department.toLowerCase().includes(searchTerm)
      );
    }

    // Atualizar seleções para manter apenas os que ainda estão na lista
    const newSelected = new Set();
    selectedEmployees.forEach((id) => {
      if (filteredEmployees.some((emp) => emp.id === id)) {
        newSelected.add(id);
      }
    });
    selectedEmployees = newSelected;

    renderEmployeesList();
    updateStatistics();
    updateEmployeeDetails();
    updateConfirmationBox();
  }

  // Excluir funcionários
  function deleteSelectedEmployees() {
    if (selectedEmployees.size === 0) return;

    const count = selectedEmployees.size;
    const names = Array.from(selectedEmployees)
      .map((id) => {
        const emp = filteredEmployees.find((e) => e.id === id);
        return emp ? emp.name : "";
      })
      .filter((name) => name !== "");

    // Atualizar mensagem do modal
    if (count === 1) {
      finalConfirmMessage.textContent = `Você está prestes a excluir o funcionário "${names[0]}". Esta ação não pode ser desfeita.`;
    } else {
      finalConfirmMessage.textContent = `Você está prestes a excluir ${count} funcionários. Esta ação não pode ser desfeita.`;
    }

    // Configurar ação do botão final
    finalDeleteButton.onclick = function () {
      performDeletion();
    };

    finalConfirmModal.show();
  }

  // Realizar exclusão
  function performDeletion() {
    finalConfirmModal.hide();

    // Simular exclusão (em produção seria uma chamada AJAX)
    const deletionPromises = Array.from(selectedEmployees).map((id) =>
      simulateDeletion(id, deletionReason)
    );

    Promise.all(deletionPromises)
      .then((results) => {
        const successfulDeletions = results.filter((r) => r.success).length;

        // Atualizar mensagem de sucesso
        if (successfulDeletions === 1) {
          successMessage.textContent = "Funcionário excluído com sucesso!";
        } else {
          successMessage.textContent = `${successfulDeletions} funcionários excluídos com sucesso!`;
        }

        // Remover funcionários excluídos da lista local
        selectedEmployees.forEach((id) => {
          const index = mockEmployees.findIndex((emp) => emp.id === id);
          if (index !== -1) {
            mockEmployees.splice(index, 1);
          }
        });

        // Limpar seleções
        selectedEmployees.clear();

        // Atualizar interface
        filterEmployees();
        updateSelection();

        // Mostrar modal de sucesso
        setTimeout(() => {
          successModal.show();
        }, 500);
      })
      .catch((error) => {
        console.error("Erro na exclusão:", error);
        showNotification(
          "Erro ao excluir funcionários. Tente novamente.",
          "error"
        );
      });
  }

  // Configurar event listeners
  function setupEventListeners() {
    // Busca
    searchEmployeeInput.addEventListener(
      "input",
      debounce(filterEmployees, 300)
    );

    // Botões de seleção
    selectAllBtn.addEventListener("click", selectAllEmployees);
    deselectAllBtn.addEventListener("click", deselectAllEmployees);

    // Checkboxes de confirmação
    confirmIrreversible.addEventListener("change", updateDeleteButtonState);
    confirmBackup.addEventListener("change", updateDeleteButtonState);
    confirmAuthorization.addEventListener("change", updateDeleteButtonState);

    // Motivo da exclusão
    deletionReasonTextarea.addEventListener("input", updateDeleteButtonState);

    // Botão de exclusão
    deleteButton.addEventListener("click", deleteSelectedEmployees);

    // Botão de redirecionamento
    redirectButton.addEventListener("click", function () {
      window.location.href = "all-employees.html";
    });
  }

  // Funções auxiliares
  function translateRole(role) {
    const roles = {
      admin: "Administrador",
      distributor: "Distribuidor",
      analyst: "Analista",
      financial: "Financeiro",
    };
    return roles[role] || role;
  }

  function translateStatus(status) {
    const statuses = {
      active: "Ativo",
      suspended: "Suspenso",
      review: "Revisão",
    };
    return statuses[status] || status;
  }

  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  function showNotification(message, type = "info") {
    // Criar elemento de notificação
    const notification = document.createElement("div");
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
    notification.innerHTML = `
            <strong>${
              type === "success"
                ? "✓ Sucesso!"
                : type === "error"
                ? "✗ Erro!"
                : "⚠ Atenção!"
            }</strong>
            <span class="ms-1">${message}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

    // Adicionar ao documento
    document.body.appendChild(notification);

    // Remover automaticamente após 5 segundos
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 5000);
  }

  function simulateDeletion(employeeId, reason) {
    return new Promise((resolve) => {
      setTimeout(() => {
        console.log(`Excluindo funcionário ${employeeId}. Motivo: ${reason}`);
        // Simular log de auditoria
        logAudit(employeeId, reason);
        resolve({ success: true, id: employeeId });
      }, 1000);
    });
  }

  function logAudit(employeeId, reason) {
    const auditLog = {
      timestamp: new Date().toISOString(),
      action: "DELETE_EMPLOYEE",
      employeeId: employeeId,
      reason: reason,
      performedBy: "admin@wasomupfy.com",
      ipAddress: "192.168.1.1",
    };
    console.log("Audit Log:", auditLog);
    // Em produção, enviaria para o servidor
  }

  // Estilos CSS dinâmicos
  const style = document.createElement("style");
  style.textContent = `
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-suspended { background-color: #fee2e2; color: #b91c1c; }
        .status-review { background-color: #fef3c7; color: #92400e; }
        .badge-role { font-size: 0.7rem; margin-right: 4px; margin-bottom: 4px; }
        .employee-card.selected { border-color: #dc3545; background-color: #fff5f5; }
    `;
  document.head.appendChild(style);

  // Inicializar
  init();
});
