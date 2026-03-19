<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="José Mbenga da Costa" />
    <meta name="theme-color" content="#FF0089">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="#FF0089">
    <link rel="apple-touch-icon" href="../../../assets/img/icones/wasomupfy_fiv_512.png">
    <link rel="apple-touch-startup-image" href="../../../assets/img/screenshots/splash.png">
    <link rel="manifest" href="manifest.json">
    <title>Visão geral — Wasom Upfy</title>
    <link rel="shortcut icon" href="../assets/img/icones/wasomupfy_fiv.png" type="image/x-icon">
    <link rel="stylesheet" href="../../../css/libs/plugins.css">
    <link rel="stylesheet" href="../../../css/libs/scrollue.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@6.2.5/dist/simplebar.min.css" />
    <link rel="stylesheet" href="../css/lastest-style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="d-flex align-items-center">
                    <img src="../../../assets/img/brand/wasomupfy_brand.png" alt="Logo Wasom Upfy"
                        class="rounded-circle me-2" style="height: 40px;">
                    <span class="brand-text">Wasom Upfy</span>
                </div>
                <i class="bi bi-chevron-left toggle-icon" id="sidebarCollapse" title="Colapsar/Expandir Menu"
                    aria-label="Colapsar/Expandir Menu"></i>
            </div>
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a href="../../home" class="nav-link">
                        <i class="bi bi-speedometer2"></i>
                        <span>Painel de Controle</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#collapseAnalytics" class="nav-link active" data-bs-toggle="collapse" aria-expanded="false"
                        aria-controls="collapseAnalytics">
                        <i class="bi bi-graph-up"></i>
                        <span>Estatísticas e Análises</span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                    </a>
                    <div class="collapse" id="collapseAnalytics">
                        <a href="../analytics/home" class="nav-link active">
                            <i class="bi bi-bar-chart-line"></i>
                            <span>Visão Geral</span>
                        </a>
                        <a href="../analytics/artists" class="nav-link">
                            <i class="bi bi-person-lines-fill"></i>
                            <span>Desempenho por Artista</span>
                        </a>
                        <a href="../analytics/stores" class="nav-link">
                            <i class="bi bi-shop"></i>
                            <span>Desempenho por Loja Digital</span>
                        </a>
                        <a href="../analytics/reports" class="nav-link">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>Relatórios Personalizados</span>
                        </a>
                    </div>
                </li>

                <li class="nav-item">
                    <a href="#collapseAdmins" class="nav-link" data-bs-toggle="collapse" aria-expanded="false"
                        aria-controls="collapseAdmins">
                        <i class="bi bi-person-gear"></i>
                        <span>Gestão de Admins</span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                    </a>
                    <div class="collapse" id="collapseAdmins">
                        <a href="../employees/all-employees" class="nav-link">
                            <i class="bi bi-people"></i>
                            <span>Listar Admins</span>
                        </a>
                        <a href="../employees/add" class="nav-link">
                            <i class="bi bi-person-plus"></i>
                            <span>Adicionar</span>
                        </a>
                        <a href="../employees/edit" class="nav-link">
                            <i class="bi bi-person-gear"></i>
                            <span>Editar</span>
                        </a>
                        <a href="../employees/delete" class="nav-link">
                            <i class="bi bi-person-x"></i>
                            <span>Excluir</span>
                        </a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#collapseUsers" class="nav-link" data-bs-toggle="collapse" aria-expanded="false"
                        aria-controls="collapseUsers">
                        <i class="bi bi-person-gear"></i>
                        <span>Gestão de Usuários</span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                    </a>
                    <div class="collapse" id="collapseUsers">
                        <a href="../users/all-users" class="nav-link">
                            <i class="bi bi-people"></i>
                            <span>Todos Usuários</span>
                        </a>
                        <a href="../users/add" class="nav-link">
                            <i class="bi bi-person-plus"></i>
                            <span>Adicionar</span>
                        </a>
                        <a href="../users/edit" class="nav-link">
                            <i class="bi bi-person-gear"></i>
                            <span>Editar</span>
                        </a>
                        <a href="../users/delete" class="nav-link">
                            <i class="bi bi-person-x"></i>
                            <span>Excluir</span>
                        </a>
                        <a href="../users/available-account" class="nav-link">
                            <i class="bi bi-person-check"></i>
                            <span>Contas Disponíveis</span>
                        </a>
                        <a href="../users/unavailable-account" class="nav-link">
                            <i class="bi bi-person-exclamation"></i>
                            <span>Contas Indisponíveis</span>
                        </a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#collapseSongs" class="nav-link" data-bs-toggle="collapse" aria-expanded="false"
                        aria-controls="collapseSongs">
                        <i class="bi bi-music-note-list"></i>
                        <span>Gestão de Músicas</span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                    </a>
                    <div class="collapse" id="collapseSongs">
                        <a href="../music/revise" class="nav-link">
                            <i class="bi bi-eye"></i>
                            <span>Revisar Envios</span>
                        </a>
                        <a href="../music/approve" class="nav-link">
                            <i class="bi bi-check-circle"></i>
                            <span>Aprovar</span>
                        </a>
                        <a href="../music/reject" class="nav-link">
                            <i class="bi bi-x-circle"></i>
                            <span>Rejeitar</span>
                        </a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="../artist/accounts-users" class="nav-link">
                        <i class="bi bi-person-check"></i>
                        <span>Contas e Usuários</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../artist/collaborators-artist" class="nav-link">
                        <i class="bi bi-people"></i>
                        <span>Artistas e Colaboradores</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#collapseDistribution" class="nav-link" data-bs-toggle="collapse" aria-expanded="false"
                        aria-controls="collapseDistribution">
                        <i class="bi bi-globe"></i>
                        <span>Distribuição</span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                    </a>
                    <div class="collapse" id="collapseDistribution">
                        <a href="../distribution/releases" class="nav-link">
                            <i class="bi bi-rocket-takeoff"></i>
                            <span>Lançamentos</span>
                        </a>
                        <a href="../distribution/store" class="nav-link">
                            <i class="bi bi-shop"></i>
                            <span>Lojas Digitais</span>
                        </a>
                        <a href="../distribution/schedule" class="nav-link">
                            <i class="bi bi-calendar-event"></i>
                            <span>Agendar Lançamento</span>
                        </a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="../manager/gestion" class="nav-link">
                        <i class="bi bi-star"></i>
                        <span>Gestão Geral</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../finances/payments" class="nav-link">
                        <i class="bi bi-wallet2"></i>
                        <span>Pagamentos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../finances/earnings" class="nav-link">
                        <i class="bi bi-currency-dollar"></i>
                        <span>Finanças e Rendimentos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#collapseIntegration" class="nav-link" data-bs-toggle="collapse" aria-expanded="false"
                        aria-controls="collapseIntegration">
                        <i class="bi bi-youtube"></i>
                        <span>Unificação e V. Youtube</span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                    </a>
                    <div class="collapse" id="collapseIntegration">
                        <a href="../integration/youtube" class="nav-link">
                            <i class="bi bi-gear"></i>
                            <span>Configurar Integração</span>
                        </a>
                        <a href="../integration/verify" class="nav-link">
                            <i class="bi bi-check2-all"></i>
                            <span>Verificar Canais</span>
                        </a>
                        <a href="../integration/monetization" class="nav-link">
                            <i class="bi bi-youtube"></i>
                            <span>Gerenciamento de Conteúdo Monetizado</span>
                        </a>
                    </div>
                </li>

                <li class="nav-item">
                    <a href="#collapseSupport" class="nav-link" data-bs-toggle="collapse" aria-expanded="false"
                        aria-controls="collapseSupport">
                        <i class="bi bi-headset"></i>
                        <span>Suporte</span>
                        <span class="badge bg-danger badge-notification">3</span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                    </a>
                    <div class="collapse" id="collapseSupport">
                        <a href="../messages/inbox" class="nav-link">
                            <i class="bi bi-envelope"></i>
                            <span>Caixa de entrada</span>
                        </a>
                        <a href="../messages/compose" class="nav-link">
                            <i class="bi bi-pencil"></i>
                            <span>Enviar mensagens</span>
                        </a>
                    </div>
                </li>

                <li class="nav-item">
                    <a href="#collapseHelp" class="nav-link" data-bs-toggle="collapse" aria-expanded="false"
                        aria-controls="collapseHelp">
                        <i class="bi bi-question-circle"></i>
                        <span>Ajuda</span>
                        <i class="bi bi-chevron-down ms-auto" style="font-size: 0.8rem;"></i>
                    </a>
                    <div class="collapse" id="collapseHelp">
                        <a href="../help/faqs" class="nav-link">
                            <i class="bi bi-messenger"></i>
                            <span>FAQs</span>
                        </a>
                        <a href="../help/tutorials" class="nav-link">
                            <i class="bi bi-book"></i>
                            <span>Tutoriais</span>
                        </a>
                        <a href="../help/contact" class="nav-link">
                            <i class="bi bi-telephone"></i>
                            <span>Contacto com suporte</span>
                        </a>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="../settings/config" class="nav-link">
                        <i class="bi bi-sliders"></i>
                        <span>Configurações</span>
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#logoutwasomupfy">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a href="http://localhost:5500/home" target="_blank" class="nav-link">
                        <i class="bi bi-box-arrow-in-up-right"></i>
                        <span>Visitar Site</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Content -->
        <div class="content w-100" id="mainContent">
            <nav class="navbar navbar-expand-lg">
                <button class="navbar-toggler" type="button" id="sidebarToggle" aria-label="Abrir/Fechar Menu">
                    <i class="bi bi-list text-white"></i>
                </button>
                <div class="ms-auto d-flex align-items-center">
                    <button class="btn btn-outline-light btn-sm me-2" onclick="toggleDarkMode()"
                        aria-label="Alternar Modo Escuro">
                        <i class="bi bi-moon"></i>
                    </button>
                    <div class="dropdown me-2 position-relative">
                        <button class="btn btn-outline-light btn-sm position-relative" type="button"
                            data-bs-toggle="dropdown" aria-label="Notificações">
                            <i class="bi bi-bell"></i>
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">5</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-start p-0" style="min-width: 250px;">
                            <li class="dropdown-header bg-dark text-white p-2">Notificações (5)</li>
                            <li><a class="dropdown-item p-2" href="#">Novo artista registrado</a></li>
                            <li><a class="dropdown-item p-2" href="#">Música atingiu 1000 plays</a></li>
                            <li><a class="dropdown-item p-2" href="#">Atualização do sistema disponível</a></li>
                            <li class="dropdown-footer text-center p-2"><a href="#" class="text-primary">Ver todas</a>
                            </li>
                        </ul>
                    </div>
                    <div class="dropdown me-2 position-relative">
                        <button class="btn btn-outline-light btn-sm position-relative" type="button"
                            data-bs-toggle="dropdown" aria-label="Mensagens">
                            <i class="bi bi-envelope"></i>
                            <span
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">2</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-start p-0" style="min-width: 250px;">
                            <li class="dropdown-header bg-dark text-white p-2">Mensagens (2)</li>
                            <li><a class="dropdown-item p-2" href="#">Suporte #4521 - Novo ticket</a></li>
                            <li><a class="dropdown-item p-2" href="#">Mensagem de artista</a></li>
                            <li class="dropdown-footer text-center p-2"><a href="#" class="text-primary">Ver todas</a>
                            </li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center"
                            type="button" data-bs-toggle="dropdown" aria-label="Menu do Usuário">
                            <img src="../../../assets/img/avatar/avatar.png" alt="Usuário" class="rounded-circle me-1"
                                style="height: 24px;">
                            <span>Cristiano Amadeu</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../user/profile"><i
                                        class="bi bi-person me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="../settings/config"><i
                                        class="bi bi-sliders me-2"></i>Configurações</a></li>
                            <li><a class="dropdown-item" href="../help/help"><i
                                        class="bi bi-question-circle me-2"></i>Ajuda</a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#logoutwasomupfy"
                                    href="#"><i class="bi bi-box-arrow-right me-2"></i>Sair</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Adicione isso dentro da tag <nav class="bottom-nav"> -->
            <div class="connection-status" id="connectionStatus"></div>
            <div class="status-notification" id="statusNotification"></div>

            <!-- ════ MODAL — Logout ════ -->
            <div class="modal fade" id="logoutwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false"
                tabindex="-1" aria-labelledby="logoutwasomupfyLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content modal-bottom">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5 text-dark" id="logoutwasomupfyLabel">Terminar sessão</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="container">
                                <div class="row justify-content-center text-center">
                                    <div class="col-md-12 content-center justify-center text-center">
                                        <p class="text-center text-dark">@josembengadacosta você tem
                                            certeza
                                            de que desejas terminar
                                            sessão?</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div>
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Não,
                                    continuar</button>
                            </div>
                            <div>
                                <button class="btn btn-danger" type="button" name="logout_wasomupfy"
                                    onclick="logout_wasomupfy()">Sim, terminar</button>
                            </div>
                            <script type="text/javascript">
                            function logout_wasomupfy() {
                                window.location = 'logout';
                            }
                            </script>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ════ MODAL — Logout  FIM ════ -->

            <div class="container-fluid p-0">
                <div class="row mb-3 mt-2">
                    <div class="welcome-text col-auto d-sm-block">
                        <h2 class="h4 mb-2"><i class="bi bi-bar-chart-line me-2"></i>Visão Geral</span></h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="home" class="text-secondary">Estatísticas e
                                        Análises</a>
                                </li>
                                <li class="breadcrumb-item active text-secondary" aria-current="page">Visão Geral</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="col-auto ms-auto text-end mt-n1 mt-3 mb-2">
                        <a class="text-secondary shadow-sm me-2" href="">Ver Playlist</a>
                        <a class="text-secondary shadow-sm me-2" href="">Todos territórios</a>
                        <a class="text-secondary shadow-sm" href="">Todos os relatórios</a>
                        <button class="btn btn-wasomupfy text-white shadow-sm" data-bs-toggle="modal"
                            data-bs-target="#addArtistwasomupfy">
                            <i class="align-middle bi bi-plus"></i> Adcionar dados</button>
                    </div>
                    <!-- Stats Description -->
                    <p class="stats-description mt-2">
                        Encontras aqui o lançamento de todas as contas da plataforma, mais poderá depender se és
                        administrador regional ou glboal para veres alguns lançamentos disponíveis. Caso tenhas dúvidas
                        em
                        alguns lançamentos faça a pesquisa do mesmo através do seu <strong>Título</strong>,
                        <strong>Artista</strong> ou <strong>UPC</strong>.
                    </p>
                    <!-- Filtros -->
                    <div class="search-container fade-in-custom">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="stats-id">ID:</label>
                                <input type="number" class="form-control" id="stats-id" placeholder="ID">
                            </div>
                            <div class="col-md-2">
                                <label for="stats-account">Conta:</label>
                                <input type="text" class="form-control" id="stats-account" placeholder="Conta">
                            </div>
                            <div class="col-md-2">
                                <label for="stats-track">Música:</label>
                                <input type="text" class="form-control" id="stats-track" placeholder="Música">
                            </div>
                            <div class="col-md-2">
                                <label for="stats-artist">Artista:</label>
                                <input type="text" class="form-control" id="stats-artist" placeholder="Artista">
                            </div>
                            <div class="col-md-2">
                                <label for="stats-playlist">Playlist:</label>
                                <select class="form-select" id="stats-playlist">
                                    <option value="">Todas</option>
                                    <option value="Top 50">Top 50</option>
                                    <option value="Viral">Viral</option>
                                    <option value="Editorial">Editorial</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="stats-territory">Território:</label>
                                <select class="form-select" id="stats-territory">
                                    <option value="">Todos</option>
                                    <option value="AO">Angola</option>
                                    <option value="PT">Portugal</option>
                                    <option value="BR">Brasil</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                            <button class="btn btn-wasomupfy text-white" id="clear-stats-filters"><i
                                    class="bi bi-eraser me-2"></i> Limpar
                                Filtros</button>
                            <span class="" id="results-count">0 resultados</span>
                        </div>
                    </div>
                </div>

                <!-- Tabela -->
                <div class="card fade-in-custom mt-3">
                    <div class="table-responsive">
                        <table id="stats-table" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Conta</th>
                                    <th>Música</th>
                                    <th>Artista</th>
                                    <th>Streams</th>
                                    <th>Plataforma</th>
                                    <th>Playlist</th>
                                    <th>Território</th>
                                    <th>Data de reprodução</th>
                                    <th>Açcões</th>
                                </tr>
                            </thead>
                            <tbody id="stats-list">
                                <!-- Dados serão carregados via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center" id="stats-pagination"></ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>



    <!-- Modal de Visualização -->
    <div class="modal fade" id="viewStatsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Estatística</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img id="stats-cover" src="" class="img-fluid rounded mb-3" alt="Capa"
                                style="max-height: 250px;">
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Música:</strong> <span id="view-track"></span></p>
                                    <p><strong>Artista:</strong> <span id="view-artist"></span></p>
                                    <p><strong>Streams:</strong> <span id="view-streams"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Plataforma:</strong> <span id="view-platform"></span></p>
                                    <p><strong>Playlist:</strong> <span id="view-playlist"></span></p>
                                    <p><strong>Território:</strong> <span id="view-territory"></span></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <p><strong>Data:</strong> <span id="view-date"></span></p>
                                <p><strong>Link:</strong> <a id="view-link" href="#" target="_blank">Abrir na
                                        plataforma</a></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="editStatsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Estatística</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="edit-stats-body">
                    <!-- Formulário será preenchido via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="save-stats-changes">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteStatsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="delete-stats-message">Tem certeza que deseja excluir esta estatística?</p>
                    <div class="mb-3">
                        <label for="delete-stats-password" class="form-label">Digite sua senha:</label>
                        <input type="password" class="form-control" id="delete-stats-password" required>
                        <div class="invalid-feedback">Por favor, insira sua senha</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirm-stats-delete">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal add artist -->
    <div class="modal fade" id="addArtistwasomupfy" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="addArtistwasomupfyLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content modal-bottom">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 text-dark" id="addArtistwasomupfyLabel">Pedido de UVY</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="artist-form">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="name" value="" required>
                                    <label class="form-label">ID</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="account" value="" required>
                                    <label class="form-label">Conta</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="account" value="" required>
                                    <label class="form-label">Artista</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <select class="form-select" name="role" required>
                                        <option value="verified">Verificado</option>
                                        <option value="pending">Pendente</option>
                                        <option value="rejected">Rejeitado</option>
                                        <option value="expired">Expirado</option>
                                    </select>
                                    <label class="form-label">Estado</label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating mb-3">
                                    <input type="url" class="form-control" name="channel_link" value="">
                                    <label class="form-label">Link do Canal</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="date" class="form-control" name="creation_date" value="" required>
                                    <label class="form-label">Data de Criação</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <div>
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                    <div>
                        <button class="btn btn-wasomupfy text-white" type="button"
                            name="logout_wasomupfy">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ════ MODAL — Logout  FIM ════ -->

    <!-- Floating Action Button -->
    <div class="fab" onclick="showQuickAction()" aria-label="Ações Rápidas">
        <i class="bi bi-plus-lg"></i>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-2">© 2026 Wasom Upfy. Todos os direitos reservados.</p>
                    <a href="#" class="me-2">Termos de Uso</a>
                    <a href="#" class="me-2">Privacidade</a>
                    <a href="#">Suporte</a>
                </div>
            </div>
        </div>
    </footer>


    <!-- Bottom Navigation -->
    <!-- <nav class="bottom-nav">
    <ul>
        <li>
            <a href="home" class="active">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="../music/approve">
                <i class="bi bi-music-note-list"></i>
                <span>Músicas</span>
            </a>
        </li>
        <li>
            <a href="../users/all-users">
                <i class="bi bi-people"></i>
                <span>Usuários</span>
            </a>
        </li>
        <li>
            <a href="../finances/earnings">
                <i class="bi bi-currency-dollar"></i>
                <span>Finanças</span>
            </a>
        </li>
        <li>
            <a href="../settings/config">
                <i class="bi bi-sliders"></i>
                <span>Config</span>
            </a>
        </li>
    </ul>
