// Configuração para i18n
const enableI18n = true; // Mude para false para desativar suporte a múltiplos idiomas

// Objeto de traduções
const translations = {
  "pt-BR": {
    faq_title: "Perguntas frequentes",
    faq_description:
      "Encontre respostas para as perguntas mais comuns sobre o uso da plataforma Wasom Upfy. <br> Não encontrou o que procurava? Entre em contato com nosso suporte!",
    faq_update_date: "Última atualização: 26 de junho de 2025",
    download_pdf: "Baixar em PDF",
    print: "Imprimir",
    search_placeholder: "Pesquisar perguntas...",
    index_title: "Índice",
    index_faq1: "Como posso cadastrar um novo artista?",
    index_faq2: "Como vejo as estatísticas das minhas músicas?",
    index_faq3: "O que fazer se eu esquecer minha senha?",
    index_faq4: "Como funciona o modo escuro?",
    index_tips: "Dicas Rápidas",
    index_tutorial: "Tutorial",
    faq1_question: "Como posso cadastrar um novo artista?",
    faq1_answer:
      'Para cadastrar um novo artista, acesse a seção "Artistas" no menu, clique em "Adicionar Novo" e preencha os detalhes solicitados, como nome, foto e informações de contato. Após revisar, salve as alterações. Este processo é simples e pode ser feito em poucos minutos. Certifique-se de que as informações estejam corretas para evitar problemas futuros.',
    faq2_question: "Como vejo as estatísticas das minhas músicas?",
    faq2_answer:
      'Acesse a seção "Estatísticas" no menu principal. Selecione o artista ou música desejada e utilize os filtros de data para visualizar os dados de streams em gráficos e tabelas. Você também pode exportar os dados em formato CSV para uma análise mais detalhada, se necessário.',
    faq3_question: "O que fazer se eu esquecer minha senha?",
    faq3_answer:
      'Vá até a página de login, clique em "Esqueceu sua senha?" e siga as instruções para redefini-la. Você receberá um e-mail com um link para criar uma nova senha. Certifique-se de verificar sua caixa de spam caso o e-mail não apareça na sua caixa de entrada.',
    faq4_question: "Como funciona o modo escuro?",
    faq4_answer:
      "O modo escuro pode ser ativado manualmente ou automaticamente. Use o seletor de temas no canto inferior direito para escolher entre Light, Dark ou Automático, que segue a preferência do seu sistema. O modo escuro é ideal para uso em ambientes com pouca luz, reduzindo o cansaço visual.",
    tips_title: "Dicas Rápidas",
    tip1: "Use os filtros de data para comparar estatísticas rapidamente.",
    tip2: "Ative notificações para novos streams na sua página de perfil.",
    tip3: "Exporte seus dados em CSV na seção de estatísticas.",
    tutorial_title: "Assista ao Nosso Tutorial",
    watch_video: "Ver Vídeo",
    tutorial_modal_title: "Tutorial Wasom Upfy",
    close: "Fechar",
    footer_version: "Versão 2.1 (2026)",
    nav_dashboard: "Dashboard",
    nav_releases: "Lançamentos",
    nav_stats: "Estatísticas",
    nav_finances: "Finanças",
    nav_ringtones: "Toques de espera",
    nav_artists: "Artistas",
    nav_youtube: "Unificação de canal YouTube",
    nav_faq: "FAQ",
    nav_support: "Suporte",
    nav_terms: "Termos de Uso",
    nav_privacy: "Política de Privacidade",
  },
  "en-US": {
    faq_title: "Frequently asked questions",
    faq_description:
      "Find answers to the most common questions about using the Wasom Upfy platform. <br> Can’t find what you’re looking for? Contact our support team!",
    faq_update_date: "Last updated: May 10, 2025",
    download_pdf: "Download as PDF",
    print: "Print",
    search_placeholder: "Search questions...",
    index_title: "Index",
    index_faq1: "How can I register a new artist?",
    index_faq2: "How do I view my music statistics?",
    index_faq3: "What should I do if I forget my password?",
    index_faq4: "How does dark mode work?",
    index_tips: "Quick Tips",
    index_tutorial: "Tutorial",
    faq1_question: "How can I register a new artist?",
    faq1_answer:
      'To register a new artist, go to the "Artists" section in the menu, click "Add New," and fill in the requested details, such as name, photo, and contact information. After reviewing, save the changes. This process is simple and can be done in a few minutes. Ensure the information is accurate to avoid future issues.',
    faq2_question: "How do I view my music statistics?",
    faq2_answer:
      'Access the "Statistics" section in the main menu. Select the desired artist or song and use the date filters to view stream data in charts and tables. You can also export the data in CSV format for more detailed analysis if needed.',
    faq3_question: "What should I do if I forget my password?",
    faq3_answer:
      'Go to the login page, click "Forgot your password?" and follow the instructions to reset it. You’ll receive an email with a link to create a new password. Be sure to check your spam folder if the email doesn’t appear in your inbox.',
    faq4_question: "How does dark mode work?",
    faq4_answer:
      "Dark mode can be activated manually or automatically. Use the theme selector in the bottom right corner to choose between Light, Dark, or Automatic, which follows your system’s preference. Dark mode is ideal for low-light environments, reducing eye strain.",
    tips_title: "Quick Tips",
    tip1: "Use date filters to quickly compare statistics.",
    tip2: "Enable notifications for new streams on your profile page.",
    tip3: "Export your data in CSV from the statistics section.",
    tutorial_title: "Watch Our Tutorial",
    watch_video: "Watch Video",
    tutorial_modal_title: "Wasom Upfy Tutorial",
    close: "Close",
    footer_version: "Version 2.0 (2025)",
    nav_dashboard: "Dashboard",
    nav_releases: "Releases",
    nav_stats: "Statistics",
    nav_finances: "Finances",
    nav_ringtones: "Ringtones",
    nav_artists: "Artists",
    nav_youtube: "YouTube Channel Unification",
    nav_faq: "FAQ",
    nav_support: "Support",
    nav_terms: "Terms of Use",
    nav_privacy: "Privacy Policy",
  },
};

