
document.addEventListener('DOMContentLoaded', function() {
    // Dados completos sobre Publishing
    const publishingData = {
        titulo: 'Publishing & Sync Licensing: Guia Completo',
        descricao: 'Maximize seus ganhos com direitos autorais e licenciamento sincronizado para filmes, TV e publicidade.',
        icone: 'fa-music',
        tempoLeitura: '8 minutos',
        nivel: 'Avançado',
        
        introducao: `
            <p class="lead">Enquanto a distribuição cuida dos <strong>royalties de gravação</strong> (os streams e vendas), o <strong>Publishing</strong> cuida dos <strong>royalties de composição</strong> - a parte dos compositores e letristas.</p>
            <p>Sem um publishing bem estruturado, você pode estar perdendo até 50% dos seus rendimentos musicais.</p>
        `,
        
        // Diferenças entre tipos de royalties
        diferencas: [
            {
                tipo: 'Royalties de Gravação (Master)',
                descricao: 'Pertencem ao dono da gravação (artista/selo). Gerados por streams, downloads e vendas físicas.',
                porcentagem: '~50% da receita total'
            },
            {
                tipo: 'Royalties de Composição (Publishing)',
                descricao: 'Pertencem aos compositores e editores. Gerados por execuções públicas, rádio, TV e sync.',
                porcentagem: '~50% da receita total'
            }
        ],
        
        // Passo a passo para registro
        passoAPasso: [
            {
                titulo: '1. Registre sua obra na sociedade de autores',
                descricao: 'Em Portugal: SPA (Sociedade Portuguesa de Autores). No Brasil: ECAD via UBC, ABRAMUS, etc.',
                detalhes: 'Você precisa ser associado a uma sociedade para registrar suas composições.'
            },
            {
                titulo: '2. Obtenha seu ISWC',
                descricao: 'Código único internacional para a composição (diferente do ISRC que é para a gravação).',
                detalhes: 'Gerado automaticamente pela sociedade ao registrar a obra.'
            },
            {
                titulo: '3. Divida os percentuais corretamente',
                descricao: 'Informe exatamente a porcentagem de cada compositor e editora (se houver).',
                detalhes: 'Use o contrato de parceria para documentar essas divisões.'
            },
            {
                titulo: '4. Monitore execuções públicas',
                descricao: 'Sua sociedade coleta dados de rádio, TV, shows e estabelecimentos comerciais.',
                detalhes: 'O pagamento varia conforme o país e o tipo de execução.'
            }
        ],
        
        // Sync Licensing
        syncInfo: {
            titulo: 'Licenciamento Sincronizado (Sync)',
            descricao: 'É a autorização para usar sua música junto com imagens: filmes, séries, comerciais, games, etc.',
            tipos: [
                {
                    meio: 'Cinema',
                    valores: '€2.000 - €50.000+',
                    exemplo: 'Trilha sonora de filme'
                },
                {
                    meio: 'TV (Séries)',
                    valores: '€1.000 - €20.000',
                    exemplo: 'The Crown, Stranger Things'
                },
                {
                    meio: 'Publicidade',
                    valores: '€5.000 - €100.000+',
                    exemplo: 'Comercial de TV/YouTube'
                },
                {
                    meio: 'Games',
                    valores: '€1.000 - €30.000',
                    exemplo: 'FIFA, NBA 2K'
                },
                {
                    meio: 'Trailers',
                    valores: '€3.000 - €40.000',
                    exemplo: 'Trailer de cinema'
                }
            ],
            dicas: [
                'Tenha versões instrumental das suas músicas',
                'Mantenha stems separados (vocais, bateria, etc.)',
                'Crie músicas com "arcos" (crescendo, clímax)',
                'Evite samples não liberados'
            ]
        },
        
        // Sociedades de autores por país
        sociedades: {
            'Portugal': ['SPA - Sociedade Portuguesa de Autores'],
            'Brasil': ['UBC', 'ABRAMUS', 'AMAR', 'SICAM', 'SOCINPRO', 'ASSIM'],
            'Angola': ['SADIA - Sociedade Angolana de Direitos de Autor'],
            'Moçambique': ['SOMAS - Sociedade Moçambicana de Autores'],
            'EUA': ['ASCAP', 'BMI', 'SESAC'],
            'Reino Unido': ['PRS for Music']
        },
        
        // Perguntas frequentes
        faq: [
            {
                pergunta: 'Preciso mesmo de publishing se só lanço no Spotify?',
                resposta: 'Sim! Suas músicas podem tocar em rádio, TV, academias, restaurantes - todos geram royalties de execução pública que você só recebe se tiver publishing registrado.'
            },
            {
                pergunta: 'Quanto custa registrar uma obra?',
                resposta: 'Na maioria das sociedades, a filiação é gratuita e o registro tem um custo simbólico (€5-€10 por música) ou é incluso na anuidade.'
            },
            {
                pergunta: 'Posso ter publishing internacional?',
                resposta: 'Sim. Sociedades têm acordos recíprocos. Se sua música tocar nos EUA, sua sociedade portuguesa recebe e repassa para você.'
            },
            {
                pergunta: 'Como encontro oportunidades de Sync?',
                resposta: 'Através de editoras musicais, bibliotecas de música (Music Libraries) ou agências especializadas em licensing.'
            }
        ],
        
        // Editores musicais parceiros (exemplo)
        editores: [
            'Universal Music Publishing',
            'Sony Music Publishing',
            'Warner Chappell Music',
            'BMG Rights Management',
            'Kobalt Music Group'
        ],
        
        linkContato: 'contact.html',
        videoUrl: 'https://www.youtube.com/embed/SEU_VIDEO_PUBLISHING' // Opcional
    };

    // Função para gerar o HTML do modal
    function gerarModalPublishing() {
        const data = publishingData;
        
        let html = `
            <div class="mb-4">
                <!-- Cabeçalho com ícone e badges -->
                <div class="d-flex align-items-center mb-3">
                    <div class="icon-shape icon-md bg-wasomupfy bg-opacity-10 text-wasomupfy rounded-circle me-3">
                        <i class="fa-solid ${data.icone} fs-4"></i>
                    </div>
                    <div>
                        <span class="badge bg-danger me-2">${data.nivel}</span>
                        <span class="text-muted"><i class="fa-regular fa-clock me-1"></i>${data.tempoLeitura} de leitura</span>
                    </div>
                </div>
                
                <!-- Introdução -->
                ${data.introducao}
                
                <!-- Accordion Principal -->
                <div class="accordion" id="accordionPublishing">
                    
                    <!-- 1. Diferença entre Gravação e Composição -->
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button bg-wasomupfy text-white rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDiferencas">
                                <i class="fa-solid fa-scale-balanced me-2"></i> Master vs Publishing: Qual a diferença?
                            </button>
                        </h2>
                        <div id="collapseDiferencas" class="accordion-collapse collapse show" data-bs-parent="#accordionPublishing">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Descrição</th>
                                                <th>% da Receita</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.diferencas.map(item => `
                                                <tr>
                                                    <td><strong>${item.tipo}</strong></td>
                                                    <td>${item.descricao}</td>
                                                    <td><span class="badge bg-info">${item.porcentagem}</span></td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-warning mt-3 mb-0">
                                    <i class="fa-solid fa-lightbulb me-2"></i>
                                    <strong>Importante:</strong> Se você é compositor E intérprete, tem direito aos DOIS tipos de royalty!
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 2. Passo a Passo para Registrar -->
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePassos">
                                <i class="fa-solid fa-list-check text-wasomupfy me-2"></i> Passo a Passo: Registre suas obras
                            </button>
                        </h2>
                        <div id="collapsePassos" class="accordion-collapse collapse" data-bs-parent="#accordionPublishing">
                            <div class="accordion-body">
                                ${data.passoAPasso.map(passo => `
                                    <div class="mb-4">
                                        <h5 class="fw-bold">${passo.titulo}</h5>
                                        <p class="mb-1">${passo.descricao}</p>
                                        <small class="text-muted"><i class="fa-solid fa-info-circle me-1"></i>${passo.detalhes}</small>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    
                    <!-- 3. Sync Licensing -->
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSync">
                                <i class="fa-solid fa-film text-danger me-2"></i> Sync Licensing: Valores e Oportunidades
                            </button>
                        </h2>
                        <div id="collapseSync" class="accordion-collapse collapse" data-bs-parent="#accordionPublishing">
                            <div class="accordion-body">
                                <p class="fw-bold">${data.syncInfo.descricao}</p>
                                
                                <h6 class="mt-3 mb-2">Valores de Mercado (estimativa):</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Meio</th>
                                                <th>Faixa de Valores</th>
                                                <th>Exemplo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.syncInfo.tipos.map(tipo => `
                                                <tr>
                                                    <td><strong>${tipo.meio}</strong></td>
                                                    <td>${tipo.valores}</td>
                                                    <td><small class="text-muted">${tipo.exemplo}</small></td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                
                                <h6 class="mt-3 mb-2">Dicas para Sync:</h6>
                                <ul class="list-group">
                                    ${data.syncInfo.dicas.map(dica => `
                                        <li class="list-group-item border-0 ps-0">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i>${dica}
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 4. Sociedades por País -->
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSociedades">
                                <i class="fa-solid fa-globe text-info me-2"></i> Sociedades de Autores por País
                            </button>
                        </h2>
                        <div id="collapseSociedades" class="accordion-collapse collapse" data-bs-parent="#accordionPublishing">
                            <div class="accordion-body">
                                <div class="row">
                                    ${Object.entries(data.sociedades).map(([pais, socs]) => `
                                        <div class="col-md-6 mb-3">
                                            <h6 class="fw-bold">${pais}</h6>
                                            <ul class="list-unstyled">
                                                ${socs.map(soc => `<li><i class="fa-solid fa-building me-2 text-wasomupfy"></i>${soc}</li>`).join('')}
                                            </ul>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 5. FAQ -->
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq">
                                <i class="fa-solid fa-circle-question text-warning me-2"></i> Perguntas Frequentes
                            </button>
                        </h2>
                        <div id="collapseFaq" class="accordion-collapse collapse" data-bs-parent="#accordionPublishing">
                            <div class="accordion-body">
                                ${data.faq.map(item => `
                                    <div class="mb-4">
                                        <p class="fw-bold mb-1"><i class="fa-solid fa-question-circle text-wasomupfy me-2"></i>${item.pergunta}</p>
                                        <p class="text-muted ps-4 mb-0">${item.resposta}</p>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                    
                    <!-- 6. Editores Parceiros -->
                    <div class="accordion-item border-0 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEditores">
                                <i class="fa-solid fa-handshake text-success me-2"></i> Principais Editoras Musicais
                            </button>
                        </h2>
                        <div id="collapseEditores" class="accordion-collapse collapse" data-bs-parent="#accordionPublishing">
                            <div class="accordion-body">
                                <p>Estas editoras podem representar seu catálogo globalmente:</p>
                                <div class="row">
                                    ${data.editores.map(editor => `
                                        <div class="col-6 mb-2">
                                            <i class="fa-solid fa-check-circle text-success me-2"></i>${editor}
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                    
        `;
        
        // Seção de vídeo (opcional)
        if (data.videoUrl) {
            html += `
                    <!-- 7. Vídeo Explicativo -->
                    <div class="accordion-item border-0 mt-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVideo">
                                <i class="fa-solid fa-video text-danger me-2"></i> Vídeo Explicativo
                            </button>
                        </h2>
                        <div id="collapseVideo" class="accordion-collapse collapse" data-bs-parent="#accordionPublishing">
                            <div class="accordion-body">
                                <div class="ratio ratio-16x9 rounded-3 overflow-hidden">
                                    <iframe src="${data.videoUrl}" title="Vídeo Publishing" allowfullscreen></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
            `;
        }
        
        // Fecha o accordion e o container
        html += `
                </div>
            </div>
        `;
        
        return html;
    }

    // Versão mais precisa - busca pelo texto exato
document.querySelectorAll('a.btn-link.text-wasomupfy').forEach(link => {
    if (link.textContent.trim() === 'Saber mais sobre Publishing') {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Atualiza o modal com o conteúdo
            document.getElementById('modalPublishingBody').innerHTML = gerarModalPublishing();
            
            // Abre o modal
            const modal = new bootstrap.Modal(document.getElementById('modalPublishing'));
            modal.show();
        });
    }
});
});
