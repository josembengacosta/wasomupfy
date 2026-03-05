<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wasom Upfy · Novidades frescas</title>
  <!-- Bootstrap 5 + Font Awesome 6 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    /* ========== DARK MODE PREMIUM (mesmo dos anteriores) ========== */
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

    /* ===== BREAKING NEWS TICKER ===== */
    .ticker-wrapper {
      background: #100b18;
      border-top: 2px solid #572e8a;
      border-bottom: 2px solid #2a1a3a;
      padding: 0.75rem 0;
      margin-top: 16px;
      margin-bottom: 30px;
      box-shadow: 0 -3px 10px rgba(106, 13, 173, 0.2);
    }
    .ticker-container {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }
    .ticker-badge {
      background: #bb3b3b;
      color: white;
      font-weight: 700;
      padding: 0.5rem 1.5rem;
      border-radius: 40px;
      font-size: 0.9rem;
      letter-spacing: 1px;
      white-space: nowrap;
      box-shadow: 0 0 15px #ff5e5e;
      animation: pulseGlow 1.5s infinite;
    }
    @keyframes pulseGlow {
      0% { box-shadow: 0 0 5px #ff5555; }
      50% { box-shadow: 0 0 20px #ff3838; }
      100% { box-shadow: 0 0 5px #ff5555; }
    }
    .ticker-content {
      overflow: hidden;
      white-space: nowrap;
      width: 100%;
    }
    .ticker-scroll {
      display: inline-block;
      animation: tickerMove 25s linear infinite;
      font-size: 1.2rem;
      font-weight: 500;
      color: #ffb86b;
    }
    .ticker-scroll span {
      margin-right: 3rem;
    }
    .ticker-scroll i {
      color: #ff7b7b;
      margin-right: 0.4rem;
    }
    @keyframes tickerMove {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }

    /* ===== TIMELINE ESTILO ===== */
    .timeline-title {
      font-size: 2.8rem;
      font-weight: 800;
      background: linear-gradient(to right, #fbd9ff, #f6b5ff);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }
    .timeline-item {
      display: flex;
      gap: 1.8rem;
      padding: 2rem 0;
      border-bottom: 1px solid #2a2a2a;
      transition: background 0.2s;
    }
    .timeline-item:hover {
      background: #111111;
      border-radius: 40px;
      padding-left: 1.5rem;
      padding-right: 1.5rem;
    }
    .timeline-date-box {
      min-width: 100px;
      text-align: center;
      background: #1b1422;
      border-radius: 40px;
      padding: 1rem 0.5rem;
      height: fit-content;
      border: 1px solid #4d2e77;
      box-shadow: 0 4px 0 #2f1d4a;
    }
    .timeline-day {
      font-size: 2.4rem;
      font-weight: 800;
      line-height: 1;
      color: #dbbaff;
    }
    .timeline-month {
      font-weight: 600;
      color: #c28dff;
      text-transform: uppercase;
      font-size: 0.9rem;
    }
    .timeline-content {
      flex: 1;
    }
    .timeline-badge-fire {
      background: #e05a00;
      color: white;
      font-size: 0.8rem;
      padding: 0.3rem 1.2rem;
      border-radius: 30px;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      margin-bottom: 0.75rem;
      border: 1px solid #ffae42;
    }
    .timeline-content h3 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    .timeline-content p {
      color: #b0b0b0;
      font-size: 1.1rem;
    }
    .timeline-link {
      color: #c394ff;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .timeline-link:hover {
      color: #eac3ff;
      text-decoration: underline;
    }

    /* sidebar widgets (mantidos) */
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

<!-- BREAKING NEWS TICKER (letreiro digital) -->
<div class="ticker-wrapper">
  <div class="container ticker-container">
    <div class="ticker-badge"><i class="fas fa-bolt me-1"></i> BREAKING</div>
    <div class="ticker-content">
      <div class="ticker-scroll">
        <span><i class="fas fa-fire"></i> Boomplay Angola agora paga 30% mais por stream</span>
        <span><i class="fas fa-fire"></i> Preto Show é o novo embaixador Wasom Upfy</span>
        <span><i class="fas fa-fire"></i> Painel de artista: novo módulo de royalties em tempo real</span>
        <span><i class="fas fa-fire"></i> Parceria com a SAD: registo gratuito para os primeiros 50 artistas</span>
        <span><i class="fas fa-fire"></i> Spotify adiciona categoria "Kuduro Antigo" curada por nós</span>
        <!-- repetido para fluidez -->
        <span><i class="fas fa-fire"></i> Boomplay Angola agora paga 30% mais por stream</span>
        <span><i class="fas fa-fire"></i> Preto Show é o novo embaixador Wasom Upfy</span>
      </div>
    </div>
  </div>
</div>

<main class="container">
  <!-- cabeçalho da página (categoria novidades) -->
  <div class="d-flex align-items-center gap-3 mb-4">
    <i class="fas fa-newspaper fa-3x" style="color: #c48aff;"></i>
    <h1 class="timeline-title mb-0">Novidades da cena musical</h1>
  </div>
  <p class="fs-5 text-secondary mb-5">Fica por dentro de novos lançamentos, lojas, features no painel e artistas que chegaram à família Wasom Upfy.</p>

  <!-- TIMELINE (2 colunas: esquerda timeline + sidebar) -->
  <div class="row g-5">
    <div class="col-lg-8">
      <!-- ITEM 1 (timeline) -->
      <div class="timeline-item">
        <div class="timeline-date-box">
          <div class="timeline-day">15</div>
          <div class="timeline-month">ABR 2025</div>
        </div>
        <div class="timeline-content">
          <span class="timeline-badge-fire"><i class="fas fa-fire"></i> NOVA LOJA</span>
          <h3>Apple Music adiciona curadoria "Kizomba Premium"</h3>
          <p>A Wasom Upfy fechou parceria direta com a Apple Music para uma playlist editorial dedicada à kizomba angolana. Se tens músicas do género, actualiza o teu catálogo e submete via painel.</p>
          <a href="#" class="timeline-link">Saber mais <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <!-- ITEM 2 -->
      <div class="timeline-item">
        <div class="timeline-date-box">
          <div class="timeline-day">12</div>
          <div class="timeline-month">ABR 2025</div>
        </div>
        <div class="timeline-content">
          <span class="timeline-badge-fire"><i class="fas fa-fire"></i> NOVO ARTISTA</span>
          <h3>Anna Joyce junta-se à Wasom Upfy para distribuição global</h3>
          <p>A estrela da kizamba e semba agora usa a nossa plataforma. O novo single "Luz do Lua" será lançado em exclusivo com distribuição nossa no dia 20 de Abril. Espera streams.</p>
          <a href="#" class="timeline-link">Ver perfil do artista <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <!-- ITEM 3 -->
      <div class="timeline-item">
        <div class="timeline-date-box">
          <div class="timeline-day">10</div>
          <div class="timeline-month">ABR 2025</div>
        </div>
        <div class="timeline-content">
          <span class="timeline-badge-fire"><i class="fas fa-fire"></i> ACTUALIZAÇÃO PAINEL</span>
          <h3>Novo módulo "Royalties em tempo real"</h3>
          <p>Agora podes ver quanto estás a ganhar por stream, por loja e até estimativas de execução pública. O painel foi reformulado e inclui gráficos interativos. Atualiza já a tua dashboard.</p>
          <a href="#" class="timeline-link">Experimentar <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <!-- ITEM 4 -->
      <div class="timeline-item">
        <div class="timeline-date-box">
          <div class="timeline-day">08</div>
          <div class="timeline-month">ABR 2025</div>
        </div>
        <div class="timeline-content">
          <span class="timeline-badge-fire"><i class="fas fa-fire"></i> NOVA LOJA</span>
          <h3>Deezer lança programa "Kuduro First" com Wasom Upfy</h3>
          <p>Os artistas de kuduro da nossa plataforma terão prioridade no algoritmo e selo de qualidade. As submissões já estão abertas na aba "oportunidades" do teu painel.</p>
          <a href="#" class="timeline-link">Submeter agora <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <!-- ITEM 5 -->
      <div class="timeline-item">
        <div class="timeline-date-box">
          <div class="timeline-day">05</div>
          <div class="timeline-month">ABR 2025</div>
        </div>
        <div class="timeline-content">
          <span class="timeline-badge-fire"><i class="fas fa-fire"></i> NOVO ARTISTA</span>
          <h3>Pai Diesel assina connosco e prepara mixtape</h3>
          <p>O ícone do kuduro pesado, Pai Diesel, agora usa a Wasom Upfy. O primeiro lançamento "Batida 10" chega a 25 de Abril. Fica atento às playlists.</p>
          <a href="#" class="timeline-link">Pré-save <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <!-- ITEM 6 -->
      <div class="timeline-item">
        <div class="timeline-date-box">
          <div class="timeline-day">02</div>
          <div class="timeline-month">ABR 2025</div>
        </div>
        <div class="timeline-content">
          <span class="timeline-badge-fire"><i class="fas fa-fire"></i> ACTUALIZAÇÃO</span>
          <h3>Integração com TikTok Music e novos filtros de áudio</h3>
          <p>Agora podes enviar masters directamente com metadados para o TikTok, além de ter acesso a sons em alta. A funcionalidade está disponível em "distribuir música".</p>
          <a href="#" class="timeline-link">Ver novidade <i class="fas fa-arrow-right"></i></a>
        </div>
      </div>

      <!-- ver mais (botão carregar) -->
      <div class="text-center my-5">
        <a href="#" class="btn btn-outline-light rounded-pill px-5 py-3"><i class="fas fa-history me-2"></i>Carregar mais novidades</a>
      </div>
    </div>

    <!-- SIDEBAR (direita) - mantemos o padrão -->
    <div class="col-lg-4">
      <div class="sidebar-widget search-form">
        <input type="text" placeholder="Pesquisar novidades...">
        <i class="fas fa-search"></i>
      </div>

      <div class="sidebar-widget">
        <h5 class="mb-3"><i class="fas fa-chart-line me-2" style="color:#b87aff;"></i> Artistas em alta esta semana</h5>
        <div class="d-flex flex-column gap-3">
          <div><i class="fas fa-crown me-2 text-warning"></i> Preto Show (estreia)</div>
          <div><i class="fas fa-fire me-2 text-danger"></i> Anna Joyce · +245% streams</div>
          <div><i class="fas fa-fire me-2 text-danger"></i> Pai Diesel · novo single</div>
          <div><i class="fas fa-music me-2 text-info"></i> Dj Butina</div>
          <div><i class="fas fa-music me-2 text-info"></i> Bruna Tatiana</div>
        </div>
      </div>

      <div class="magic-banner sidebar-widget">
        <i class="fas fa-wand-magic-sparkles fa-2x mb-3" style="color: #c48aff;"></i>
        <h5>Serviços Personalizados</h5>
        <p>Masterização, pitching, artwork. Tudo para o teu lançamento brilhar.</p>
        <a href="#" class="btn btn-light rounded-pill w-100" style="background: #ac73ff;">Quero saber mais</a>
      </div>

      <div class="giveaway-card sidebar-widget">
        <span class="badge bg-warning text-dark mb-2"><i class="fas fa-gift"></i> Passatempo ativo</span>
        <h5><i class="fas fa-trophy me-2" style="color: #f5c542;"></i>Ganha uma Masterização Grátis</h5>
        <p class="small">Envia a tua faixa inédita até 30/04. O vencedor leva master + distribuição premium.</p>
        <a href="#" class="stretched-link text-decoration-none" style="color:#d9b3ff;">Participa aqui →</a>
      </div>
    </div>
  </div>
</main>

<!-- FOOTER PADRÃO (4 colunas) -->
<footer class="footer pt-5 pb-4">
  <div class="container">
    <div class="row justify-content-center mb-5">
      <div class="col-lg-6 text-center">
        <h4 class="fw-light">📬 Recebe dicas exclusivas e passatempos</h4>
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