// Função de debounce para limitar chamadas
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

// Função para mudar o idioma
function changeLanguage(lang) {
  if (!enableI18n) return;
  document.querySelectorAll("[data-i18n]").forEach((element) => {
    const key = element.getAttribute("data-i18n");
    if (translations[lang][key]) {
      const icon = element.querySelector("i");
      const text = translations[lang][key];
      element.innerHTML = icon ? `${icon.outerHTML} ${text}` : text;
    }
  });
  document.querySelectorAll("[data-i18n-placeholder]").forEach((element) => {
    const key = element.getAttribute("data-i18n-placeholder");
    if (translations[lang][key]) {
      element.placeholder = translations[lang][key];
    }
  });
  document.documentElement.lang = lang;
}

// Função para alternar o dropdown de temas
function toggleDropdown() {
  const dropdown = document.getElementById("themeDropdown");
  dropdown.classList.toggle("active");
}

// Função para alternar as respostas do FAQ
function toggleFAQ(element) {
  const faqItem = element.parentElement;
  const answer = element.nextElementSibling;
  const isActive = faqItem.classList.contains("active");

  // Fecha outros itens abertos
  document.querySelectorAll(".faq-item.active").forEach((item) => {
    if (item !== faqItem) {
      item.classList.remove("active");
      item.querySelector(".question").setAttribute("aria-expanded", "false");
      const otherAnswer = item.querySelector(".answer");
      otherAnswer.style.maxHeight = "0";
      otherAnswer.style.padding = "0";
      otherAnswer.style.opacity = "0";
    }
  });

  // Alterna o item clicado
  faqItem.classList.toggle("active");
  element.setAttribute("aria-expanded", !isActive);
  if (!isActive) {
    answer.style.maxHeight = answer.scrollHeight + "px";
    answer.style.opacity = "1";
  } else {
    answer.style.maxHeight = "0";
    answer.style.padding = "0";
    answer.style.opacity = "0";
  }

  updateProgressBar();
}

