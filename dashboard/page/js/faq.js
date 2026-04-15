// ══════════════════════════════════════════════════════
// WASOM UPFY v2.0 — FAQ JavaScript
// Arquivo: assets/js/faq.js
// ══════════════════════════════════════════════════════

// ── i18n config ──────────────────────────────────────
const enableI18n = false; // true para activar selector de idioma na UI

const translations = {
  "pt-AO": {
    faq_title: "Perguntas Frequentes",
    faq_description:
      "Encontra respostas para as perguntas mais comuns sobre a plataforma Wasom Upfy.<br>Não encontraste o que procuravas? <a href='support' class='text-white fw-bold'>Entra em contacto com o suporte!</a>",
    faq_update_date: "Última actualização: 11 de Março de 2026",
    download_pdf: "Descarregar em PDF",
    print: "Imprimir",
    search_placeholder: "Pesquisar perguntas...",
    index_title: "Índice",
    tips_title: "Dicas Rápidas",
    tutorial_title: "Assiste ao Nosso Tutorial",
    watch_video: "Ver Vídeo",
    tutorial_modal_title: "Tutorial Wasom Upfy",
    close: "Fechar",
    // Categories
    cat_all: "Todas",
    cat_conta: "Conta",
    cat_lancamentos: "Lançamentos",
    cat_financeiro: "Financeiro",
    cat_artistas: "Artistas",
    cat_estatisticas: "Estatísticas",
    cat_youtube: "YouTube",
    cat_planos: "Planos",
    cat_suporte: "Suporte",
    // Tips
    tip1: "Usa os filtros de data para comparar estatísticas entre períodos rapidamente.",
    tip2: "Activa notificações para novos streams nas Configurações.",
    tip3: "Exporta os dados em CSV na secção de estatísticas para análise detalhada.",
    // FAQ questions
    faq1_question:  "Como cadastrar um novo artista?",
    faq2_question:  "Como actualizar os meus dados de perfil?",
    faq3_question:  "O que fazer se esquecer a palavra-passe?",
    faq4_question:  "Como activar a autenticação de dois factores (2FA)?",
    faq5_question:  "A minha conta foi suspensa. O que devo fazer?",
    faq6_question:  "Como funciona o modo escuro?",
    faq7_question:  "Como criar um novo lançamento?",
    faq8_question:  "Quais formatos de áudio são aceites?",
    faq9_question:  "Qual é o requisito mínimo para a capa?",
    faq10_question: "Quanto tempo demora a distribuição?",
    faq11_question: "Posso editar um lançamento após o envio?",
    faq12_question: "Como agendar uma data de lançamento?",
    faq13_question: "Como ver o meu saldo disponível?",
    faq14_question: "Como efectuar um levantamento?",
    faq15_question: "Qual é o valor mínimo para levantamento?",
    faq16_question: "Como funcionam os royalties?",
    faq17_question: "Quando recebo os pagamentos de royalties?",
    faq18_question: "Como funciona a divisão de royalties entre colaboradores?",
    faq19_question: "Posso ter vários artistas na mesma conta?",
    faq20_question: "Como adicionar um colaborador à minha conta?",
    faq21_question: "Como vincular redes sociais ao perfil do artista?",
    faq22_question: "Como ver as estatísticas das minhas músicas?",
    faq23_question: "Que plataformas aparecem nas estatísticas?",
    faq24_question: "Como exportar os dados de estatísticas?",
    faq25_question: "Com que frequência as estatísticas são actualizadas?",
    faq26_question: "O que é a unificação de canal YouTube?",
    faq27_question: "Como verificar o meu canal YouTube?",
    faq28_question: "O que é um Art Track?",
    faq29_question: "Quais planos estão disponíveis?",
    faq30_question: "Posso mudar de plano?",
    faq31_question: "Como activar o meu plano após o pagamento?",
    faq32_question: "Como enviar um pedido de suporte?",
    faq33_question: "Qual é o prazo de resposta do suporte?",
    faq34_question: "Posso solicitar um reembolso?",
    // FAQ answers
    faq1_answer:
      "Acede à secção Artistas no menu, clica em Adicionar Novo e preenche os dados — nome artístico, nome real, foto, bio e contactos. Após rever, guarda as alterações. O processo leva poucos minutos. Certifica-te de que os dados estão correctos para evitar problemas futuros.",
    faq2_answer:
      "Vai a Meu Perfil no menu do utilizador. Podes actualizar nome, foto, e-mail e palavra-passe. Após editar, clica em Guardar alterações. Algumas alterações como o e-mail podem requerer confirmação adicional.",
    faq3_answer:
      "Vai à página de login, clica em Esqueceu a palavra-passe? e segue as instruções. Receberás um e-mail com link para criar nova palavra-passe. Verifica a pasta spam se necessário. O link expira em 30 minutos.",
    faq4_answer:
      "Acede a Configurações → Segurança e activa Autenticação de dois factores. Será gerado um código QR para ligar ao teu autenticador (Google Authenticator, Authy, etc.). Cada login pedirá o código do autenticador além da palavra-passe.",
    faq5_answer:
      "Contas são suspensas por violação dos Termos de Uso ou actividade suspeita. Se acreditas que foi um erro, envia um pedido de suporte explicando a situação. A equipa irá rever e responder em até 48 horas. Não cries uma nova conta enquanto o processo está em análise.",
    faq6_answer:
      "Clica no ícone de sol/lua na barra de navegação para alternar entre modo claro e escuro. A preferência é guardada automaticamente. Podes também definir o tema nas Configurações.",
    faq7_answer:
      "Vai a Lançamentos → Novo Lançamento. Preenche o título, artista, género e data. Faz upload do ficheiro de áudio (WAV ou FLAC recomendado) e da capa (mínimo 3000×3000 px). Revê e confirma. O lançamento é processado em até 72 horas.",
    faq8_answer:
      "Formatos aceites: WAV (recomendado — 16 ou 24 bits, 44,1 kHz), FLAC (sem perdas), AIFF (compatível Apple) e MP3 a 320 kbps (menos recomendado). Tamanho máximo por ficheiro: 1 GB.",
    faq9_answer:
      "A capa deve ter no mínimo 3000×3000 pixels, formato quadrado (1:1), em JPG ou PNG com qualidade máxima. Não deve conter logótipos de lojas, URLs ou informações de contacto. Uma capa de baixa qualidade pode resultar na rejeição do lançamento.",
    faq10_answer:
      "Após aprovação interna (até 72h), o lançamento é enviado às plataformas. Spotify e Apple Music geralmente em 3–7 dias, outras em até 14 dias. Recomendamos submeter com pelo menos 2 semanas de antecedência.",
    faq11_answer:
      "Enquanto o lançamento está em rascunho ou em revisão, podes editar livremente. Após ser distribuído, título e artista não podem ser alterados pois já estão nas plataformas. Para alterações urgentes, contacta o suporte.",
    faq12_answer:
      "Durante a criação do lançamento, no campo Data de lançamento, selecciona uma data futura. Para que a distribuição esteja completa na data desejada, submete com pelo menos 2 semanas de antecedência.",
    faq13_answer:
      "O teu saldo aparece no topo de Finanças → Visão Geral. É dividido em saldo disponível (pronto para levantamento) e saldo pendente (em processamento). O histórico está em Finanças → Transacções.",
    faq14_answer:
      "Vai a Finanças → Levantamentos, escolhe o método (IBAN, Express, PayPal), introduz o valor e confirma a palavra-passe. Receberás um e-mail de confirmação. Prazo de processamento: 3 a 5 dias úteis.",
    faq15_answer:
      "O valor mínimo para levantamento é de 1.000 AOA. Consulta a página Conta e serviços disponíveis para ver os limites mensais do teu plano.",
    faq16_answer:
      "A Wasom Upfy distribui 90% dos royalties directamente ao artista. Os restantes 10% cobrem custos de distribuição e operação. Os royalties são calculados com base nos streams/downloads em cada plataforma e actualizados mensalmente.",
    faq17_answer:
      "Os royalties são processados e creditados na tua carteira até ao dia 15 de cada mês, referentes ao mês anterior. Algumas plataformas têm atraso de 2–3 meses nos relatórios. Receberás uma notificação quando o saldo for actualizado.",
    faq18_answer:
      "Vai a Finanças → Visão Geral → Divisão de Royalties. Podes configurar percentagens para cada colaborador por lançamento ou álbum. O sistema divide automaticamente. A soma das percentagens deve ser sempre 100%.",
    faq19_answer:
      "Sim. Dependendo do teu plano, podes criar múltiplos artistas na mesma conta. O plano Label tem número ilimitado de artistas, enquanto Artist e Album têm limites. Consulta os detalhes em Conta e serviços.",
    faq20_answer:
      "Vai a Gestão de Conta → Colaboradores e clica em Convidar Colaborador. Introduz o e-mail e define as permissões (visualização, edição, finanças). A pessoa receberá um convite por e-mail.",
    faq21_answer:
      "Vai a Artistas → [nome do artista] → Editar Perfil. Na secção redes sociais, podes adicionar links para Instagram, Facebook, YouTube, Spotify, Apple Music, TikTok e website.",
    faq22_answer:
      "Acede a Estatísticas no menu principal. Filtra por artista, álbum, faixa, período e plataforma. Os dados são apresentados em gráficos e tabelas. Clica num artista para ver detalhes completos incluindo países e playlists.",
    faq23_answer:
      "As estatísticas incluem: Spotify, Apple Music, YouTube Music, Deezer, Tidal, Amazon Music, Boomplay, TikTok, iTunes e outras lojas. Nem todas as plataformas reportam em tempo real — algumas têm atraso de 24–72 horas.",
    faq24_answer:
      "Em Estatísticas → Exportar, selecciona período, artistas e plataformas. Podes exportar em CSV para análise em Excel ou em PDF como relatório formatado. Relatórios completos pré-gerados estão em Estatísticas → Relatórios.",
    faq25_answer:
      "As estatísticas são actualizadas diariamente. O Spotify e Apple Music fornecem dados com até 1 dia de atraso. Boomplay e Amazon Music podem ter atraso de 3–5 dias. Os totais mensais são definitivos após o fecho do mês.",
    faq26_answer:
      "A unificação de canal permite ligar o teu canal YouTube à plataforma para sincronizar Art Tracks, acompanhar streams e receitas, gerir vídeos e detectar conteúdo de fãs. Disponível para todos os planos, sem custo adicional.",
    faq27_answer:
      "Vai a Artistas → Unificação YouTube → Registar Canal. Após submeter o URL, receberás um código WASOM-XXXXXXXX. Adiciona-o à descrição do canal YouTube e aguarda confirmação — demora até 48 horas.",
    faq28_answer:
      "Um Art Track é um vídeo automático criado pelo YouTube Music com a capa do lançamento como imagem estática e a música como áudio. Criado automaticamente quando distribuis pelo YouTube Music. Podes monetizá-lo através da unificação do canal.",
    faq29_answer:
      "A Wasom Upfy oferece quatro planos: Single (2.000 AOA por lançamento), Album (5.000 AOA por lançamento), Artist (11.400 AOA/mês) e Label (70.000 AOA/mês). Consulta Conta e serviços para detalhes completos.",
    faq30_answer:
      "Sim. Upgrade (plano superior): disponível imediatamente após pagamento. Downgrade (plano inferior): entra em vigor no final do ciclo actual. Contacta o suporte para iniciar a mudança.",
    faq31_answer:
      "Após efectuar o pagamento, faz upload do comprovante em Conta → Activar Plano. A equipa irá verificar e activar o plano em até 24 horas úteis. Receberás uma notificação por e-mail quando for activado.",
    faq32_answer:
      "Vai a Suporte, selecciona o tipo de problema, o nível de urgência e descreve o problema com detalhe. Podes anexar ficheiros até 10 MB cada. O limite é de 5 pedidos por hora. A equipa responde em até 48 horas úteis.",
    faq33_answer:
      "O suporte funciona de Segunda a Sexta 9h–18h (WAT) e Sábado 9h–13h. Tickets são respondidos em até 48 horas úteis. Tickets urgentes têm prioridade em até 24 horas.",
    faq34_answer:
      "Não. Todos os pagamentos efectuados à Wasom Upfy são definitivos e não reembolsáveis, independentemente da circunstância, conforme a nossa Política de Não Reembolso. A única excepção é uma cobrança duplicada por erro técnico comprovável da plataforma, que poderá ser analisada para crédito de conta (não reembolso monetário). Nesse caso, deves abrir um pedido de suporte com os comprovativos no prazo de 72 horas após a ocorrência. A análise não garante resultado favorável."
  },

  "en-US": {
    faq_title: "Frequently Asked Questions",
    faq_description:
      "Find answers to the most common questions about the Wasom Upfy platform.<br>Can't find what you're looking for? <a href='support' style='color:#fff;font-weight:bold'>Contact our support!</a>",
    faq_update_date: "Last updated: March 11, 2026",
    download_pdf: "Download PDF",
    print: "Print",
    search_placeholder: "Search questions...",
    index_title: "Index",
    tips_title: "Quick Tips",
    tutorial_title: "Watch Our Tutorial",
    watch_video: "Watch Video",
    tutorial_modal_title: "Wasom Upfy Tutorial",
    close: "Close",
    cat_all: "All",
    cat_conta: "Account",
    cat_lancamentos: "Releases",
    cat_financeiro: "Financial",
    cat_artistas: "Artists",
    cat_estatisticas: "Statistics",
    cat_youtube: "YouTube",
    cat_planos: "Plans",
    cat_suporte: "Support",
    tip1: "Use date filters to quickly compare statistics across periods.",
    tip2: "Enable stream notifications in Settings.",
    tip3: "Export your data as CSV in the statistics section.",
    faq1_question: "How do I register a new artist?",
    faq2_question: "How do I update my profile information?",
    faq3_question: "What if I forget my password?",
    faq4_question: "How do I enable two-factor authentication (2FA)?",
    faq5_question: "My account was suspended. What should I do?",
    faq6_question: "How does dark mode work?",
    faq7_question: "How do I create a new release?",
    faq8_question: "Which audio formats are accepted?",
    faq9_question: "What are the cover art requirements?",
    faq10_question: "How long does distribution take?",
    faq11_question: "Can I edit a release after submitting it?",
    faq12_question: "How do I schedule a release date?",
    faq13_question: "How do I check my available balance?",
    faq14_question: "How do I make a withdrawal?",
    faq15_question: "What is the minimum withdrawal amount?",
    faq16_question: "How do royalties work?",
    faq17_question: "When do I receive royalty payments?",
    faq18_question: "How does royalty splitting work between collaborators?",
    faq19_question: "Can I have multiple artists on the same account?",
    faq20_question: "How do I add a collaborator to my account?",
    faq21_question: "How do I link social networks to an artist profile?",
    faq22_question: "How do I view my music statistics?",
    faq23_question: "Which platforms appear in the statistics?",
    faq24_question: "How do I export statistics data?",
    faq25_question: "How often are statistics updated?",
    faq26_question: "What is YouTube channel unification?",
    faq27_question: "How do I verify my YouTube channel?",
    faq28_question: "What is an Art Track?",
    faq29_question: "What plans are available?",
    faq30_question: "Can I change my plan?",
    faq31_question: "How do I activate my plan after payment?",
    faq32_question: "How do I submit a support request?",
    faq33_question: "What is the support response time?",
    faq34_question: "How do I request a refund?",
    faq1_answer:  "Go to the Artists section in the menu, click Add New, and fill in the details — stage name, real name, photo, bio, and contacts. Review and save changes.",
    faq2_answer:  "Go to My Profile in the user menu. You can update your name, photo, email, and password. Click Save changes.",
    faq3_answer:  "Go to the login page, click Forgot your password? and follow the instructions. You'll receive an email with a reset link. Check your spam folder. The link expires in 30 minutes.",
    faq4_answer:  "Go to Settings → Security and enable Two-factor authentication. Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.).",
    faq5_answer:  "Accounts are suspended for Terms of Use violations or suspicious activity. If you believe it was a mistake, submit a support request. The team will respond within 48 hours. Do not create a new account while under review.",
    faq6_answer:  "Click the sun/moon icon in the navigation bar to toggle between light and dark mode. Your preference is saved automatically.",
    faq7_answer:  "Go to Releases → New Release. Fill in the title, artist, genre, and date. Upload the audio file (WAV or FLAC recommended) and cover art (minimum 3000×3000 px). Review and confirm. Processing takes up to 72 hours.",
    faq8_answer:  "Accepted formats: WAV (recommended — 16 or 24 bit, 44.1 kHz), FLAC (lossless), AIFF (Apple compatible), and MP3 at 320 kbps (less recommended). Maximum file size: 1 GB.",
    faq9_answer:  "Cover art must be at least 3000×3000 pixels, square format (1:1), in JPG or PNG. It must not contain store logos, URLs, or contact information.",
    faq10_answer: "After internal approval (up to 72h), the release is sent to platforms. Spotify and Apple Music typically in 3–7 days, others up to 14 days. Submit at least 2 weeks in advance.",
    faq11_answer: "While in draft or under review, you can edit freely. Once distributed, title and artist cannot be changed as they are already on the platforms.",
    faq12_answer: "During release creation, select a future date in the Release date field. Submit at least 2 weeks in advance to ensure availability on the desired date.",
    faq13_answer: "Your balance appears at the top of Finances → Overview, divided into available balance and pending balance. History is in Finances → Transactions.",
    faq14_answer: "Go to Finances → Withdrawals, choose the method (IBAN, Express, PayPal), enter the amount, and confirm your password. Processing takes 3–5 business days.",
    faq15_answer: "The minimum withdrawal amount is 1,000 AOA. Check Account and services for monthly limits on your plan.",
    faq16_answer: "Wasom Upfy distributes 90% of royalties directly to the artist. The remaining 10% covers distribution and platform operating costs.",
    faq17_answer: "Royalties are processed and credited to your wallet by the 15th of each month for the previous month. Some platforms have a 2–3 month delay.",
    faq18_answer: "Go to Finances → Overview → Royalty Split. Set percentages for each collaborator per release or album. The total must always equal 100%.",
    faq19_answer: "Yes. Depending on your plan, you can create multiple artists. The Label plan allows unlimited artists. Check Account and services for details.",
    faq20_answer: "Go to Account Management → Collaborators and click Invite Collaborator. Enter their email and set permissions (view, edit, finances).",
    faq21_answer: "Go to Artists → [artist name] → Edit Profile. In the social networks section, add links for Instagram, Facebook, YouTube, Spotify, Apple Music, TikTok, and website.",
    faq22_answer: "Go to Statistics in the main menu. Filter by artist, album, track, period, and platform. Click an artist for full details including countries and playlists.",
    faq23_answer: "Statistics include: Spotify, Apple Music, YouTube Music, Deezer, Tidal, Amazon Music, Boomplay, TikTok, iTunes, and others. Some platforms have a 24–72 hour delay.",
    faq24_answer: "In Statistics → Export, select period, artists, and platforms. Export as CSV for Excel analysis or as PDF. Pre-generated reports are in Statistics → Reports.",
    faq25_answer: "Statistics are updated daily. Spotify and Apple Music have up to 1 day delay. Boomplay and Amazon Music may have 3–5 day delays.",
    faq26_answer: "YouTube channel unification lets you connect your YouTube channel to sync Art Tracks, track streams and revenue, and detect fan content. Available on all plans at no extra cost.",
    faq27_answer: "Go to Artists → YouTube Unification → Register Channel. After submitting the URL, you'll receive a WASOM-XXXXXXXX code. Add it to your YouTube channel description and wait up to 48 hours for confirmation.",
    faq28_answer: "An Art Track is an automatic video created by YouTube Music with your release cover as a static image and the music as audio. Created automatically when you distribute through YouTube Music.",
    faq29_answer: "Wasom Upfy offers four plans: Single (2,000 AOA per release), Album (5,000 AOA per release), Artist (11,400 AOA/month), and Label (70,000 AOA/month).",
    faq30_answer: "Yes. Upgrade: available immediately after payment. Downgrade: takes effect at the end of the current cycle. Contact support to initiate the change.",
    faq31_answer: "After payment, upload the receipt in Account → Activate Plan. The team will verify and activate within 24 business hours. You'll receive an email notification.",
    faq32_answer: "Go to Support, select the issue type, urgency level, and describe the problem in detail. You can attach files up to 10 MB each. The team responds within 48 business hours.",
    faq33_answer: "Support operates Monday–Friday 9am–6pm (WAT) and Saturday 9am–1pm. Tickets are answered within 48 business hours. Urgent tickets get priority within 24 hours.",
    faq34_answer: "Submit a support request with type Refund Request, including the transaction number, date, and reason. Refunds are processed within 10 business days.",
  },
};