</nav> -->


    <div class="page-loader" id="pageLoader">
        <div class="loader-content">
            <!-- Sua imagem pulsante -->
            <img src="../../../assets/img/brand/wasomupfy_brand.png" class="loader-image" alt="Carregando">
            <!-- Barra de progresso agora perfeitamente centralizada -->
            <div class="loader-progress"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Dados de exemplo
    const statsData = [{
            id: 1,
            account: "Conta Pro",
            track: "Melodia Tropical",
            artist: "Carlos Dias",
            streams: 150000,
            platform: "deezer",
            playlist: "Top 50",
            territory: "AO",
            date: "2023-06-15",
            cover: "https://via.placeholder.com/300?text=Capa+1",
            link: "https://open.spotify.com/track/123"
        },
        {
            id: 2,
            account: "Conta Basic",
            track: "Noite em Luanda",
            artist: "Ana Silva",
            streams: 75000,
            platform: "apple",
            playlist: "Editorial",
            territory: "AO",
            date: "2023-06-14",
            cover: "https://via.placeholder.com/300?text=Capa+2",
            link: "https://music.apple.com/track/456"
        },
        {
            id: 3,
            account: "Conta Internacional",
            track: "Saudades de Luanda",
            artist: "Banda Maravilha",
            streams: 250000,
            platform: "spotify",
            playlist: "Viral",
            territory: "PT",
            date: "2023-06-18",
            cover: "https://via.placeholder.com/300?text=Capa+3",
            link: "https://open.spotify.com/track/789"
        },
        {
            id: 4,
            account: "Conta Intl",
            track: "Saudade",
            artist: "Marco Pereira",
            streams: 220000,
            platform: "discogs",
            playlist: "Sem playlist",
            territory: "BR",
            date: "2023-06-10",
            cover: "https://via.placeholder.com/300?text=Capa+4",
            link: "https://open.spotify.com/track/734"
        }
    ];
    </script>
    <script>
    // Configurações
    const itemsPerPage = 5;
    let currentPage = 1;
    let currentEditingId = null;

    // Elementos DOM
    const elements = {
        list: document.getElementById("stats-list"),
        pagination: document.getElementById("stats-pagination"),
        resultsCount: document.getElementById("results-count"),
        searchInputs: {
            id: document.getElementById("stats-id"),
            account: document.getElementById("stats-account"),
            track: document.getElementById("stats-track"),
            artist: document.getElementById("stats-artist"),
            playlist: document.getElementById("stats-playlist"),
            territory: document.getElementById("stats-territory"),
        },
        clearBtn: document.getElementById("clear-stats-filters"),
        viewModal: new bootstrap.Modal("#viewStatsModal"),
        editModal: new bootstrap.Modal("#editStatsModal"),
        deleteModal: new bootstrap.Modal("#deleteStatsModal"),
        saveBtn: document.getElementById("save-stats-changes"),
        confirmDeleteBtn: document.getElementById("confirm-stats-delete"),
        deletePassword: document.getElementById("delete-stats-password"),
    };

    // Inicialização
    document.addEventListener("DOMContentLoaded", initStats);

    function initStats() {
        renderStats(statsData);
        setupEventListeners();
    }

    // Renderização principal
    function renderStats(data) {
        elements.list.innerHTML = "";

        const paginatedData = data.slice(
            (currentPage - 1) * itemsPerPage,
            currentPage * itemsPerPage
        );

        elements.resultsCount.textContent = `${data.length} ${data.length === 1 ? "resultado" : "resultados"
        }`;

        paginatedData.forEach((item) => {
            const row = document.createElement("tr");
            row.innerHTML = `
            <td>${item.id}</td>
            <td>${item.account}</td>
            <td>${item.track}</td>
            <td>${item.artist}</td>
            <td>${formatNumber(item.streams)}</td>
            <td>${getPlatformIcon(item.platform)} ${getPlatformName(
            item.platform
        )}</td>
            <td>${item.playlist}</td>
            <td>${getTerritoryFlag(item.territory)} ${getTerritoryName(
            item.territory
        )}</td>
            <td>${formatDate(item.date)}</td>
            <td>
                <button class="btn btn-sm btn-outline-info view-btn me-1" data-id="${item.id
            }" title="Visualizar">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary edit-btn me-1" data-id="${item.id
            }" title="Editar">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${item.id
            }" title="Excluir">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
            elements.list.appendChild(row);
        });

        renderPagination(data.length);
        setupActionButtons();
    }

    // Funções auxiliares
    function formatNumber(num) {
        return new Intl.NumberFormat("pt-AO").format(num);
    }

    function formatDate(dateString) {
        return new Date(dateString).toLocaleDateString("pt-AO");
    }

    function getTerritoryFlag(territory) {
        const flags = {
            AO: "🇦🇴",
            PT: "🇵🇹",
            BR: "🇧🇷",
        };
        return flags[territory] || "";
    }

    function getTerritoryName(territory) {
        const names = {
            AO: "Angola",
            PT: "Portugal",
            BR: "Brasil",
        };
        return names[territory] || territory;
    }

    function getPlatformIcon(platform) {
        const platformIcons = {
            spotify: '<i class="bi bi-spotify" style="color: #1DB954;"></i>',
            apple: '<i class="bi bi-apple" style="color: #e4e4e4ff;"></i>',
            snapchat: '<i class="bi bi-snapchat" style="color: #fcd63cff;"></i>',
            deezer: '<i class="bi bi-music-player" style="color: #f20089ff;"></i>',
            youtube: '<i class="bi bi-youtube" style="color: #FF0000;"></i>',
            "youtube-music": '<i class="bi bi-youtube" style="color: #FF0000;"></i>',
            tidal: '<i class="bi bi-music-note-list" style="color: #00FFFF;"></i>',
            "amazon-music": '<i class="bi bi-music-note" style="color: #fcd63cff;"></i>',
            soundcloud: '<i class="bi bi-cloud" style="color: #FF5500;"></i>',
            pandora: '<i class="bi bi-radioactive" style="color: #005483;"></i>',
            napster: '<i class="bi bi-music-note" style="color: #000000;"></i>',
            kkbox: '<i class="bi bi-music-note" style="color: #33CC33;"></i>',
            qobuz: '<i class="bi bi-music-note" style="color: #1A1A1A;"></i>',
            iheartradio: '<i class="bi bi-heart-fill" style="color: #C6002B;"></i>',
            audiomack: '<i class="bi bi-music-note" style="color: #FFA200;"></i>',
            bandcamp: '<i class="bi bi-music-note" style="color: #629AA9;"></i>',
            anghami: '<i class="bi bi-music-note" style="color: #F70F0F;"></i>',
            boomplay: '<i class="bi bi-music-note" style="color: #00D1B2;"></i>',
            joox: '<i class="bi bi-music-note" style="color: #FF5F5F;"></i>',
            gaana: '<i class="bi bi-music-note" style="color: #FF3D6E;"></i>',
            wynk: '<i class="bi bi-music-note" style="color: #4CAF50;"></i>',
            jiosaavn: '<i class="bi bi-music-note" style="color: #00B0FF;"></i>',
            tiktok: '<i class="bi bi-tiktok" style="color: #010101;"></i>',
            triller: '<i class="bi bi-music-note" style="color: #FF0080;"></i>',
            shazam: '<i class="bi bi-search" style="color: #0088FF;"></i>',
            discogs: '<i class="bi bi-vinyl-fill" style="color: #333333;"></i>',
            beatport: '<i class="bi bi-music-note" style="color: #94D500;"></i>',
            junodownload: '<i class="bi bi-music-note" style="color: #FF6600;"></i>',
            traxsource: '<i class="bi bi-music-note" style="color: #00B4FF;"></i>',
            reverbnation: '<i class="bi bi-music-note" style="color: #E4352B;"></i>',
            audiophile: '<i class="bi bi-music-note" style="color: #6B3FA0;"></i>',
        };

        return (
            platformIcons[platform.toLowerCase()] ||
            `<i class="bi bi-music-note" style="color: #6c757d;"></i> ${platform}`
        );
    }

    function getPlatformName(platform) {
        const platformNames = {
            // Plataformas globais
            spotify: "Spotify",
            apple: "Apple Music",
            deezer: "Deezer",
            youtube: "YouTube Music",
            "youtube-music": "YouTube Music",
            tidal: "Tidal",
            "amazon-music": "Amazon Music",
            soundcloud: "SoundCloud",
            pandora: "Pandora",
            napster: "Napster",
            iheartradio: "iHeartRadio",
            audiomack: "Audiomack",
            bandcamp: "Bandcamp",
            shazam: "Shazam",

            // Plataformas asiáticas
            kkbox: "KKBOX",
            joox: "JOOX",
            melon: "Melon",
            "line-music": "LINE Music",
            bugs: "Bugs!",

            // Plataformas indianas
            gaana: "Gaana",
            wynk: "Wynk",
            jiosaavn: "JioSaavn",
            hungama: "Hungama",

            // Plataformas africanas
            anghami: "Anghami",
            boomplay: "Boomplay",
            mdundo: "Mdundo",

            // Plataformas latino-americanas
            "claro-musica": "Claro Música",
            "tigo-music": "Tigo Music",
            "movistar-musica": "Movistar Música",

            // Plataformas de DJs/produção
            beatport: "Beatport",
            traxsource: "Traxsource",
            junodownload: "Juno Download",
            bandlab: "BandLab",

            // Plataformas de descoberta
            discogs: "Discogs",
            lastfm: "Last.fm",
            musixmatch: "Musixmatch",

            // Redes sociais/short-form
            tiktok: "TikTok",
            triller: "Triller",
            instagram: "Instagram",
            snapchat: "Snapchat",

            // Plataformas especializadas
            qobuz: "Qobuz",
            idagio: "Idagio",
            primephonic: "Primephonic",

            // Plataformas de áudio 3D
            sonicast: "Sonicast",
            dolby: "Dolby Atmos Music",

            // Outras
            "8tracks": "8tracks",
            slacker: "Slacker Radio",
            rhapsody: "Rhapsody",
            groove: "Groove Music",
            "yandex-music": "Yandex Music",
            "vk-music": "VK Music",
        };

        // Verifica se a plataforma existe no dicionário (case insensitive)
        const normalizedPlatform = platform.toLowerCase().replace(/\s+/g, "-");
        return (
            platformNames[normalizedPlatform] ||
            platform
            .split("-")
            .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(" ")
        );
    }

    // Ações (Visualizar, Editar, Excluir)
    function setupActionButtons() {
        // Visualizar
        document.querySelectorAll(".view-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
                const itemId = parseInt(btn.dataset.id);
                const item = statsData.find((item) => item.id === itemId);
                showViewModal(item);
            });
        });

        // Editar
        document.querySelectorAll(".edit-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
                currentEditingId = parseInt(btn.dataset.id);
                const item = statsData.find((item) => item.id === currentEditingId);
                openEditModal(item);
            });
        });

        // Excluir
        document.querySelectorAll(".delete-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
                currentEditingId = parseInt(btn.dataset.id);
                const item = statsData.find((item) => item.id === currentEditingId);
                document.getElementById(
                    "delete-stats-message"
                ).textContent = `Tem certeza que deseja excluir os dados de "${item.track}"?`;
                elements.deleteModal.show();
            });
        });
    }

    // Modal de Visualização
    function showViewModal(item) {
        document.getElementById("stats-cover").src = item.cover;
        document.getElementById("view-track").textContent = item.track;
        document.getElementById("view-artist").textContent = item.artist;
        document.getElementById("view-streams").textContent = formatNumber(
            item.streams
        );
        document.getElementById("view-platform").innerHTML = `${getPlatformIcon(
        item.platform
    )} ${getPlatformName(item.platform)}`;
        document.getElementById("view-playlist").textContent = item.playlist;
        document.getElementById("view-territory").textContent = `${getTerritoryFlag(
        item.territory
    )} ${getTerritoryName(item.territory)}`;
        document.getElementById("view-date").textContent = formatDate(item.date);
        document.getElementById("view-link").href = item.link;

        elements.viewModal.show();
    }

    // Modal de Edição
    function openEditModal(item) {
        document.getElementById("edit-stats-body").innerHTML = `
        <form id="edit-stats-form">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Música</label>
                        <input type="text" class="form-control" id="edit-track" value="${item.track
        }" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Artista</label>
                        <input type="text" class="form-control" id="edit-artist" value="${item.artist
        }" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Streams</label>
                        <input type="number" class="form-control" id="edit-streams" value="${item.streams
        }" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Plataforma</label>
                        <select class="form-select" id="edit-platform" required>
                            <option value="spotify" ${item.platform === "spotify" ? "selected" : ""
        }>Spotify</option>
                            <option value="apple" ${item.platform === "apple" ? "selected" : ""
        }>Apple Music</option>
                            <option value="deezer" ${item.platform === "deezer" ? "selected" : ""
        }>Deezer</option>
                            <option value="youtube" ${item.platform === "youtube" ? "selected" : ""
        }>YouTube</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Playlist</label>
                        <input type="text" class="form-control" id="edit-playlist" value="${item.playlist
        }">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Território</label>
                        <select class="form-select" id="edit-territory" required>
                            <option value="AO" ${item.territory === "AO" ? "selected" : ""
        }>Angola</option>
                            <option value="PT" ${item.territory === "PT" ? "selected" : ""
        }>Portugal</option>
                            <option value="BR" ${item.territory === "BR" ? "selected" : ""
        }>Brasil</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">URL da Capa</label>
                <input type="url" class="form-control" id="edit-cover" value="${item.cover
        }">
            </div>
            <div class="mb-3">
                <label class="form-label">Link</label>
                <input type="url" class="form-control" id="edit-link" value="${item.link
        }">
            </div>
        </form>
    `;

        elements.editModal.show();
    }

    // Filtros
    function applyFilters() {
        const filters = {
            id: elements.searchInputs.id.value,
            account: elements.searchInputs.account.value.toLowerCase(),
            track: elements.searchInputs.track.value.toLowerCase(),
            artist: elements.searchInputs.artist.value.toLowerCase(),
            playlist: elements.searchInputs.playlist.value,
            territory: elements.searchInputs.territory.value,
        };

        const filtered = statsData.filter((item) => {
            return (
                (!filters.id || item.id.toString().includes(filters.id)) &&
                (!filters.account ||
                    item.account.toLowerCase().includes(filters.account)) &&
                (!filters.track || item.track.toLowerCase().includes(filters.track)) &&
                (!filters.artist || item.artist.toLowerCase().includes(filters.artist)) &&
                (!filters.playlist || item.playlist === filters.playlist) &&
                (!filters.territory || item.territory === filters.territory)
            );
        });

        currentPage = 1;
        renderStats(filtered);
    }

    // Paginação
    function renderPagination(totalItems) {
        elements.pagination.innerHTML = "";
        const totalPages = Math.ceil(totalItems / itemsPerPage);

        // Botão Anterior
        const prevLi = document.createElement("li");
        prevLi.className = `page-item ${currentPage === 1 ? "disabled" : ""}`;
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage - 1
        }">Anterior</a>`;
        elements.pagination.appendChild(prevLi);

        // Páginas
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement("li");
            li.className = `page-item ${i === currentPage ? "active" : ""}`;
            li.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
            elements.pagination.appendChild(li);
        }

        // Botão Próximo
        const nextLi = document.createElement("li");
        nextLi.className = `page-item ${currentPage === totalPages ? "disabled" : ""
        }`;
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${currentPage + 1
        }">Próximo</a>`;
        elements.pagination.appendChild(nextLi);

        // Eventos
        document.querySelectorAll(".page-link").forEach((link) => {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                const page = parseInt(link.dataset.page);
                if (!isNaN(page) && page >= 1 && page <= totalPages) {
                    currentPage = page;
                    applyFilters();
                }
            });
        });
    }

    // Event Listeners
    function setupEventListeners() {
        // Filtros
        Object.values(elements.searchInputs).forEach((input) => {
            input.addEventListener("input", applyFilters);
        });

        // Limpar filtros
        elements.clearBtn.addEventListener("click", () => {
            Object.values(elements.searchInputs).forEach((input) => {
                if (input.tagName === "SELECT") {
                    input.value = "";
                } else {
                    input.value = "";
                }
            });
            currentPage = 1;
            renderStats(statsData);
        });

        // Salvar edição
        elements.saveBtn.addEventListener("click", () => {
            // Implemente a lógica para salvar
            alert(`Dados ${currentEditingId} salvos com sucesso!`);
            elements.editModal.hide();
        });

        // Confirmar exclusão
        elements.confirmDeleteBtn.addEventListener("click", () => {
            if (!elements.deletePassword.value) {
                elements.deletePassword.classList.add("is-invalid");
                return;
            }

            // Implemente a lógica para excluir
            alert(`Dados ${currentEditingId} excluídos com sucesso!`);
            elements.deleteModal.hide();
            elements.deletePassword.value = "";
            elements.deletePassword.classList.remove("is-invalid");
        });
    }
    </script>
    <script src="../../../js/lastest.js"></script>
</body>

</html>