// Função para pesquisar no FAQ com destaque
function searchFAQ() {
  const searchInput = document
    .getElementById("faqSearch")
    .value.toLowerCase()
    .trim();
  const faqItems = document.querySelectorAll(".faq-item");
  const indexItems = document.querySelectorAll(".nav-index .index-item");

  faqItems.forEach((item, index) => {
    const questionElement = item.querySelector(".question span");
    const answerElement = item.querySelector(".answer");
    const questionText = questionElement.textContent.toLowerCase();
    const answerText = answerElement.textContent.toLowerCase();

    // Limpar marcações anteriores
    questionElement.innerHTML = questionElement.textContent;
    answerElement.innerHTML = answerElement.textContent;

    const indexItem = indexItems[index]; // Correspondência com o índice
    if (
      searchInput &&
      (questionText.includes(searchInput) || answerText.includes(searchInput))
    ) {
      item.classList.add("visible");
      if (indexItem) indexItem.classList.remove("hidden");

      // Destacar termos na pergunta
      if (questionText.includes(searchInput)) {
        const regex = new RegExp(`(${searchInput})`, "gi");
        questionElement.innerHTML = questionElement.textContent.replace(
          regex,
          "<mark>$1</mark>"
        );
      }

      // Destacar termos na resposta
      if (answerText.includes(searchInput)) {
        const regex = new RegExp(`(${searchInput})`, "gi");
        answerElement.innerHTML = answerElement.textContent.replace(
          regex,
          "<mark>$1</mark>"
        );
      }
    } else if (!searchInput) {
      item.classList.add("visible");
      if (indexItem) indexItem.classList.remove("hidden");
    } else {
      item.classList.remove("visible");
      if (indexItem) indexItem.classList.add("hidden");
    }
  });

  // Sempre mostrar os links de Dicas e Tutorial
  document.getElementById("index-tips").classList.remove("hidden");
  document.getElementById("index-tutorial").classList.remove("hidden");

  updateProgressBar();
}

// Função para atualizar a barra de progresso
function updateProgressBar() {
  const backToTop = document.getElementById("backToTop");
  const scrollTop =
    document.documentElement.scrollTop || document.body.scrollTop;
  const scrollHeight =
    document.documentElement.scrollHeight -
    document.documentElement.clientHeight;
  const progress = (scrollTop / scrollHeight) * 100;
  const progressBarFill = document.getElementById("progressBar");
  progressBarFill.style.width = `${progress}%`;

  // Mostra o botão "Voltar ao Topo" após rolar 150px em mobile, 300px em desktop
  const threshold = window.innerWidth <= 767 ? 150 : 300;
  if (scrollTop > threshold) {
    backToTop.classList.add("visible");
  } else {
    backToTop.classList.remove("visible");
  }
}

// Função para voltar ao topo
function scrollToTop() {
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
}

// Função para imprimir o FAQ
function printFAQ() {
  window.print();
}

// Função para recalcular alturas das respostas
function recalculateAnswerHeights() {
  document.querySelectorAll(".faq-item").forEach((item) => {
    const answer = item.querySelector(".answer");
    if (item.classList.contains("active")) {
      answer.style.maxHeight = answer.scrollHeight + "px";
      answer.style.opacity = "1";
    } else {
      answer.style.maxHeight = "0";
      answer.style.padding = "0";
      answer.style.opacity = "0";
    }
  });
}

// Inicializa a página
window.addEventListener("load", () => {
  if (enableI18n) {
    const userLang = navigator.language || navigator.userLanguage;
    const defaultLang = userLang.startsWith("en") ? "en-US" : "pt-BR";
    changeLanguage(defaultLang);
    document.getElementById("languageSelect").value = defaultLang;
  } else {
    document.getElementById("languageSelector").style.display = "none";
    document.documentElement.lang = "pt-BR";
  }

  changeTheme("auto");
  updateProgressBar();
  requestAnimationFrame(recalculateAnswerHeights);
});

// Recalcula alturas ao redimensionar
window.addEventListener("resize", () => {
  updateProgressBar();
  recalculateAnswerHeights();
});

// Fecha o dropdown ao clicar fora
document.addEventListener("click", (e) => {
  const dropdown = document.getElementById("themeDropdown");
  const toggle = document.getElementById("themeToggle");
  if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
    dropdown.classList.remove("active");
  }
});

// Atualiza a barra de progresso ao rolar (com debounce)
window.addEventListener("scroll", debounce(updateProgressBar, 10));

// Impedir hover em dispositivos de toque
if ("ontouchstart" in window) {
  document
    .querySelectorAll(".faq-item, .nav-index a, .navbar-nav .nav-link")
    .forEach((element) => {
      element.addEventListener("touchstart", (e) => {
        e.preventDefault();
        if (element.classList.contains("question")) {
          toggleFAQ(element);
        } else if (element.tagName === "A") {
          window.location.href = element.href;
        }
      });
    });
}
