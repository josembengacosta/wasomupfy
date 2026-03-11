// faq.js — Wasom Upfy
// Caminho: js/faq.js

// Configuração para i18n
const enableI18n = true; // Mude para false para desativar suporte a múltiplos idiomas

// Objeto de traduções
const translations = {
  "pt-BR": {
    // ── Geral ────────────────────────────────────────────────────────────────
    faq_title: "Perguntas Frequentes",
    faq_description:
      "Encontre respostas para as perguntas mais comuns sobre a plataforma Wasom Upfy. <br> Não encontrou o que procurava? Entre em contacto com o nosso <a href='support' class='text-secondary'>suporte</a>!",
    faq_update_date: "Última actualização: 14 de Fevereiro de 2026",
    download_pdf: "Baixar em PDF",
    print: "Imprimir",
    search_placeholder: "Pesquisar perguntas...",
    index_title: "Índice",
    tips_title: "Dicas Rápidas",
    index_tips: "Dicas Rápidas",
    index_tutorial: "Tutorial",
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

    // ── Índice ───────────────────────────────────────────────────────────────
    index_faq1:  "Como me cadastro na plataforma?",
    index_faq2:  "Esqueci a minha senha",
    index_faq3:  "Como funciona a verificação de e-mail?",
    index_faq4:  "Como activar o 2FA?",
    index_faq5:  "Como desactivar a minha conta?",
    index_faq6:  "Como eliminar a minha conta?",
    index_faq7:  "Como reactivar uma conta desactivada?",
    index_faq8:  "Prazo para chegar nas lojas",
    index_faq9:  "Formatos de áudio aceitos",
    index_faq10: "Requisitos da arte da capa",
    index_faq11: "Para quais lojas distribuem?",
    index_faq12: "ISRC e metadados",
    index_faq13: "Diferença entre planos",
    index_faq14: "Posso mudar de plano?",
    index_faq15: "Que percentagem de royalties recebo?",
    index_faq16: "Quando e como recebo os ganhos?",
    index_faq17: "Como cadastrar um novo artista?",
    index_faq18: "Como ver as estatísticas?",
    index_faq19: "Posso adicionar colaboradores?",
    index_faq20: "Como funciona o modo escuro?",
    index_faq21: "A plataforma suporta múltiplos idiomas?",

    // ── Perguntas & Respostas ────────────────────────────────────────────────
    // — Conta & Acesso —
    faq1_question: "Como me cadastro na Wasom Upfy?",
    faq1_answer:
      "Aceda a wasomupfy/register e preencha o formulário com o seu nome, e-mail e uma senha segura. Após submeter, receberá um e-mail de verificação — clique no link para activar a conta. Feito isso, já pode entrar no dashboard e começar a gerir os seus artistas e lançamentos. Se as inscrições estiverem temporariamente encerradas, aparecerá uma mensagem de aviso no site.",

    faq2_question: "O que fazer se esquecer a minha senha?",
    faq2_answer:
      "Aceda à página de login e clique em \"Esqueci a senha\". Receberá um e-mail com um link de redefinição seguro válido por 1 hora. Clique no link, defina uma nova senha forte (mínimo 8 caracteres, misturando letras, números e símbolos) e confirme. Verifique a caixa de spam caso o e-mail não apareça na sua caixa de entrada. Se continuar sem acesso, contacte o suporte.",

    faq3_question: "Como funciona a verificação de e-mail?",
    faq3_answer:
      "Após o cadastro, enviamos automaticamente um e-mail de verificação. Clique no botão \"Verificar e-mail\" contido no e-mail para activar a sua conta. Enquanto o e-mail não estiver verificado, o acesso ao dashboard ficará limitado. Se não receber o e-mail em alguns minutos, entre no painel e clique em \"Reenviar verificação\". O link de verificação expira ao fim de 24 horas.",

    faq4_question: "Como activar a autenticação em dois factores (2FA)?",
    faq4_answer:
      "Aceda ao dashboard → Definições → Segurança e active o 2FA. Utilizamos autenticação por e-mail: após inserir a senha no login, será enviado um código temporário (OTP) para o seu e-mail que deverá inserir para concluir o acesso. O código expira ao fim de 10 minutos. Recomendamos activar o 2FA para proteger a sua conta, especialmente se gerir artistas ou receber royalties.",

    faq5_question: "Como desactivar temporariamente a minha conta?",
    faq5_answer:
      "Aceda ao dashboard → Definições → Conta e escolha a opção \"Desactivar conta\". A conta ficará suspensa e os seus dados serão preservados. Durante este período, o seu perfil e músicas ficam invisíveis para terceiros. Para reactivar, basta fazer login novamente — uma caixa de diálogo irá perguntar se deseja restaurar a conta, e após confirmar, tudo voltará ao normal.",

    faq6_question: "Como eliminar permanentemente a minha conta?",
    faq6_answer:
      "Aceda ao dashboard → Definições → Conta → Eliminar conta. Esta acção é irreversível: todos os seus artistas, lançamentos, estatísticas e dados pessoais serão apagados permanentemente. Recomendamos exportar os seus relatórios financeiros antes de prosseguir. Caso tenha royalties pendentes, solicite o levantamento primeiro. A eliminação é processada imediatamente após a confirmação.",

    faq7_question: "Como reactivar uma conta desactivada?",
    faq7_answer:
      "Aceda à página de login com o seu e-mail e senha. Ao iniciar sessão, o sistema detecta que a conta está desactivada e apresenta uma caixa de diálogo a perguntar se deseja \"Restaurar a conta\". Confirme a acção e a conta será reactivada imediatamente com todos os dados intactos — artistas, lançamentos, histórico financeiro e configurações.",

    // — Distribuição —
    faq8_question: "Quanto tempo demora para a música estar nas lojas?",
    faq8_answer:
      "O prazo médio é de 3 a 7 dias úteis após aprovação interna (24-48h). Cada plataforma tem o seu próprio tempo: Spotify 3-5 dias, Apple Music 2-3 dias, Deezer 3-7 dias. Recomendamos enviar o lançamento com pelo menos 3 semanas de antecedência para garantir que o pitch para playlists editoriais seja feito a tempo. Data de lançamento agendada é possível — defina-a no formulário de upload.",

    faq9_question: "Quais os formatos de áudio aceitos?",
    faq9_answer:
      "Aceitamos exclusivamente WAV estéreo, 44.1 kHz, 16-bit ou 24-bit. Ficheiros MP3, AAC, OGG e outros formatos com perda de qualidade não são aceites pelas lojas digitais. Para melhores resultados no mastering, deixe um headroom de -1 dB e evite compressão excessiva. O tamanho máximo por faixa é de 1 GB. Se tiver graves intensos, opte por 24-bit para preservar a dinâmica.",

    faq10_question: "Quais os requisitos da arte da capa?",
    faq10_answer:
      "A capa deve ser um quadrado perfeito de mínimo 3000×3000 px, em formato JPG ou PNG, modo de cor RGB, sem artefactos ou pixelização. É proibido incluir logótipos de redes sociais (Instagram, TikTok, etc.), marcas d'água, preços, QR codes, URLs ou informações de contacto. Capas com conteúdo explícito sem a marcação adequada serão rejeitadas. Reveja sempre a capa em tamanho reduzido (thumbnail) antes de submeter.",

    faq11_question: "Para quais lojas a Wasom Upfy distribui?",
    faq11_answer:
      "Distribuímos para mais de 150 lojas e plataformas globais, incluindo Spotify, Apple Music, Deezer, TikTok, Amazon Music, TIDAL, YouTube Music, Boomplay, Audiomack, Anghami e muitas outras. A disponibilidade pode variar por plano — os planos Single e Álbum cobrem as principais lojas, enquanto os planos Artista e Label garantem distribuição completa e prioritária para todas as plataformas disponíveis.",

    faq12_question: "Como preencher os metadados e gerar o ISRC?",
    faq12_answer:
      "O ISRC (International Standard Recording Code) é gerado automaticamente pela plataforma para cada faixa. No formulário de upload, preencha correctamente: nome do artista principal, artistas convidados (feat.), compositores com as respectivas percentagens, produtores, engenheiros de mixagem e mastering, género musical, idioma e se a letra contém conteúdo explícito. Dados incorrectos podem atrasar a distribuição ou causar conflitos de royalties.",

    // — Planos —
    faq13_question: "Qual a diferença entre os planos disponíveis?",
    faq13_answer:
      "Temos 4 planos: Single (2.000 Kz) — ideal para lançar 1 a 3 faixas pontuais; Álbum (5.000 Kz) — para trabalhos completos com múltiplas faixas; Artista (11.400 Kz/2 anos) — gestão contínua para artistas activos, com lançamentos ilimitados, estatísticas avançadas e suporte prioritário; Label (70.000 Kz/2 anos) — para editoras e selos que gerem vários artistas, com painel multi-artista e relatórios consolidados.",

    faq14_question: "Posso mudar de plano depois da compra?",
    faq14_answer:
      "Sim. Pode fazer upgrade para um plano superior a qualquer momento — basta aceder ao dashboard e seleccionar o novo plano. Para fazer downgrade ou questões específicas de transição, contacte o suporte. O plano actual permanece activo até ao fim do período contratado antes de ser substituído.",

    // — Royalties —
    faq15_question: "Que percentagem de royalties recebo?",
    faq15_answer:
      "Recebe 90% dos royalties líquidos gerados pelas suas músicas nas lojas. Os 10% restantes cobrem a infraestrutura da plataforma, suporte ao artista e taxas administrativas. Os royalties líquidos são calculados após as deduções das plataformas (ex: Spotify retém uma parte antes de repassar às distribuidoras). Pode acompanhar os ganhos em tempo real no seu dashboard, filtrado por loja, período ou artista.",

    faq16_question: "Quando e como recebo os meus ganhos?",
    faq16_answer:
      "Os relatórios de streams são actualizados mensalmente — os dados de Janeiro ficam disponíveis em Março (dia 15) e o pagamento é processado até ao dia 20. Após atingir o valor mínimo de levantamento do seu plano, pode solicitar o resgate via transferência bancária, IBAN ou outros métodos disponíveis na sua carteira Wasom Upfy. Certifique-se de que os seus dados bancários estão correctos nas definições antes de solicitar.",

    // — Artistas & Dashboard —
    faq17_question: "Como cadastro um novo artista na plataforma?",
    faq17_answer:
      "No dashboard, aceda à secção \"Artistas\" e clique em \"Adicionar Artista\". Preencha o nome artístico, bio, género musical e carregue a foto de perfil (formato JPG/PNG, mínimo 400×400 px). Após guardar, o artista fica disponível para associar aos seus lançamentos. Pode editar as informações a qualquer momento. Em planos Label, pode gerir múltiplos artistas sob o mesmo painel com controlo total sobre cada perfil.",

    faq18_question: "Como vejo as estatísticas das minhas músicas?",
    faq18_answer:
      "Aceda ao dashboard e clique em \"Estatísticas\" no menu lateral. Pode filtrar por artista, lançamento ou período de tempo para visualizar streams, países com mais audiência, plataformas com melhor desempenho e evolução ao longo do tempo. Os dados são apresentados em gráficos e tabelas interactivas. Também pode exportar os relatórios em formato CSV para análise detalhada em Excel ou Google Sheets.",

    faq19_question: "Posso adicionar colaboradores à minha conta?",
    faq19_answer:
      "Sim. Aceda ao dashboard → Definições → Colaboradores e convide utilizadores por e-mail. Pode definir o nível de acesso: Visualizador (só lê estatísticas), Editor (gere artistas e lançamentos) ou Administrador (acesso total exceto dados financeiros). Os colaboradores recebem um convite por e-mail para criar ou vincular a sua conta à equipa. Ideal para managers, produtores e equipas de marketing.",

    // — Plataforma —
    faq20_question: "Como funciona o modo escuro?",
    faq20_answer:
      "O modo escuro pode ser activado manualmente ou seguir automaticamente a preferência do seu sistema operativo. Clique no ícone no canto inferior direito e escolha entre Claro, Escuro ou Sistema (automático). A preferência é guardada no seu browser e aplica-se a todas as páginas do site. O modo escuro reduz o cansaço visual em ambientes com pouca luz e prolonga a bateria em dispositivos com ecrã OLED.",

    faq21_question: "A plataforma suporta múltiplos idiomas?",
    faq21_answer:
      "Sim. O site público e algumas secções do FAQ estão disponíveis em Português (PT/AO/BR) e Inglês. O idioma é detectado automaticamente com base nas preferências do seu browser, mas pode alterá-lo manualmente através do selector de idioma na página. O dashboard está optimizado para Português, sendo o idioma principal da plataforma dada a sua natureza focada no mercado angolano e da CPLP.",

    // ── Dicas ────────────────────────────────────────────────────────────────
    tip1: "Lance com 3 semanas de antecedência para garantir o pitch nas playlists editoriais do Spotify e Apple Music.",
    tip2: "Use os filtros de data nas Estatísticas para comparar lançamentos e identificar a sua audiência principal.",
    tip3: "Active o 2FA nas definições de segurança para proteger a sua conta e os seus royalties.",
    tip4: "Exporte os relatórios financeiros em CSV regularmente para manter um registo histórico fora da plataforma.",
  },

  "en-US": {
    // ── Geral ────────────────────────────────────────────────────────────────
    faq_title: "Frequently Asked Questions",
    faq_description:
      "Find answers to the most common questions about the Wasom Upfy platform. <br> Can't find what you're looking for? Contact our <a href='support' class='text-secondary'>support team</a>!",
    faq_update_date: "Last updated: February 14, 2026",
    download_pdf: "Download as PDF",
    print: "Print",
    search_placeholder: "Search questions...",
    index_title: "Index",
    tips_title: "Quick Tips",
    index_tips: "Quick Tips",
    index_tutorial: "Tutorial",
    tutorial_title: "Watch Our Tutorial",
    watch_video: "Watch Video",
    tutorial_modal_title: "Wasom Upfy Tutorial",
    close: "Close",
    footer_version: "Version 2.1 (2026)",
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

    // ── Índice ───────────────────────────────────────────────────────────────
    index_faq1:  "How do I sign up?",
    index_faq2:  "Forgot my password",
    index_faq3:  "How does email verification work?",
    index_faq4:  "How to enable 2FA?",
    index_faq5:  "How to deactivate my account?",
    index_faq6:  "How to delete my account?",
    index_faq7:  "How to reactivate a deactivated account?",
    index_faq8:  "Store delivery times",
    index_faq9:  "Accepted audio formats",
    index_faq10: "Cover art requirements",
    index_faq11: "Which stores do you distribute to?",
    index_faq12: "ISRC and metadata",
    index_faq13: "Difference between plans",
    index_faq14: "Can I change my plan?",
    index_faq15: "What percentage of royalties do I receive?",
    index_faq16: "When and how do I get paid?",
    index_faq17: "How to register a new artist?",
    index_faq18: "How do I view statistics?",
    index_faq19: "Can I add collaborators?",
    index_faq20: "How does dark mode work?",
    index_faq21: "Does the platform support multiple languages?",

    // ── Perguntas & Respostas ────────────────────────────────────────────────
    // — Account & Access —
    faq1_question: "How do I sign up for Wasom Upfy?",
    faq1_answer:
      "Go to wasomupfy/register and fill in the form with your name, email and a strong password. After submitting, you will receive a verification email — click the link to activate your account. You can then access the dashboard and start managing your artists and releases. If registrations are temporarily closed, a notice will appear on the site.",

    faq2_question: "What should I do if I forget my password?",
    faq2_answer:
      "Go to the login page and click \"Forgot my password\". You will receive an email with a secure reset link valid for 1 hour. Click the link, set a new strong password (minimum 8 characters, mixing letters, numbers and symbols) and confirm. Check your spam folder if the email does not appear in your inbox. If you still cannot access your account, contact support.",

    faq3_question: "How does email verification work?",
    faq3_answer:
      "After signing up, we automatically send a verification email. Click the \"Verify email\" button in the email to activate your account. While your email is not verified, access to the dashboard will be limited. If you do not receive the email within a few minutes, log in to the panel and click \"Resend verification\". The verification link expires after 24 hours.",

    faq4_question: "How do I enable two-factor authentication (2FA)?",
    faq4_answer:
      "Go to dashboard → Settings → Security and enable 2FA. We use email-based authentication: after entering your password at login, a one-time code (OTP) will be sent to your email that you must enter to complete access. The code expires after 10 minutes. We strongly recommend enabling 2FA to protect your account, especially if you manage artists or receive royalties.",

    faq5_question: "How do I temporarily deactivate my account?",
    faq5_answer:
      "Go to dashboard → Settings → Account and choose the \"Deactivate account\" option. Your account will be suspended and all your data preserved. During this period, your profile and music will be invisible to others. To reactivate, simply log in again — a dialog box will ask if you want to restore the account, and after confirming, everything will return to normal.",

    faq6_question: "How do I permanently delete my account?",
    faq6_answer:
      "Go to dashboard → Settings → Account → Delete account. This action is irreversible: all your artists, releases, statistics and personal data will be permanently deleted. We recommend exporting your financial reports before proceeding. If you have pending royalties, request a withdrawal first. Deletion is processed immediately after confirmation.",

    faq7_question: "How do I reactivate a deactivated account?",
    faq7_answer:
      "Go to the login page with your email and password. Upon logging in, the system detects that the account is deactivated and shows a dialog asking if you want to \"Restore the account\". Confirm the action and the account will be immediately reactivated with all data intact — artists, releases, financial history and settings.",

    // — Distribution —
    faq8_question: "How long does it take for music to appear in stores?",
    faq8_answer:
      "The average timeframe is 3 to 7 business days after internal approval (24-48h). Each platform has its own timeline: Spotify 3-5 days, Apple Music 2-3 days, Deezer 3-7 days. We recommend submitting your release at least 3 weeks in advance to ensure editorial playlist pitching is done on time. Scheduled release dates are possible — set yours in the upload form.",

    faq9_question: "What audio formats are accepted?",
    faq9_answer:
      "We exclusively accept stereo WAV, 44.1 kHz, 16-bit or 24-bit. MP3, AAC, OGG and other lossy formats are not accepted by digital stores. For best mastering results, leave -1 dB of headroom and avoid excessive compression. Maximum file size per track is 1 GB. If your music has heavy bass, opt for 24-bit to preserve dynamics.",

    faq10_question: "What are the cover art requirements?",
    faq10_answer:
      "The cover must be a perfect square of at least 3000×3000 px, in JPG or PNG format, RGB color mode, without artifacts or pixelation. It is prohibited to include social media logos (Instagram, TikTok, etc.), watermarks, prices, QR codes, URLs or contact information. Covers with explicit content without proper labeling will be rejected. Always review the cover at reduced size (thumbnail) before submitting.",

    faq11_question: "Which stores does Wasom Upfy distribute to?",
    faq11_answer:
      "We distribute to over 150 global stores and platforms, including Spotify, Apple Music, Deezer, TikTok, Amazon Music, TIDAL, YouTube Music, Boomplay, Audiomack, Anghami and many others. Availability may vary by plan — the Single and Album plans cover the main stores, while the Artist and Label plans ensure complete and priority distribution to all available platforms.",

    faq12_question: "How do I fill in metadata and generate an ISRC?",
    faq12_answer:
      "The ISRC (International Standard Recording Code) is automatically generated by the platform for each track. In the upload form, correctly fill in: main artist name, featured artists, composers with their percentages, producers, mixing and mastering engineers, musical genre, language, and whether the lyrics contain explicit content. Incorrect data can delay distribution or cause royalty conflicts.",

    // — Plans —
    faq13_question: "What is the difference between the available plans?",
    faq13_answer:
      "We have 4 plans: Single (2,000 AOA) — ideal for releasing 1 to 3 individual tracks; Album (5,000 AOA) — for complete projects with multiple tracks; Artist (11,400 AOA/2 years) — ongoing management for active artists, with unlimited releases, advanced statistics and priority support; Label (70,000 AOA/2 years) — for labels managing multiple artists, with a multi-artist panel and consolidated reports.",

    faq14_question: "Can I change my plan after purchasing?",
    faq14_answer:
      "Yes. You can upgrade to a higher plan at any time — simply go to the dashboard and select the new plan. For downgrades or specific plan transition questions, contact support. Your current plan remains active until the end of the contracted period before being replaced.",

    // — Royalties —
    faq15_question: "What percentage of royalties do I receive?",
    faq15_answer:
      "You receive 90% of the net royalties generated by your music in stores. The remaining 10% covers platform infrastructure, artist support and administrative fees. Net royalties are calculated after platform deductions (e.g., Spotify retains a portion before passing earnings to distributors). You can track your earnings in real time on your dashboard, filtered by store, period or artist.",

    faq16_question: "When and how do I receive my earnings?",
    faq16_answer:
      "Stream reports are updated monthly — January data becomes available in March (day 15) and payment is processed by day 20. Once you reach the minimum withdrawal amount for your plan, you can request a payout through your Wasom Upfy wallet via bank transfer, IBAN or other available methods. Make sure your banking details are correct in account settings before requesting a withdrawal.",

    // — Artists & Dashboard —
    faq17_question: "How do I register a new artist on the platform?",
    faq17_answer:
      "In the dashboard, go to the \"Artists\" section and click \"Add Artist\". Fill in the stage name, bio, musical genre and upload the profile photo (JPG/PNG format, minimum 400×400 px). After saving, the artist is available to associate with your releases. You can edit the information at any time. On Label plans, you can manage multiple artists under the same panel with full control over each profile.",

    faq18_question: "How do I view my music statistics?",
    faq18_answer:
      "Access the dashboard and click \"Statistics\" in the side menu. You can filter by artist, release or time period to view streams, top countries, best-performing platforms and evolution over time. Data is presented in interactive charts and tables. You can also export reports in CSV format for detailed analysis in external tools like Excel or Google Sheets.",

    faq19_question: "Can I add collaborators to my account?",
    faq19_answer:
      "Yes. Go to dashboard → Settings → Collaborators and invite users by email. You can set the access level for each collaborator: Viewer (read-only statistics), Editor (manages artists and releases) or Administrator (full access except financial data). Collaborators receive an email invitation to create or link their account to your team. Ideal for managers, producers and marketing teams.",

    // — Platform —
    faq20_question: "How does dark mode work?",
    faq20_answer:
      "Dark mode can be activated manually or automatically follow your operating system's preference. Click the icon in the bottom right corner of the page and choose between Light, Dark or System (automatic). The preference is saved in your browser and applies to all pages of the site. Dark mode reduces eye strain in low-light environments and extends battery life on OLED screen devices.",

    faq21_question: "Does the platform support multiple languages?",
    faq21_answer:
      "Yes. The public website and some FAQ sections are available in Portuguese (PT/AO/BR) and English. The language is automatically detected based on your browser preferences, but you can change it manually via the language selector on the page. The dashboard is optimized for Portuguese, being the platform's main language given its focus on the Angolan and CPLP markets.",

    // ── Dicas ────────────────────────────────────────────────────────────────
    tip1: "Submit releases at least 3 weeks in advance to ensure editorial playlist pitching on Spotify and Apple Music.",
    tip2: "Use date filters in Statistics to compare performance across releases and identify your main audience.",
    tip3: "Enable 2FA in security settings to protect your account and royalties from unauthorized access.",
    tip4: "Export financial reports as CSV regularly to keep a historical record of your earnings outside the platform.",
  },
};

