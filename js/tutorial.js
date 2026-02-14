document.addEventListener("DOMContentLoaded", function () {
  // Base de dados completa com todas as informações dos tutoriais
  const tutoriaisInfo = {
    "Criar sua conta": {
      titulo: "Guia Completo: Criar sua conta na Wasom Upfy",
      icone: "fa-user-plus",
      descricao:
        "Aprenda passo a passo como configurar seu perfil de artista ou selo e verificar seus dados de pagamento.",
      passos: [
        'Acesse wasomupfy.com e clique em "Criar conta"',
        "Preencha seu e-mail e crie uma senha forte",
        "Escolha seu tipo de perfil: Artista Independente ou Selo/Label",
        "Complete seu perfil com nome artístico, biografia e foto",
        "Configure seus dados bancários para recebimento de royalties",
        "Verifique seu e-mail através do link enviado",
        "Faça upload do seu documento de identificação (RG/CNH/Passaporte)",
      ],
      dicas: [
        "Use um e-mail profissional ou dedicado para sua carreira musical",
        "Mantenha seus dados sempre atualizados para evitar atrasos nos pagamentos",
        "Verifique se o nome artístico não possui restrições de direitos autorais",
      ],
      requisitos: [
        "Conta de e-mail válida",
        "Documento de identificação oficial",
        "Conta bancária para recebimento dos royalties",
        "Maior de 18 anos ou autorização dos responsáveis",
      ],
      linkGuia: "../../authentic/register.html",
      tempoLeitura: "5 minutos",
      nivel: "Iniciante",
      videoUrl: "https://www.youtube.com/watch?v=SEU_VIDEO_CADASTRO",
    },

    "Formatos de Áudio": {
      titulo: "Formatos de Áudio: Requisitos Técnicos e Melhores Práticas",
      icone: "fa-cloud-arrow-up",
      descricao:
        "Entenda a diferença entre Single, EP e Álbum e conheça os requisitos técnicos para upload.",
      passos: [
        "Single: 1 a 3 faixas, ideal para lançamentos frequentes",
        "EP: 4 a 6 faixas, considerado um lançamento intermediário",
        "Álbum: 7+ faixas, trabalho completo e conceitual",
        "Formato obrigatório: WAV, 44.1kHz, 16bit ou 24bit",
        "Arquivos MP3 não são aceitos nas lojas digitais",
        "Nomeie seus arquivos corretamente: Artista_NomeDaMusica.wav",
      ],
      dicas: [
        "Evite compressão excessiva no mastering",
        "Deixe -1dB de headroom para evitar distorção",
        "Faça testes em diferentes dispositivos antes do upload",
        "Mantenha consistência de volume entre as faixas",
      ],
      especificacoes: {
        Formato: "WAV (recomendado) ou FLAC",
        "Taxa de Amostragem": "44.1 kHz",
        "Profundidade de Bits": "16 ou 24 bits",
        Canais: "Stereo ou Mono",
        "Tamanho máximo": "1GB por faixa",
      },
      linkGuia: "#",
      tempoLeitura: "8 minutos",
      nivel: "Intermediário",
    },

    "Guia de Capas": {
      titulo: "Guia Definitivo para Capas de Álbum",
      icone: "fa-image",
      descricao:
        "Evite rejeições: saiba as dimensões exatas, resolução e o que não pode conter na sua capa.",
      passos: [
        "Dimensão mínima: 1600 x 1600 pixels",
        "Dimensão ideal: 3000 x 3000 pixels",
        "Formato: JPG ou PNG",
        "Resolução: 300 DPI",
        "Espaço para texto: 5% de margem de segurança",
        "Cores: Perfil RGB, sem CMYK",
      ],
      proibicoes: [
        "Logotipos de marcas não autorizadas",
        "Imagens de baixa qualidade ou pixeladas",
        "Códigos de barras ou QR codes",
        "Números de telefone ou e-mails",
        "Conteúdo explícito sem censura adequada",
        "Imagens com direitos autorais de terceiros",
      ],
      dicas: [
        "Use contraste para destacar o título",
        "Teste a capa em tamanho reduzido (thumbnail)",
        "Mantenha identidade visual consistente",
        "Considere contratar um designer profissional",
      ],
      linkGuia: "#",
      tempoLeitura: "6 minutos",
      nivel: "Iniciante",
    },

    "ISRC e Metadados": {
      titulo: "ISRC e Metadados: Identificação e Créditos",
      icone: "fa-list-check",
      descricao:
        "Como preencher corretamente compositores, produtores e gerar seus códigos ISRC.",
      passos: [
        "ISRC = Código único para cada faixa (gerado automaticamente)",
        "Estrutura: BR-WAS-25-00001",
        "Informe todos os compositores e suas respectivas porcentagens",
        "Créditos de produção, mixagem e masterização",
        "Artista principal e artistas convidados (feat.)",
        "Gênero musical principal e secundário",
        "Idioma da música e letra explícita ou não",
      ],
      dicas: [
        "Documente todos os envolvidos antes do lançamento",
        "Use o mesmo nome artístico em todas as plataformas",
        "Verifique se não há conflito de nomes com outros artistas",
        "Mantenha um registro dos seus códigos ISRC",
      ],
      definicoes: {
        ISRC: "International Standard Recording Code",
        UPC: "Universal Product Code (para álbuns)",
        ISWC: "Código para obras musicais",
        IPI: "Código do compositor na SOCIEDADE",
      },
      linkGuia: "#",
      tempoLeitura: "10 minutos",
      nivel: "Avançado",
    },

    "Ganhos e Royalties": {
      titulo: "Ganhos e Royalties: Como Funciona a Monetização",
      icone: "fa-money-bill-trend-up",
      descricao:
        "Saiba como funcionam os relatórios de vendas e como solicitar o levantamento dos seus lucros.",
      passos: [
        "Streaming: pagamento por play (aproximadamente $0.003 - $0.005)",
        "Downloads permanentes: valor fixo por venda",
        "Relatórios mensais disponíveis no dia 15",
        "Período de apuração: 60 dias para pagamento",
        "Valor mínimo para saque: R$50,00",
        "Formas de pagamento: TED, PIX, PayPal",
        "Impostos: retenção na fonte conforme legislação",
      ],
      dicas: [
        "Acompanhe seus relatórios semanalmente",
        "Divulgue nas redes sociais quando lançar nova música",
        "Crie um catálogo consistente para renda recorrente",
        "Participe de playlists para aumentar streams",
      ],
      faq: [
        "P: Quando recebo meu primeiro pagamento?",
        "R: Após 60 dias do fechamento do mês de streams.",
        "P: Como aumento meus ganhos?",
        "R: Lançando regularmente e divulgando seu trabalho.",
        "P: Posso receber em dólar?",
        "R: Sim, através de conta internacional ou Paypal.",
      ],
      linkGuia: "#",
      tempoLeitura: "7 minutos",
      nivel: "Intermediário",
    },

    "Pitching & Marketing": {
      titulo: "Pitching & Marketing: Como Ser Descoberto",
      icone: "fa-bullhorn",
      descricao:
        "Dicas para enviar sua música para playlists editoriais e estratégias de marketing digital.",
      passos: [
        "Envie seu pitch com pelo menos 2 semanas de antecedência",
        "Selecione até 2 gêneros musicais principais",
        "Descreva sua música em até 500 caracteres",
        "Compare com artistas similares (3-5 referências)",
        "Destaque novidades: clipe, turnê, feat. especial",
        "Inclua informações sobre sua base de fãs",
        "Mencione prêmios, festivais ou destaques anteriores",
      ],
      dicas: [
        "Construa sua presença nas redes sociais",
        "Crie conteúdo regular para o Instagram/TikTok",
        "Use links de pré-save antes do lançamento",
        "Colabore com outros artistas do mesmo gênero",
        "Invista em anúncios segmentados",
      ],
      estrategias: [
        "Release para blogs e influenciadores",
        "Playlists colaborativas",
        "Cross-promotion com outros artistas",
        "Conteúdo dos bastidores",
        "Lançamento de lyric video",
      ],
      linkGuia: "#",
      tempoLeitura: "12 minutos",
      nivel: "Avançado",
    },
  };

  // Função para gerar HTML do modal baseado no tutorial
  // Função para gerar HTML do modal baseado no tutorial - VERSÃO CORRIGIDA
function gerarConteudoModal(tutorialKey) {
    const info = tutoriaisInfo[tutorialKey];
    if (!info) return '<p>Conteúdo não encontrado.</p>';

    let html = `
        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-shape icon-md bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-circle me-3">
                    <i class="fa-solid ${info.icone} fs-4"></i>
                </div>
                <div>
                    <span class="badge bg-${info.nivel === 'Iniciante' ? 'success' : info.nivel === 'Intermediário' ? 'warning' : 'danger'} me-2">${info.nivel}</span>
                    <span class="text-muted"><i class="fa-regular fa-clock me-1"></i>${info.tempoLeitura} de leitura</span>
                </div>
            </div>
            
            <p class="lead mb-4">${info.descricao}</p>
            
            <div class="accordion" id="accordionTutorial_${tutorialKey.replace(/\s+/g, '')}">
                <!-- Passo a Passo -->
                <div class="accordion-item border-0 mb-3 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button bg-wasomupfy text-white rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePassos_${tutorialKey.replace(/\s+/g, '')}">
                            <i class="fa-solid fa-list-check me-2"></i> Passo a Passo Detalhado
                        </button>
                    </h2>
                    <div id="collapsePassos_${tutorialKey.replace(/\s+/g, '')}" class="accordion-collapse collapse show" data-bs-parent="#accordionTutorial_${tutorialKey.replace(/\s+/g, '')}">
                        <div class="accordion-body">
                            <ol class="list-group list-group-numbered">
                                ${info.passos.map(passo => `<li class="list-group-item border-0 ps-0">${passo}</li>`).join('')}
                            </ol>
                        </div>
                    </div>
                </div>
    `;

    // Seção de Dicas
    if (info.dicas) {
        html += `
                <div class="accordion-item border-0 mb-3 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-light-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDicas_${tutorialKey.replace(/\s+/g, '')}">
                            <i class="fa-solid fa-lightbulb text-warning me-2"></i> Dicas de Ouro
                        </button>
                    </h2>
                    <div id="collapseDicas_${tutorialKey.replace(/\s+/g, '')}" class="accordion-collapse collapse" data-bs-parent="#accordionTutorial_${tutorialKey.replace(/\s+/g, '')}">
                        <div class="accordion-body">
                            <ul class="list-group">
                                ${info.dicas.map(dica => `
                                    <li class="list-group-item border-0 ps-0">
                                        <i class="fa-solid fa-check-circle text-success me-2"></i>${dica}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
        `;
    }

    // Seção de Requisitos e Especificações
    if (info.requisitos || info.especificacoes || info.proibicoes || info.definicoes) {
        html += `
                <div class="accordion-item border-0 mb-3 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-light-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRequisitos_${tutorialKey.replace(/\s+/g, '')}">
                            <i class="fa-solid fa-clipboard-check text-wasomupfy me-2"></i> Requisitos e Especificações
                        </button>
                    </h2>
                    <div id="collapseRequisitos_${tutorialKey.replace(/\s+/g, '')}" class="accordion-collapse collapse" data-bs-parent="#accordionTutorial_${tutorialKey.replace(/\s+/g, '')}">
                        <div class="accordion-body">
        `;
        
        if (info.requisitos) {
            html += `
                            <h6 class="fw-bold mb-3">Requisitos:</h6>
                            <ul class="list-group mb-4">
                                ${info.requisitos.map(req => `<li class="list-group-item border-0 ps-0"><i class="fa-solid fa-circle-check text-success me-2"></i>${req}</li>`).join('')}
                            </ul>
            `;
        }
        
        if (info.proibicoes) {
            html += `
                            <h6 class="fw-bold mb-3 text-danger">Não Permitido:</h6>
                            <ul class="list-group mb-4">
                                ${info.proibicoes.map(proib => `<li class="list-group-item border-0 ps-0"><i class="fa-solid fa-circle-exclamation text-danger me-2"></i>${proib}</li>`).join('')}
                            </ul>
            `;
        }
        
        if (info.especificacoes) {
            html += `
                            <h6 class="fw-bold mb-3 mt-4">Especificações Técnicas:</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        ${Object.entries(info.especificacoes).map(([key, value]) => `
                                            <tr>
                                                <th scope="row" class="bg-light">${key}</th>
                                                <td>${value}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
            `;
        }
        
        if (info.definicoes) {
            html += `
                            <h6 class="fw-bold mb-3 mt-4">Definições:</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        ${Object.entries(info.definicoes).map(([key, value]) => `
                                            <tr>
                                                <th scope="row" class="bg-light">${key}</th>
                                                <td>${value}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
            `;
        }
        
        html += `
                        </div>
                    </div>
                </div>
        `;
    }

    // Seção de FAQ - CORRIGIDA!
    if (info.faq) {
        html += `
                <div class="accordion-item border-0 mb-3 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-light-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFAQ_${tutorialKey.replace(/\s+/g, '')}">
                            <i class="fa-solid fa-circle-question text-info me-2"></i> Perguntas Frequentes
                        </button>
                    </h2>
                    <div id="collapseFAQ_${tutorialKey.replace(/\s+/g, '')}" class="accordion-collapse collapse" data-bs-parent="#accordionTutorial_${tutorialKey.replace(/\s+/g, '')}">
                        <div class="accordion-body">
                            <div class="list-group">
        `;
        
        // Loop correto para FAQ
        for (let i = 0; i < info.faq.length; i++) {
            if (i % 2 === 0) {
                html += `<div class="mb-2"><strong>${info.faq[i]}</strong></div>`;
            } else {
                html += `<div class="text-muted mb-3 ps-3">${info.faq[i]}</div>`;
            }
        }
        
        html += `
                            </div>
                        </div>
                    </div>
                </div>
        `;
    }

    // Seção de Estratégias (para Pitching & Marketing)
    if (info.estrategias) {
        html += `
                <div class="accordion-item border-0 mb-3 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-light-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEstrategias_${tutorialKey.replace(/\s+/g, '')}">
                            <i class="fa-solid fa-bullhorn text-wasomupfy me-2"></i> Estratégias de Marketing
                        </button>
                    </h2>
                    <div id="collapseEstrategias_${tutorialKey.replace(/\s+/g, '')}" class="accordion-collapse collapse" data-bs-parent="#accordionTutorial_${tutorialKey.replace(/\s+/g, '')}">
                        <div class="accordion-body">
                            <ul class="list-group">
                                ${info.estrategias.map(estrategia => `
                                    <li class="list-group-item border-0 ps-0">
                                        <i class="fa-solid fa-check-circle text-success me-2"></i>${estrategia}
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
        `;
    }

    // Seção de Vídeo - OPCIONAL 
    if (info.videoUrl) {
        html += `
                <div class="accordion-item border-0 shadow-sm">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-light-100" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVideo_${tutorialKey.replace(/\s+/g, '')}">
                            <i class="fa-solid fa-video text-danger me-2"></i> Vídeo Tutorial
                        </button>
                    </h2>
                    <div id="collapseVideo_${tutorialKey.replace(/\s+/g, '')}" class="accordion-collapse collapse" data-bs-parent="#accordionTutorial_${tutorialKey.replace(/\s+/g, '')}">
                        <div class="accordion-body">
                            <div class="ratio ratio-16x9 rounded-3 overflow-hidden">
                                <iframe src="${info.videoUrl}" 
                                        title="Vídeo Tutorial" 
                                        allowfullscreen>
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
        `;
    }

    // Fecha todas as tags abertas
    html += `
            </div>
        </div>
    `;

    return html;
}

  // Adiciona evento de clique em todos os links "Ler mais"
  document.querySelectorAll(".card .text-wasomupfy.fw-bold").forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const card = this.closest(".card");
      let tituloCard = card.querySelector("h3").textContent.trim();

      tituloCard = tituloCard.replace(/^[0-9]+\.\s*/, "");

      const info = tutoriaisInfo[tituloCard];

      if (info) {
        document.getElementById("modalConteudoLabel").textContent = info.titulo;
        document.getElementById("modalConteudoBody").innerHTML =
          gerarConteudoModal(tituloCard);
        const btnAcao = document.getElementById("modalBtnAcao");
        btnAcao.href = info.linkGuia || "#";
        btnAcao.textContent =
          info.linkGuia && info.linkGuia !== "#"
            ? "Ir para o guia completo"
            : "Saber mais";
        const modal = new bootstrap.Modal(
          document.getElementById("modalConteudo")
        );
        modal.show();
      }
    });
  });
});
