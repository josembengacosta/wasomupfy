// Mapeamento de planos para classes e textos
const planInfo = {
  single: { class: "bg-info", text: "single", price: 1.15 },
  album: { class: "bg-primary", text: "Álbum", price: 4.5 },
  artist: { class: "bg-warning text-dark", text: "Artista", price: 10.0 },
  label: { class: "bg-success", text: "Label", price: 50.0 },
};

// Mapeamento de métodos de pagamento
const methodInfo = {
  pix: { icon: "bi-qr-code", text: "PIX", type: "digital" },
  credit_card: {
    icon: "bi-credit-card",
    text: "Cartão de Crédito",
    type: "card",
  },
  bank_transfer: {
    icon: "bi-bank",
    text: "Transferência Bancária",
    type: "transfer",
  },
  iban: { icon: "bi-bank2", text: "IBAN", type: "bank" },
  express: { icon: "bi-lightning-charge", text: "Express", type: "fast" },
  mpesa: { icon: "bi-phone", text: "MPesa", type: "mobile" },
};

// Formatar moeda em KZ
function formatKZ(amount) {
  return new Intl.NumberFormat("pt-AO", {
    style: "currency",
    currency: "AOA",
    minimumFractionDigits: 2,
  })
    .format(amount / 100)
    .replace("AOA", "KZ");
}

// Formatar data no padrão de Angola
function formatDateAngola(dateString) {
  const date = new Date(dateString);
  return date.toLocaleString("pt-AO", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    timeZone: "Africa/Luanda",
  });
}

// Gerar número de recibo
function generateReceiptNumber(paymentId) {
  const now = new Date();
  const year = now.getFullYear();
  const seq = String(paymentId).padStart(4, "0");
  return `UPFY-${year}${seq}`;
}