// ── Utilitários ───────────────────────────────────────────────────────────────

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
    if (translations[lang] && translations[lang][key] !== undefined) {
      const icon = element.querySelector("i");
      const text = translations[lang][key];
      element.innerHTML = icon ? `${icon.outerHTML} ${text}` : text;
    }
  });
  document.querySelectorAll("[data-i18n-placeholder]").forEach((element) => {
    const key = element.getAttribute("data-i18n-placeholder");
    if (translations[lang] && translations[lang][key] !== undefined) {
      element.placeholder = translations[lang][key];
    }
  });
  document.documentElement.lang = lang;
}

// Função para alternar o dropdown de temas
function toggleDropdown() {
  const dropdown = document.getElementById("themeDropdown");
  if (dropdown) dropdown.classList.toggle("active");
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

    const indexItem = indexItems[index];
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
  const tipsEl = document.getElementById("index-tips");
  const tutEl = document.getElementById("index-tutorial");
  if (tipsEl) tipsEl.classList.remove("hidden");
  if (tutEl) tutEl.classList.remove("hidden");

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
  const progress = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
  const progressBarFill = document.getElementById("progressBar");
  if (progressBarFill) progressBarFill.style.width = `${progress}%`;

  // Mostra o botão "Voltar ao Topo" após rolar 150px em mobile, 300px em desktop
  if (backToTop) {
    const threshold = window.innerWidth <= 767 ? 150 : 300;
    if (scrollTop > threshold) {
      backToTop.classList.add("visible");
    } else {
      backToTop.classList.remove("visible");
    }
  }
}

// Função para voltar ao topo
function scrollToTop() {
  window.scrollTo({ top: 0, behavior: "smooth" });
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

// ── Inicialização ─────────────────────────────────────────────────────────────

window.addEventListener("load", () => {
  if (enableI18n) {
    const userLang = navigator.language || navigator.userLanguage;
    const defaultLang = userLang.startsWith("en") ? "en-US" : "pt-BR";
    changeLanguage(defaultLang);
    const langSel = document.getElementById("languageSelect");
    if (langSel) langSel.value = defaultLang;
  } else {
    const langSelector = document.getElementById("languageSelector");
    if (langSelector) langSelector.style.display = "none";
    document.documentElement.lang = "pt-BR";
  }

  // changeTheme é definido em js/vendors/color-modes.js
  if (typeof changeTheme === "function") changeTheme("auto");

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
  if (
    dropdown &&
    toggle &&
    !toggle.contains(e.target) &&
    !dropdown.contains(e.target)
  ) {
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