// ── Current state ─────────────────────────────────────
let currentCategory = "all";
let currentSearch   = "";
let currentLang     = "pt-AO";

// ── Debounce ──────────────────────────────────────────
function debounce(func, wait) {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), wait);
  };
}

// ── i18n ──────────────────────────────────────────────
function changeLanguage(lang) {
  if (!enableI18n && lang !== currentLang) return;
  currentLang = lang;
  const t = translations[lang] || translations["pt-AO"];

  document.querySelectorAll("[data-i18n]").forEach(function (el) {
    const key = el.getAttribute("data-i18n");
    if (!t[key]) return;
    const icon = el.querySelector("i");
    el.innerHTML = icon ? icon.outerHTML + " " + t[key] : t[key];
  });
  document.querySelectorAll("[data-i18n-placeholder]").forEach(function (el) {
    const key = el.getAttribute("data-i18n-placeholder");
    if (t[key]) el.placeholder = t[key];
  });
  document.documentElement.lang = lang;
  buildIndex();
}

// ── Toggle FAQ item (custom accordion) ───────────────
// Chamado via onclick="toggleFAQ(this)" no HTML
function toggleFAQ(questionEl) {
  const faqItem = questionEl.parentElement;
  const answer  = questionEl.nextElementSibling;
  const isActive = faqItem.classList.contains("active");

  // Fecha os outros abertos
  document.querySelectorAll(".faq-item.active").forEach(function (item) {
    if (item !== faqItem) {
      item.classList.remove("active");
      var q = item.querySelector(".question");
      if (q) q.setAttribute("aria-expanded", "false");
      var a = item.querySelector(".answer");
      if (a) { a.style.maxHeight = "0"; a.style.padding = "0"; a.style.opacity = "0"; }
    }
  });

  faqItem.classList.toggle("active");
  questionEl.setAttribute("aria-expanded", String(!isActive));

  if (!isActive) {
    answer.style.maxHeight = answer.scrollHeight + "px";
    answer.style.padding   = "";
    answer.style.opacity   = "1";
  } else {
    answer.style.maxHeight = "0";
    answer.style.padding   = "0";
    answer.style.opacity   = "0";
  }

  updateProgressBar();
}