$(document).ready(function () {
  let paymentsTable;
  let currentDeletePayment = null;

  // Inicializar DataTable
  function initDataTable() {
    if ($.fn.DataTable.isDataTable("#payments-table")) {
      paymentsTable.destroy();
    }

    paymentsTable = $("#payments-table").DataTable({
      data: samplePayments,
      columns: [
        { data: "id" },
        {
          data: "user",
          render: function (data) {
            return `
                            <div class="d-flex align-items-center">
                                <img src="${data.avatar}" alt="${data.name}" class="user-avatar me-2">
                                <div>
                                    <div>${data.name}</div>
                                    <small class="text-muted">${data.email}</small>
                                </div>
                            </div>
                        `;
          },
        },
        {
          data: "plan",
          render: function (data) {
            const info = planInfo[data];
            return `<span class="badge ${info.class} badge-plan">${info.text}</span>`;
          },
        },
        {
          data: "amount",
          render: function (data) {
            return formatKZ(data);
          },
        },
        {
          data: "method",
          render: function (data) {
            const info = methodInfo[data] || {
              icon: "bi-question-circle",
              text: data,
            };
            return `<i class="bi ${info.icon} me-1"></i> ${info.text}`;
          },
        },
        {
          data: "status",
          render: function (data) {
            let statusClass, statusText;
            switch (data) {
              case "paid":
                statusClass = "status-paid";
                statusText = "Pago";
                break;
              case "pending":
                statusClass = "status-pending";
                statusText = "Em Análise";
                break;
              case "approved":
                statusClass = "status-approved";
                statusText = "Aprovado";
                break;
              case "rejected":
                statusClass = "status-rejected";
                statusText = "Recusado";
                break;
              default:
                statusClass = "status-pending";
                statusText = data;
            }
            return `<span class="payment-status ${statusClass}">${statusText}</span>`;
          },
        },
        {
          data: "date",
          render: function (data) {
            return formatDateAngola(data);
          },
        },
        {
          data: "id",
          render: function (data, type, row) {
            return `
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary view-payment-btn" data-id="${data}" title="Visualizar">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-outline-warning edit-payment-btn" data-id="${data}" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger delete-payment-btn" data-id="${data}" title="Excluir">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
          },
        },
      ],
      language: {
        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json",
      },
      responsive: true,
      pageLength: 10,
      dom: '<"top"lf>rt<"bottom"ip><"clear">',
      initComplete: function () {
        // Atualizar contagem inicial
        updateResultsCount();
      },
    });

    // Configurar filtros em tempo real
    setupRealTimeFilters();
  }

  // Configurar filtros em tempo real
  function setupRealTimeFilters() {
    // IDs dos filtros
    const filterIds = [
      "filter-payment-id",
      "filter-user-email",
      "filter-user-name",
      "filter-payment-plan",
      "filter-payment-method",
      "filter-payment-status",
      "date-from",
      "date-to",
      "amount-min",
      "amount-max",
    ];

    // Aplicar filtro para cada campo
    filterIds.forEach((filterId) => {
      $(`#${filterId}`).on("keyup change", function () {
        applyFilters();
      });
    });
  }

  // Aplicar todos os filtros
  function applyFilters() {
    paymentsTable.search("").columns().search("").draw();

    // ID do pagamento
    const paymentId = $("#filter-payment-id").val();
    if (paymentId) {
      paymentsTable.columns(0).search(paymentId, true, false, true);
    }

    // E-mail do usuário
    const userEmail = $("#filter-user-email").val();
    if (userEmail) {
      paymentsTable.columns(1).search(userEmail);
    }

    // Nome do usuário
    const userName = $("#filter-user-name").val();
    if (userName) {
      paymentsTable.columns(1).search(userName);
    }

    // Plano
    const plan = $("#filter-payment-plan").val();
    if (plan) {
      paymentsTable.columns(2).search(plan);
    }

    // Método de pagamento
    const method = $("#filter-payment-method").val();
    if (method) {
      paymentsTable.columns(4).search(method);
    }

    // Status
    const status = $("#filter-payment-status").val();
    if (status) {
      paymentsTable.columns(5).search(status);
    }

    // Data de início
    const dateFrom = $("#date-from").val();
    // Data de fim
    const dateTo = $("#date-to").val();

    if (dateFrom || dateTo) {
      paymentsTable.draw();
      // Filtrar por data após o draw
      $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const paymentDate = new Date(samplePayments[dataIndex].date);
        const fromDate = dateFrom ? new Date(dateFrom + "T00:00:00") : null;
        const toDate = dateTo ? new Date(dateTo + "T23:59:59") : null;

        if (fromDate && toDate) {
          return paymentDate >= fromDate && paymentDate <= toDate;
        } else if (fromDate) {
          return paymentDate >= fromDate;
        } else if (toDate) {
          return paymentDate <= toDate;
        }
        return true;
      });
    }

    // Valor mínimo
    const amountMin = $("#amount-min").val();
    // Valor máximo
    const amountMax = $("#amount-max").val();

    if (amountMin || amountMax) {
      paymentsTable.draw();
      // Filtrar por valor após o draw
      $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const amount = samplePayments[dataIndex].amount / 100; // Converter para KZ
        const min = amountMin ? parseFloat(amountMin) : null;
        const max = amountMax ? parseFloat(amountMax) : null;

        if (min && max) {
          return amount >= min && amount <= max;
        } else if (min) {
          return amount >= min;
        } else if (max) {
          return amount <= max;
        }
        return true;
      });
    }

    // Aplicar filtros e atualizar contagem
    paymentsTable.draw();
    updateResultsCount();

    // Limpar filtros personalizados após o draw
    $.fn.dataTable.ext.search = [];
  }

  // Atualizar contagem de resultados
  function updateResultsCount() {
    const filteredCount = paymentsTable.rows({ search: "applied" }).count();
    const totalCount = paymentsTable.rows().count();

    if (filteredCount === totalCount) {
      $("#payment-results-count").text(`${totalCount} resultados`);
    } else {
      $("#payment-results-count").text(
        `${filteredCount} de ${totalCount} resultados`
      );
    }
  }

  initDataTable();

  // Limpar filtros
  $("#clear-payment-filters").click(function () {
    $("#filter-payment-id").val("");
    $("#filter-user-email").val("");
    $("#filter-user-name").val("");
    $("#filter-payment-plan").val("");
    $("#filter-payment-method").val("");
    $("#filter-payment-status").val("");
    $("#date-from").val("");
    $("#date-to").val("");
    $("#amount-min").val("");
    $("#amount-max").val("");

    // Resetar filtros da DataTable
    paymentsTable.search("").columns().search("").draw();
    updateResultsCount();
  });

  // Visualizar pagamento
  $(document).on("click", ".view-payment-btn", function () {
    const paymentId = $(this).data("id");
    const payment = samplePayments.find((p) => p.id === paymentId);

    if (payment) {
      const user = payment.user;
      const formattedDate = formatDateAngola(payment.date);
      const formattedAmount = formatKZ(payment.amount);

      let statusClass, statusText;
      switch (payment.status) {
        case "paid":
          statusClass = "status-paid";
          statusText = "Pago";
          break;
        case "pending":
          statusClass = "status-pending";
          statusText = "Em Análise";
          break;
        case "approved":
          statusClass = "status-approved";
          statusText = "Aprovado";
          break;
        case "rejected":
          statusClass = "status-rejected";
          statusText = "Recusado";
          break;
        default:
          statusClass = "status-pending";
          statusText = payment.status;
      }

      const methodInfoData = methodInfo[payment.method] || {
        icon: "bi-question-circle",
        text: payment.method,
      };

      let attachmentHtml = "";
      if (payment.attachment) {
        const ext = payment.attachment.split(".").pop().toLowerCase();
        const isImage = ["jpg", "jpeg", "png", "gif"].includes(ext);

        attachmentHtml = `
                    <div class="mt-3">
                        <h6><i class="bi bi-paperclip me-2"></i> Comprovante Anexado</h6>
                        ${
                          isImage
                            ? `<img src="https://via.placeholder.com/300x200?text=Comprovante" alt="Comprovante" class="img-thumbnail" style="max-height: 200px;">`
                            : `<a href="#" class="btn btn-outline-primary">
                                <i class="bi bi-file-earmark-pdf me-1"></i> ${payment.attachment}
                            </a>`
                        }
                        <button class="btn btn-sm btn-success mt-2" id="download-attachment-btn">
                            <i class="bi bi-download me-1"></i> Baixar Comprovante
                        </button>
                    </div>
                `;
      }

      // Informações específicas do método
      let methodDetails = "";
      if (payment.method === "iban" && payment.ibanNumber) {
        methodDetails = `
                    <tr>
                        <th>Número IBAN</th>
                        <td><code>${payment.ibanNumber}</code></td>
                    </tr>
                `;
      }

      $("#view-payment-body").html(`
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <img src="${user.avatar}" alt="${
        user.name
      }" class="user-avatar mb-3" style="width: 100px; height: 100px;">
                                <h5>${user.name}</h5>
                                <p class="text-muted">
                                    <i class="bi bi-geo-alt"></i> ${
                                      user.country === "AO"
                                        ? "Angola"
                                        : user.country === "PT"
                                        ? "Portugal"
                                        : user.country === "BR"
                                        ? "Brasil"
                                        : "Moçambique"
                                    }
                                </p>
                                
                                <div class="d-flex justify-content-center mt-3">
                                    <a href="mailto:${
                                      user.email
                                    }" class="btn btn-sm btn-outline-primary me-2" title="Enviar Email">
                                        <i class="bi bi-envelope"></i>
                                    </a>
                                    <a href="https://wa.me/${user.phone.replace(
                                      /[^\d]/g,
                                      ""
                                    )}" class="btn btn-sm btn-outline-success" target="_blank" title="WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <h6><i class="bi bi-credit-card me-2"></i> Informações do Pagamento</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th width="30%">ID Pagamento</th>
                                    <td><strong>PAY-${payment.id}</strong></td>
                                </tr>
                                <tr>
                                    <th>Plano</th>
                                    <td><span class="badge ${
                                      planInfo[payment.plan].class
                                    }">${
        planInfo[payment.plan].text
      }</span></td>
                                </tr>
                                <tr>
                                    <th>Valor</th>
                                    <td><strong class="text-primary">${formattedAmount}</strong></td>
                                </tr>
                                <tr>
                                    <th>Método</th>
                                    <td><i class="bi ${
                                      methodInfoData.icon
                                    }"></i> ${methodInfoData.text}</td>
                                </tr>
                                ${methodDetails}
                                <tr>
                                    <th>Referência</th>
                                    <td><code>${
                                      payment.reference || "N/A"
                                    }</code></td>
                                </tr>
                                <tr>
                                    <th>Data/Hora</th>
                                    <td>${formattedDate}</td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td><span class="payment-status ${statusClass}">${statusText}</span></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="bi bi-chat-left-text me-2"></i> Observações</h6>
                            <div class="alert alert-light">
                                ${
                                  payment.notes ||
                                  "Nenhuma observação registrada."
                                }
                            </div>
                        </div>
                        
                        ${attachmentHtml}
                    </div>
                </div>
            `);

      // Adicionar evento para download do comprovante
      if (payment.attachment) {
        $("#download-attachment-btn").click(function () {
          alert(
            `Baixando comprovante: ${payment.attachment}\n\n(Em uma implementação real, isso baixaria o arquivo)`
          );
        });
      }

      // Armazenar ID do pagamento para geração de recibo
      $("#viewPaymentModal").data("current-payment-id", paymentId);
      $("#viewPaymentModal").modal("show");
    }
  });

  // Gerar recibo
  $("#generate-receipt-btn").click(function () {
    const paymentId = $("#viewPaymentModal").data("current-payment-id");
    const payment = samplePayments.find((p) => p.id === paymentId);

    if (payment) {
      const user = payment.user;
      const formattedDate = formatDateAngola(payment.date);
      const formattedAmount = formatKZ(payment.amount);

      let statusText, statusClass;
      switch (payment.status) {
        case "paid":
          statusText = "Pago";
          statusClass = "status-paid";
          break;
        case "pending":
          statusText = "Em Análise";
          statusClass = "status-pending";
          break;
        case "approved":
          statusText = "Aprovado";
          statusClass = "status-approved";
          break;
        case "rejected":
          statusText = "Recusado";
          statusClass = "status-rejected";
          break;
        default:
          statusText = payment.status;
          statusClass = "status-pending";
      }

      const methodInfoData = methodInfo[payment.method] || {
        text: payment.method,
      };

      let countryName;
      switch (user.country) {
        case "AO":
          countryName = "Angola";
          break;
        case "PT":
          countryName = "Portugal";
          break;
        case "BR":
          countryName = "Brasil";
          break;
        case "MZ":
          countryName = "Moçambique";
          break;
        default:
          countryName = user.country;
      }

      // Atualizar conteúdo do recibo
      $("#receipt-number").text(generateReceiptNumber(payment.id));
      $("#receipt-client-name").text(user.name);
      $("#receipt-client-email").text(user.email);
      $("#receipt-client-phone").text(user.phone);
      $("#receipt-client-country").text(countryName);
      $("#receipt-date").text(formattedDate);
      $("#receipt-payment-id").text(`PAY-${payment.id}`);
      $("#receipt-method").text(methodInfoData.text);
      $("#receipt-status")
        .text(statusText)
        .removeClass()
        .addClass(`payment-status ${statusClass}`);
      $("#receipt-plan").text(planInfo[payment.plan].text);
      $("#receipt-amount").text(formattedAmount);
      $("#receipt-total").text(formattedAmount);

      $("#viewPaymentModal").modal("hide");
      $("#receiptModal").modal("show");
    }
  });

  // Imprimir recibo
  $("#print-receipt-btn").click(function () {
    const receiptContent = document
      .getElementById("receipt-content")
      .cloneNode(true);

    // Remover estilos de borda para impressão
    receiptContent.querySelector(".receipt-preview").style.border = "none";
    receiptContent.querySelector(".receipt-preview").style.padding = "0";

    // Criar janela de impressão
    const printWindow = window.open("", "_blank");
    printWindow.document.write(`
            <html>
                <head>
                    <title>Recibo de Pagamento - WASOM UPFY</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .table { width: 100%; border-collapse: collapse; }
                        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
                        .text-center { text-align: center; }
                        .text-end { text-align: right; }
                        .payment-status { 
                            padding: 3px 8px; 
                            border-radius: 20px; 
                            font-weight: bold; 
                            display: inline-block;
                        }
                        .status-paid { background-color: #d1fae5; color: #065f46; }
                        .receipt-logo { max-width: 150px; margin-bottom: 20px; }
                        @media print {
                            @page { margin: 10mm; }
                            body { margin: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${receiptContent.innerHTML}
                    <script>
                        setTimeout(() => {
                            window.print();
                            setTimeout(() => window.close(), 500);
                        }, 250);
                    </script>
                </body>
            </html>
        `);
    printWindow.document.close();
  });

  // Baixar recibo como PDF
  $("#download-receipt-btn").click(function () {
    const element = document.getElementById("receipt-content");
    const opt = {
      margin: [10, 10, 10, 10],
      filename: `recibo_wasomupfy_${$("#receipt-number").text()}.pdf`,
      image: { type: "jpeg", quality: 1 },
      html2canvas: {
        scale: 2,
        useCORS: true,
        backgroundColor: "#ffffff",
        windowWidth: 1200,
      },
      jsPDF: {
        unit: "mm",
        format: "a4",
        orientation: "portrait",
      },
    };

    // Clonar elemento para evitar problemas
    const elementClone = element.cloneNode(true);
    elementClone.style.width = "100%";
    elementClone.style.maxWidth = "100%";

    // Criar container temporário
    const container = document.createElement("div");
    container.style.position = "absolute";
    container.style.left = "-9999px";
    container.appendChild(elementClone);
    document.body.appendChild(container);

    html2pdf()
      .set(opt)
      .from(elementClone)
      .save()
      .finally(() => {
        document.body.removeChild(container);
      });
  });

  // Excluir pagamento com previsualização
  $(document).on("click", ".delete-payment-btn", function () {
    const paymentId = $(this).data("id");
    const payment = samplePayments.find((p) => p.id === paymentId);

    if (payment) {
      currentDeletePayment = payment;

      // Preencher modal de exclusão com informações
      $("#delete-payment-details").html(`
                <div class="alert alert-danger">
                    <h6><i class="bi bi-exclamation-triangle-fill me-2"></i>ATENÇÃO: Esta ação é irreversível!</h6>
                    <p class="mb-0">Você está prestes a excluir permanentemente o pagamento abaixo:</p>
                </div>
                
                <div class="card border-danger">
                    <div class="card-body">
                        <h6>Detalhes do Pagamento a ser Excluído:</h6>
                        <table class="table table-sm">
                            <tr>
                                <th>ID:</th>
                                <td><strong>PAY-${payment.id}</strong></td>
                            </tr>
                            <tr>
                                <th>Cliente:</th>
                                <td>${payment.user.name}</td>
                            </tr>
                            <tr>
                                <th>Plano:</th>
                                <td><span class="badge ${
                                  planInfo[payment.plan].class
                                }">${planInfo[payment.plan].text}</span></td>
                            </tr>
                            <tr>
                                <th>Valor:</th>
                                <td><strong>${formatKZ(
                                  payment.amount
                                )}</strong></td>
                            </tr>
                            <tr>
                                <th>Data:</th>
                                <td>${formatDateAngola(payment.date)}</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="mt-3">
                    <p><strong>Motivo da exclusão (opcional):</strong></p>
                    <textarea class="form-control" id="delete-reason" rows="2" placeholder="Descreva o motivo da exclusão..."></textarea>
                </div>
            `);

      $("#deletePaymentModal").modal("show");
    }
  });

  // Confirmar exclusão com senha
  $("#delete-payment-form").submit(function (e) {
    e.preventDefault();

    const password = $("#admin-password").val();
    const reason = $("#delete-reason").val();

    // Verificação de senha (em produção, seria uma chamada API)
    if (password === "admin123") {
      if (currentDeletePayment) {
        // Remover do array
        const index = samplePayments.findIndex(
          (p) => p.id === currentDeletePayment.id
        );
        if (index !== -1) {
          samplePayments.splice(index, 1);

          // Registrar log da exclusão
          console.log(`Pagamento PAY-${currentDeletePayment.id} excluído.`, {
            motivo: reason || "Sem motivo informado",
            data: new Date().toISOString(),
            usuario: "Administrador",
          });

          // Recarregar DataTable
          initDataTable();

          // Mostrar confirmação
          $("#deletePaymentModal").modal("hide");
          $("#admin-password").val("");
          $("#delete-reason").val("");

          // Toast de sucesso
          showToast(
            "success",
            "Pagamento excluído com sucesso!",
            `O pagamento PAY-${currentDeletePayment.id} foi removido permanentemente.`
          );
        }
      }
    } else {
      showToast(
        "error",
        "Senha incorreta!",
        "A senha de administrador está incorreta."
      );
    }
  });

  // Editar pagamento
  $(document).on("click", ".edit-payment-btn", function () {
    const paymentId = $(this).data("id");
    const payment = samplePayments.find((p) => p.id === paymentId);

    if (payment) {
      const user = payment.user;

      // Preencher formulário
      $("#payment-user-email").val(user.email);
      $("#payment-user-name").val(user.name);
      $("#payment-user-phone").val(user.phone);
      $("#payment-user-country").val(user.country);
      $("#payment-plan").val(payment.plan);
      $("#payment-amount").val((payment.amount / 100).toFixed(2));
      $("#payment-method").val(payment.method);
      $("#payment-status").val(payment.status);
      $("#payment-date").val(payment.date.substring(0, 16));
      $("#payment-reference").val(payment.reference);

      // Se tiver IBAN, adicionar campo
      if (payment.method === "iban" && payment.ibanNumber) {
        let ibanField = $("#payment-iban-field");
        if (ibanField.length === 0) {
          $("#payment-method").after(`
                        <div class="col-md-6" id="payment-iban-field">
                            <label for="payment-iban" class="form-label">Número IBAN*</label>
                            <input type="text" class="form-control" id="payment-iban" placeholder="Ex: AO0600410001234567891" required>
                        </div>
                    `);
        }
        $("#payment-iban").val(payment.ibanNumber);
      } else {
        $("#payment-iban-field").remove();
      }

      // Configurar para edição
      $("#create-payment-form").data("editing-id", paymentId);
      $("#addPaymentModal .modal-title").html(
        '<i class="bi bi-pencil-square me-2"></i>Editar Pagamento'
      );
      $('#addPaymentModal .modal-footer button[type="submit"]').html(
        '<i class="bi bi-save me-2"></i>Salvar Alterações'
      );
      $("#addPaymentModal").modal("show");
    }
  });

  // Criar/Editar pagamento
  $("#create-payment-form").submit(function (e) {
    e.preventDefault();

    // Obter valores
    const email = $("#payment-user-email").val();
    const name = $("#payment-user-name").val();
    const phone = $("#payment-user-phone").val();
    const country = $("#payment-user-country").val();
    const plan = $("#payment-plan").val();
    const amount = parseFloat($("#payment-amount").val()) * 100; // Converter para centavos
    const method = $("#payment-method").val();
    const status = $("#payment-status").val();
    const date = $("#payment-date").val();
    const reference = $("#payment-reference").val();
    const iban = method === "iban" ? $("#payment-iban").val() : null;

    // Validações
    if (
      !email ||
      !name ||
      !phone ||
      !country ||
      !plan ||
      isNaN(amount) ||
      !method ||
      !status ||
      !date
    ) {
      showToast(
        "error",
        "Campos obrigatórios!",
        "Por favor, preencha todos os campos obrigatórios."
      );
      return;
    }

    if (method === "iban" && (!iban || iban.length < 5)) {
      showToast(
        "error",
        "IBAN inválido!",
        "Por favor, informe um número IBAN válido."
      );
      return;
    }

    const editingId = $(this).data("editing-id");

    if (editingId) {
      // Editar pagamento existente
      const paymentIndex = samplePayments.findIndex((p) => p.id === editingId);
      if (paymentIndex !== -1) {
        samplePayments[paymentIndex] = {
          ...samplePayments[paymentIndex],
          user: {
            ...samplePayments[paymentIndex].user,
            email,
            name,
            phone,
            country,
          },
          plan,
          amount,
          method,
          status,
          date: new Date(date).toISOString(),
          reference,
          ibanNumber: iban,
        };

        // Atualizar DataTable
        paymentsTable
          .row(paymentIndex)
          .data(samplePayments[paymentIndex])
          .draw();

        showToast(
          "success",
          "Pagamento atualizado!",
          "As alterações foram salvas com sucesso."
        );
      }
    } else {
      // Criar novo pagamento
      const newPayment = {
        id:
          samplePayments.length > 0
            ? Math.max(...samplePayments.map((p) => p.id)) + 1
            : 1001,
        userId:
          samplePayments.length > 0
            ? Math.max(...samplePayments.map((p) => p.userId)) + 1
            : 501,
        user: {
          name,
          email,
          phone,
          country,
          avatar: `https://randomuser.me/api/portraits/${
            Math.random() > 0.5 ? "men" : "women"
          }/${Math.floor(Math.random() * 99)}.jpg`,
        },
        plan,
        amount,
        currency: "KZ",
        method,
        status,
        date: new Date(date).toISOString(),
        reference,
        ibanNumber: iban,
        attachment: null,
        notes: "",
      };

      samplePayments.push(newPayment);
      paymentsTable.row.add(newPayment).draw();

      showToast(
        "success",
        "Pagamento criado!",
        "O novo pagamento foi registrado com sucesso."
      );
    }

    // Fechar e limpar
    $("#addPaymentModal").modal("hide");
    this.reset();
    $(this).removeData("editing-id");
    $("#payment-iban-field").remove();
    $("#addPaymentModal .modal-title").html(
      '<i class="bi bi-plus-circle me-2"></i>Registrar Novo Pagamento'
    );
    $('#addPaymentModal .modal-footer button[type="submit"]').html(
      '<i class="bi bi-save me-2"></i>Registrar Pagamento'
    );
  });

  // Mostrar/ocultar campo IBAN
  $("#payment-method").change(function () {
    const method = $(this).val();
    $("#payment-iban-field").remove();

    if (method === "iban") {
      $(this).closest(".row").append(`
                <div class="col-md-6" id="payment-iban-field">
                    <label for="payment-iban" class="form-label">Número IBAN*</label>
                    <input type="text" class="form-control" id="payment-iban" placeholder="Ex: AO0600410001234567891" required>
                </div>
            `);
    }
  });

  // Preencher valor do plano
  $("#payment-plan").change(function () {
    const plan = $(this).val();
    const info = planInfo[plan];

    if (info && info.price) {
      $("#payment-amount").val((info.price / 100).toFixed(2));
    }
  });

  // Função para mostrar toast
  function showToast(type, title, message) {
    // Criar toast dinâmico
    const toastId = "toast-" + Date.now();
    const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

    // Adicionar ao container
    const toastContainer = $(".toast-container");
    if (toastContainer.length === 0) {
      $("body").append(
        '<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>'
      );
    }

    $(".toast-container").append(toastHtml);

    // Mostrar toast
    const toast = new bootstrap.Toast(document.getElementById(toastId), {
      delay: 5000,
    });
    toast.show();

    // Remover após ocultar
    $(`#${toastId}`).on("hidden.bs.toast", function () {
      $(this).remove();
    });
  }

  // Toggle para mostrar/ocultar senha no modal de exclusão
  $(document).on("click", "#toggle-password", function () {
    const passwordInput = $("#admin-password");
    const type =
      passwordInput.attr("type") === "password" ? "text" : "password";
    passwordInput.attr("type", type);
    $(this).find("i").toggleClass("bi-eye bi-eye-slash");
  });
});
