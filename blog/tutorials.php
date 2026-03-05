<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wasom Upfy · Tutoriais para artistas</title>
  <!-- Bootstrap 5 + Font Awesome 6 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* ===== DARK MODE PREMIUM (mesmo padrão) ===== */
    body {
      background-color: #0c0c0c;
      color: #f0f0f0;
      font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    }

    /* navbar transparente → sólida */
    .navbar {
      background: transparent !important;
      backdrop-filter: blur(0);
      transition: background 0.3s ease, backdrop-filter 0.3s ease, box-shadow 0.2s;
      padding: 1.2rem 0;
    }
    .navbar-scrolled {
      background: #0c0c0c !important;
      backdrop-filter: blur(6px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.8);
      border-bottom: 1px solid rgba(138, 43, 226, 0.25);
    }
    .navbar .nav-link {
      color: #fff !important;
      font-weight: 500;
      margin: 0 0.75rem;
      opacity: 0.85;
      transition: 0.2s;
    }
    .navbar .nav-link:hover {
      opacity: 1;
      color: #c77dff !important;
    }
    .btn-cta {
      background: linear-gradient(145deg, #9b4dff, #7a2be0);
      border: none;
      color: white;
      font-weight: 600;
      padding: 0.6rem 1.6rem;
      border-radius: 40px;
      box-shadow: 0 4px 14px rgba(138, 43, 226, 0.5);
      transition: all 0.2s;
    }
    .btn-cta:hover {
      background: linear-gradient(145deg, #b366ff, #8a4bff);
      transform: scale(1.03);
      box-shadow: 0 8px 20px rgba(155, 77, 255, 0.7);
      color: white;
    }

    /* ===== CORES EDUCAÇÃO/CLAREZA ===== */
    .tutorial-header {
      background: #0e0a15;
      border-bottom: 2px solid #4b2c7a;
    }
    .section-title {
      font-size: 2.8rem;
      font-weight: 700;
      background: linear-gradient(135deg, #ffffff, #d0b2ff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      margin-bottom: 1rem;
    }
    .book-icon {
      color: #ad73ff;
      filter: drop-shadow(0 0 10px #9f5bff);
    }

    /* barra de progresso "Comece por aqui" */
    .starter-progress {
      background: #191122;
      border-radius: 60px;
      padding: 2rem 2.5rem;
      border: 1px solid #6b3faf;
      box-shadow: 0 10px 30px #140d1e;
      margin: 30px 0 50px;
    }
    .progress-step {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      color: #c0b0d0;
    }
    .progress-step.active {
      color: #dbbaff;
      font-weight: 600;
    }
    .step-badge {
      width: 40px;
      height: 40px;
      background: #2e2142;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid #5a3b85;
    }
    .active .step-badge {
      background: #9b4dff;
      border-color: #dbbaff;
      color: white;
    }
    .progress-line {
      height: 4px;
      background: #322a40;
      flex: 1;
      border-radius: 4px;
      position: relative;
    }
    .progress-line::after {
      content: '';
      position: absolute;
      height: 4px;
      background: #b47aff;
      width: 40%; /* progresso simbólico */
      border-radius: 4px;
    }

    /* cards de aprendizado */
    .tutorial-card {
      background: #151515;
      border: 1px solid #3d2b55;
      border-radius: 36px;
      padding: 0;
      overflow: hidden;
      transition: all 0.25s;
      height: 100%;
      box-shadow: 0 12px 20px -10px #000;
    }
    .tutorial-card:hover {
      transform: translateY(-8px);
      border-color: #a46eff;
      box-shadow: 0 30px 30px -18px #bd8aff;
    }
    .card-img-tutorial {
      height: 180px;
      background-size: cover;
      background-position: center;
      position: relative;
    }
    .difficulty-badge {
      position: absolute;
      top: 12px;
      right: 12px;
      padding: 0.3rem 1.2rem;
      border-radius: 30px;
      font-size: 0.8rem;
      font-weight: 600;
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(4px);
      border: 1px solid;
    }
    .badge-iniciante { border-color: #4bc0a5; color: #9effe6; }
    .badge-intermedio { border-color: #f5b342; color: #ffe7b3; }
    .badge-avancado { border-color: #ff6b8b; color: #ffc4d3; }
    .card-body {
      padding: 1.5rem 1.2rem 1.8rem;
    }
    .meta-info {
      display: flex;
      justify-content: space-between;
      color: #a0a0a0;
      font-size: 0.9rem;
      border-bottom: 1px dashed #3e2e4e;
      padding-bottom: 0.8rem;
      margin-bottom: 1rem;
    }
    .tutorial-title {
      font-weight: 700;
      font-size: 1.4rem;
      margin-bottom: 0.8rem;
    }
    .btn-tutorial {
      background: transparent;
      border: 2px solid #7357a5;
      border-radius: 40px;
      padding: 0.6rem 1.5rem;
      color: white;
      font-weight: 500;
      transition: 0.2s;
    }
    .btn-tutorial:hover {
      background: #7357a5;
      color: black;
    }

    /* sidebar widgets (iguais) */
    .sidebar-widget {
      background: #161616;
      border-radius: 30px;
      padding: 1.8rem 1.5rem;
      margin-bottom: 2.5rem;
      border: 1px solid #2d2d2d;
    }
    .search-form {
      position: relative;
    }
    .search-form input {
      background: #232323;
      border: 1px solid #3a3a3a;
      border-radius: 60px;
      padding: 0.85rem 2rem;
      color: white;
      width: 100%;
    }
    .search-form input:focus {
      outline: 2px solid #b366ff;
    }
    .search-form i {
      position: absolute;
      right: 20px;
      top: 50%;
      transform: translateY(-50%);
      color: #b87aff;
    }
    .magic-banner {
      background: linear-gradient(145deg, #1f112b, #2a1738);
      border-radius: 32px;
      padding: 2rem 1.8rem;
      border: 1px solid #7d4ec7;
    }
    .giveaway-card {
      background: #1d1d1d;
      border-radius: 26px;
      padding: 1.5rem;
      border: 1px solid #513582;
    }
    .footer-icon-circle {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 42px;
      height: 42px;
      background: #232023;
      border-radius: 50%;
      color: #d9b3ff;
      font-size: 1.3rem;
      transition: 0.2s;
      border: 1px solid #5d427d;
      text-decoration: none;
    }
    .footer-icon-circle:hover {
      background: #7a4fc2;
      color: #fff;
      transform: scale(1.1);
    }
    footer {
      background: #0a0a0a;
      border-top: 2px solid #282138;
    }
    .newsletter-input {
      background: #1d1d1d;
      border: 1px solid #633b9c;
      border-radius: 60px;
      padding: 0.7rem 1.2rem;
      color: white;
    }
  </style>
</head>
<body>

<!-- CABEÇALHO PADRÃO -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand fw-bold fs-3" href="#">
      <span style="color: #c58aff;">Wasom</span><span style="color: white;">Upfy</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link" href="#">Início</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Planos</a></li>
        <li class="nav-item"><a class="nav-link active" href="#">Blog</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Contacto</a></li>
        <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
          <a class="btn btn-cta" href="#"><i class="fas fa-cloud-upload-alt me-2"></i>Distribuir Agora</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div style="height: 96px;"></div>

<!-- CONTEÚDO PRINCIPAL -->
<main class="container">
  <!-- Título com ícone de livro e chamada educacional -->
  <div class="d-flex align-items-center gap-3 mb-4">
    <i class="fas fa-book-open fa-3x book-icon"></i>
    <div>
      <h1 class="section-title mb-1">Tutoriais & Aprendizado</h1>
      <p class="text-secondary fs-5">Domina a distribuição, os royalties e o marketing. Aprende ao teu ritmo.</p>
    </div>
  </div>

  <!-- SECÇÃO "COMEÇA POR AQUI" (barra de progresso visual) -->
  <div class="starter-progress">
    <div class="row align-items-center g-4">
      <div class="col-lg-4">
        <h4 class="fw-bold"><i class="fas fa-route me-2" style="color: #cb9eff;"></i>Começa por aqui</h4>
        <p class="text-secondary mb-0">Novo na Wasom Upfy? Segue estes passos para lançar a tua primeira música.</p>
      </div>
      <div class="col-lg-8">
        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-4">
          <!-- passo 1 -->
          <div class="progress-step active">
            <span class="step-badge">1</span>
            <span class="d-none d-sm-inline">Criar conta</span>
          </div>
          <div class="progress-line d-none d-md-block"></div>
          <!-- passo 2 -->
          <div class="progress-step">
            <span class="step-badge">2</span>
            <span class="d-none d-sm-inline">Enviar música</span>
          </div>
          <div class="progress-line d-none d-md-block"></div>
          <!-- passo 3 -->
          <div class="progress-step">
            <span class="step-badge">3</span>
            <span class="d-none d-sm-inline">Registar na SAD</span>
          </div>
          <div class="progress-line d-none d-md-block"></div>
          <!-- passo 4 -->
          <div class="progress-step">
            <span class="step-badge">4</span>
            <span class="d-none d-sm-inline">Receber royalties</span>
          </div>
        </div>
        <!-- botão de atalho -->
        <div class="mt-3 text-end">
          <a href="#" class="btn btn-sm btn-outline-light rounded-pill px-4"><i class="fas fa-graduation-cap me-2"></i>Tutorial para iniciantes</a>
        </div>
      </div>
    </div>
  </div>

  <!-- grid principal: cards de aprendizado + sidebar -->
  <div class="row g-5">
    <!-- COLUNA ESQUERDA (cards tutoriais) -->
    <div class="col-lg-8">
      <div class="row g-4">
        <!-- card 1 - iniciante -->
        <div class="col-md-6">
          <div class="tutorial-card">
            <div class="card-img-tutorial" style="background-image: url('https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?q=80&w=2070&auto=format&fit=crop');">
              <span class="difficulty-badge badge-iniciante"><i class="fas fa-seedling me-1"></i>Iniciante</span>
            </div>
            <div class="card-body">
              <div class="meta-info">
                <span><i class="far fa-clock me-1"></i> 4 min leitura</span>
                <span><i class="fas fa-video me-1"></i> Tutorial</span>
              </div>
              <h3 class="tutorial-title">Como enviar a primeira música para o Spotify</h3>
              <p class="text-secondary">Passo a passo: formatos, metadata, capa e data de lançamento.</p>
              <a href="#" class="btn-tutorial w-100 text-center mt-2">Começar <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
          </div>
        </div>

        <!-- card 2 - intermédio -->
        <div class="col-md-6">
          <div class="tutorial-card">
            <div class="card-img-tutorial" style="background-image: url('https://images.unsplash.com/photo-1526478806333-5fd488125a2b?q=80&w=2070&auto=format&fit=crop');">
              <span class="difficulty-badge badge-intermedio"><i class="fas fa-chart-line me-1"></i>Intermédio</span>
            </div>
            <div class="card-body">
              <div class="meta-info">
                <span><i class="far fa-clock me-1"></i> 7 min leitura</span>
                <span><i class="fas fa-calculator me-1"></i> Planilha</span>
              </div>
              <h3 class="tutorial-title">Como calcular royalties de streaming em kwanza</h3>
              <p class="text-secondary">Entende as taxas, conversões e o que esperar de cada plataforma.</p>
              <a href="#" class="btn-tutorial w-100 text-center mt-2">Aprender <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
          </div>
        </div>

        <!-- card 3 - iniciante -->
        <div class="col-md-6">
          <div class="tutorial-card">
            <div class="card-img-tutorial" style="background-image: url('https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?q=80&w=2070&auto=format&fit=crop');">
              <span class="difficulty-badge badge-iniciante"><i class="fas fa-seedling me-1"></i>Iniciante</span>
            </div>
            <div class="card-body">
              <div class="meta-info">
                <span><i class="far fa-clock me-1"></i> 3 min leitura</span>
                <span><i class="fas fa-file-alt me-1"></i> Guia</span>
              </div>
              <h3 class="tutorial-title">Como criar artwork profissional com o telemóvel</h3>
              <p class="text-secondary">Apps gratuitas, dimensões oficiais e exemplos para Kuduro/Kizomba.</p>
              <a href="#" class="btn-tutorial w-100 text-center mt-2">Ver dicas <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
          </div>
        </div>

        <!-- card 4 - avançado -->
        <div class="col-md-6">
          <div class="tutorial-card">
            <div class="card-img-tutorial" style="background-image: url('https://images.unsplash.com/photo-1511376777868-611b54f68947?q=80&w=2070&auto=format&fit=crop');">
              <span class="difficulty-badge badge-avancado"><i class="fas fa-dragon me-1"></i>Avançado</span>
            </div>
            <div class="card-body">
              <div class="meta-info">
                <span><i class="far fa-clock me-1"></i> 12 min leitura</span>
                <span><i class="fas fa-code-branch me-1"></i> Estratégia</span>
              </div>
              <h3 class="tutorial-title">Pitching para playlists internacionais (Kuduro)</h3>
              <p class="text-secondary">Técnicas de curadoria, contactos e como aumentar as chances de selo.</p>
              <a href="#" class="btn-tutorial w-100 text-center mt-2">Mergulhar <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
          </div>
        </div>

        <!-- card 5 - intermédio -->
        <div class="col-md-6">
          <div class="tutorial-card">
            <div class="card-img-tutorial" style="background-image: url('https://images.unsplash.com/photo-1501612785327-c8904665d422?q=80&w=2070&auto=format&fit=crop');">
              <span class="difficulty-badge badge-intermedio"><i class="fas fa-chart-line me-1"></i>Intermédio</span>
            </div>
            <div class="card-body">
              <div class="meta-info">
                <span><i class="far fa-clock me-1"></i> 5 min leitura</span>
                <span><i class="fas fa-microphone-alt me-1"></i> Áudio</span>
              </div>
              <h3 class="tutorial-title">Masterização caseira: parâmetros para Kizomba</h3>
              <p class="text-secondary">Ajustes de EQ, compressão e loudness para um som quente e moderno.</p>
              <a href="#" class="btn-tutorial w-100 text-center mt-2">Ouvir exemplos <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
          </div>
        </div>

        <!-- card 6 - avançado -->
        <div class="col-md-6">
          <div class="tutorial-card">
            <div class="card-img-tutorial" style="background-image: url('https://images.unsplash.com/photo-1459499362902-55a20553e082?q=80&w=2074&auto=format&fit=crop');">
              <span class="difficulty-badge badge-avancado"><i class="fas fa-dragon me-1"></i>Avançado</span>
            </div>
            <div class="card-body">
              <div class="meta-info">
                <span><i class="far fa-clock me-1"></i> 8 min leitura</span>
                <span><i class="fas fa-coins me-1"></i> Finanças</span>
              </div>
              <h3 class="tutorial-title">Declarar royalties no IRS em Angola</h3>
              <p class="text-secondary">Guia para artistas, produtores: como declarar e pagar menos impostos legalmente.</p>
              <a href="#" class="btn-tutorial w-100 text-center mt-2">Consultar <i class="fas fa-arrow-right ms-1"></i></a>
            </div>
          </div>
        </div>
      </div>

      <!-- paginação simples -->
      <nav class="mt-5 d-flex justify-content-center">
        <ul class="pagination" style="--bs-pagination-color: #b87aff;">
          <li class="page-item disabled"><span class="page-link bg-transparent text-secondary">«</span></li>
          <li class="page-item active"><span class="page-link" style="background:#9b4dff; border:none;">1</span></li>
          <li class="page-item"><a class="page-link bg-transparent text-light" href="#">2</a></li>
          <li class="page-item"><a class="page-link bg-transparent text-light" href="#">3</a></li>
          <li class="page-item"><a class="page-link bg-transparent text-light" href="#">Próximo</a></li>
        </ul>
      </nav>
    </div>

    <!-- SIDEBAR DIREITA (padrão blog) -->
    <div class="col-lg-4">
      <div class="sidebar-widget search-form">
        <input type="text" placeholder="Pesquisar tutoriais...">
        <i class="fas fa-search"></i>
      </div>

      <!-- widget com níveis de dificuldade (resumo) -->
      <div class="sidebar-widget">
        <h5><i class="fas fa-signal me-2" style="color:#b87aff;"></i>Níveis</h5>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <span class="badge bg-dark border border-success px-3 py-2"><i class="fas fa-seedling text-success me-1"></i>Iniciante (8)</span>
          <span class="badge bg-dark border border-warning px-3 py-2"><i class="fas fa-chart-line text-warning me-1"></i>Intermédio (5)</span>
          <span class="badge bg-dark border border-danger px-3 py-2"><i class="fas fa-dragon text-danger me-1"></i>Avançado (4)</span>
        </div>
      </div>

      <div class="magic-banner sidebar-widget">
        <i class="fas fa-wand-magic-sparkles fa-2x mb-3" style="color: #c48aff;"></i>
        <h5>Mentoria personalizada</h5>
        <p>Sessões 1:1 com especialistas da indústria para acelerar a tua carreira.</p>
        <a href="#" class="btn btn-light rounded-pill w-100" style="background: #ac73ff;">Reservar agora</a>
      </div>

      <div class="giveaway-card sidebar-widget">
        <span class="badge bg-warning text-dark mb-2"><i class="fas fa-gift"></i> Quiz</span>
        <h5><i class="fas fa-question-circle me-2" style="color: #f5c542;"></i>Que nível de conhecimento tens?</h5>
        <p class="small">Responde a 5 perguntas e descobre tutoriais recomendados para ti.</p>
        <a href="#" class="stretched-link text-decoration-none" style="color:#d9b3ff;">Fazer quiz →</a>
      </div>
    </div>
  </div>
</main>

<!-- FOOTER PADRÃO (4 colunas) -->
<footer class="footer pt-5 pb-4">
  <div class="container">
    <div class="row justify-content-center mb-5">
      <div class="col-lg-6 text-center">
        <h4 class="fw-light">📬 Recebe novos tutoriais no e-mail</h4>
        <div class="d-flex flex-column flex-sm-row gap-2 mt-3">
          <input type="email" class="form-control newsletter-input" placeholder="O teu melhor e-mail">
          <button class="btn btn-cta px-4">Inscrever</button>
        </div>
      </div>
    </div>
    <div class="row g-4">
      <div class="col-md-3">
        <h5><i class="fas fa-circle-info me-2" style="color:#b87aff;"></i> Sobre Nós</h5>
        <ul class="list-unstyled mt-3">
          <li><a href="#" class="text-secondary-50"><i class="fas fa-chevron-right me-1 fa-xs"></i>Quem somos</a></li>
          <li><a href="#" class="text-secondary-50"><i class="fas fa-chevron-right me-1 fa-xs"></i>Carreiras</a></li>
          <li><a href="#" class="text-secondary-50"><i class="fas fa-award me-1"></i>Nossa Marca</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h5><i class="fas fa-headset me-2"></i> Suporte</h5>
        <ul class="list-unstyled mt-3">
          <li><a href="#">Centro de ajuda</a></li>
          <li><a href="#">Fale connosco</a></li>
          <li><a href="#"><i class="fas fa-gem me-1"></i>Planos e preços</a></li>
        </ul>
      </div>
      <div class="col-md-3">
        <h5><i class="fas fa-envelope me-2"></i> Contacto</h5>
        <ul class="list-unstyled mt-3">
          <li><i class="fas fa-phone-alt me-2"></i>+244 923 456 789</li>
          <li><i class="fas fa-map-pin me-2"></i>Luanda, Talatona</li>
          <li><i class="fas fa-at me-2"></i>blog@wasomupfy.ao</li>
        </ul>
      </div>
      <div class="col-md-3">
        <h5><i class="fas fa-share-alt me-2"></i> Redes</h5>
        <div class="d-flex gap-2 mt-3 flex-wrap">
          <a href="#" class="footer-icon-circle"><i class="fab fa-instagram"></i></a>
          <a href="#" class="footer-icon-circle"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="footer-icon-circle"><i class="fab fa-x-twitter"></i></a>
          <a href="#" class="footer-icon-circle"><i class="fab fa-tiktok"></i></a>
          <a href="#" class="footer-icon-circle"><i class="fab fa-youtube"></i></a>
        </div>
        <p class="mt-4 small text-secondary">© 2025 Wasom Upfy – distribuição global com raízes angolanas.</p>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.addEventListener('scroll', function() {
    const nav = document.getElementById('mainNav');
    if (window.scrollY > 50) {
      nav.classList.add('navbar-scrolled');
    } else {
      nav.classList.remove('navbar-scrolled');
    }
  });
</script>
</body>
</html>