// ── Category filter ───────────────────────────────────
function filterCategory(cat) {
  currentCategory = cat;

  // Actualiza botões
  document.querySelectorAll(".cat-btn").forEach(function (btn) {
    btn.classList.toggle("active", btn.dataset.cat === cat);
  });

  // Fecha todos os itens abertos ao trocar categoria
  document.querySelectorAll(".faq-item.active").forEach(function (item) {
    item.classList.remove("active");
    var q = item.querySelector(".question");
    if (q) q.setAttribute("aria-expanded", "false");
    var a = item.querySelector(".answer");
    if (a) { a.style.maxHeight = "0"; a.style.padding = "0"; a.style.opacity = "0"; }
  });

  applyFilters();
}

// ── Search ────────────────────────────────────────────
function searchFAQ() {
  var input = document.getElementById("faqSearch");
  currentSearch = input ? input.value.toLowerCase().trim() : "";
  applyFilters();
}

// ── Apply both category + search filters ─────────────
function applyFilters() {
  var faqItems = document.querySelectorAll(".faq-item");
  var visibleCount = 0;

  faqItems.forEach(function (item) {
    var cat = item.getAttribute("data-category") || "";
    var questionEl = item.querySelector(".question span");
    var answerEl   = item.querySelector(".answer");
    if (!questionEl || !answerEl) return;

    // Repõe texto sem marcações
    var rawQuestion = questionEl.getAttribute("data-raw") || questionEl.textContent;
    var rawAnswer   = answerEl.getAttribute("data-raw")   || answerEl.textContent;
    questionEl.setAttribute("data-raw", rawQuestion);
    answerEl.setAttribute("data-raw",   rawAnswer);

    var matchCat    = (currentCategory === "all") || (cat === currentCategory);
    var matchSearch = !currentSearch ||
                      rawQuestion.toLowerCase().includes(currentSearch) ||
                      rawAnswer.toLowerCase().includes(currentSearch);

    if (matchCat && matchSearch) {
      item.classList.add("visible");
      visibleCount++;

      // Highlight
      if (currentSearch) {
        var regex = new RegExp("(" + escapeRegex(currentSearch) + ")", "gi");
        questionEl.innerHTML = rawQuestion.replace(regex, "<mark>$1</mark>");
        answerEl.innerHTML   = rawAnswer.replace(regex, "<mark>$1</mark>");
      } else {
        questionEl.innerHTML = rawQuestion;
        answerEl.innerHTML   = rawAnswer;
      }
    } else {
      item.classList.remove("visible");
    }
  });

  // No results
  var noRes = document.getElementById("noResults");
  if (noRes) noRes.style.display = visibleCount === 0 ? "block" : "none";

  buildIndex();
  updateProgressBar();
}

function escapeRegex(str) {
  return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

// ── Build index (dynamic) ─────────────────────────────
function buildIndex() {
  var lists = ["indexList", "indexListMobile"];
  lists.forEach(function (listId) {
    var ul = document.getElementById(listId);
    if (!ul) return;
    ul.innerHTML = "";

    document.querySelectorAll(".faq-item.visible").forEach(function (item) {
      var id  = item.getAttribute("id");
      var qEl = item.querySelector(".question span");
      if (!id || !qEl) return;

      var rawText = qEl.getAttribute("data-raw") || qEl.textContent;

      var li = document.createElement("li");
      li.className = "index-item";
      li.innerHTML = '<a href="#' + id + '">' + rawText + '</a>';
      li.querySelector("a").addEventListener("click", function (e) {
        e.preventDefault();
        var target = document.getElementById(id);
        if (target) {
          target.scrollIntoView({ behavior: "smooth", block: "start" });
          // Abre o item se estiver fechado
          var q = target.querySelector(".question");
          if (q && !target.classList.contains("active")) toggleFAQ(q);
        }
      });
      ul.appendChild(li);
    });

    // Tips e Tutorial sempre no fim do índice
    ["tips", "tutorial"].forEach(function (secId) {
      var sec = document.getElementById(secId);
      if (!sec) return;
      var labels = { tips: "Dicas Rápidas", tutorial: "Tutorial" };
      var li = document.createElement("li");
      li.className = "index-item";
      li.innerHTML = '<a href="#' + secId + '">' + labels[secId] + '</a>';
      li.querySelector("a").addEventListener("click", function (e) {
        e.preventDefault();
        var t = document.getElementById(secId);
        if (t) t.scrollIntoView({ behavior: "smooth", block: "start" });
      });
      ul.appendChild(li);
    });
  });
}

// ── Progress bar de leitura ───────────────────────────
function updateProgressBar() {
  var fill       = document.getElementById("progressBar");
  var backToTop  = document.getElementById("backToTop");
  var scrollTop  = document.documentElement.scrollTop || document.body.scrollTop;
  var scrollH    = document.documentElement.scrollHeight - document.documentElement.clientHeight;
  var pct        = scrollH > 0 ? (scrollTop / scrollH) * 100 : 0;

  if (fill)     fill.style.width = pct + "%";

  var threshold = window.innerWidth <= 767 ? 150 : 300;
  if (backToTop) backToTop.classList.toggle("visible", scrollTop > threshold);
}

// ── Back to top ───────────────────────────────────────
function scrollToTop() {
  window.scrollTo({ top: 0, behavior: "smooth" });
}

// ── Print ─────────────────────────────────────────────
function printFAQ() {
  window.print();
}

// ── Recalculate answer heights after resize ───────────
function recalculateAnswerHeights() {
  document.querySelectorAll(".faq-item").forEach(function (item) {
    var answer = item.querySelector(".answer");
    if (!answer) return;
    if (item.classList.contains("active")) {
      answer.style.maxHeight = answer.scrollHeight + "px";
      answer.style.opacity   = "1";
    } else {
      answer.style.maxHeight = "0";
      answer.style.padding   = "0";
      answer.style.opacity   = "0";
    }
  });
}

// ── Init ──────────────────────────────────────────────
window.addEventListener("load", function () {
  // Language
  if (enableI18n) {
    var sel = document.getElementById("languageSelector");
    if (sel) sel.style.display = "";
    var selEl = document.getElementById("languageSelect");
    var userLang = navigator.language || navigator.userLanguage || "pt-AO";
    var lang = userLang.startsWith("en") ? "en-US" : "pt-AO";
    if (selEl) selEl.value = lang;
    changeLanguage(lang);
  } else {
    changeLanguage("pt-AO");
  }

  // Mostrar todos os itens
  applyFilters();
  updateProgressBar();
  requestAnimationFrame(recalculateAnswerHeights);

  // URL param: ?search=... ou ?categoria=...
  var params = new URLSearchParams(window.location.search);
  var searchParam = params.get("search");
  var catParam    = params.get("categoria");

  if (catParam) {
    filterCategory(catParam);
    var catBtn = document.querySelector('[data-cat="' + catParam + '"]');
    if (catBtn) catBtn.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  if (searchParam) {
    var inp = document.getElementById("faqSearch");
    if (inp) { inp.value = searchParam; searchFAQ(); }
  }
});

window.addEventListener("resize", debounce(function () {
  updateProgressBar();
  recalculateAnswerHeights();
}, 100));

window.addEventListener("scroll", debounce(updateProgressBar, 10));