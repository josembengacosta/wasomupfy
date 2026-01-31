// Função para ativar o botão ao rolar até o final (opcional, sem botão de aceitação aqui)
        function checkScroll() {
            const backToTop = document.getElementById('backToTop');
            const scrollHeight = document.documentElement.scrollHeight;
            const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            const clientHeight = document.documentElement.clientHeight;
            const scrollPosition = scrollTop + clientHeight;

            // Mostra o botão "Voltar ao Topo" após rolar 300px
            if (scrollTop > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        }

        // Função para voltar ao topo
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Função para imprimir a política
        function printPrivacy() {
            window.print();
        }

        // Adiciona evento de scroll
        window.addEventListener('scroll', checkScroll);

        // Inicializa o tema ao carregar a página
        window.addEventListener('load', () => {
            changeTheme('auto');
            checkScroll(); // Verifica o estado inicial
        });

        // Atualiza o tema se a preferência do sistema mudar
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!document.querySelector('.theme-btn.active') || document.querySelector('.theme-btn.active').dataset.theme === 'auto') {
                changeTheme('auto');
            }
        });

        // Fecha o dropdown ao clicar fora
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('themeDropdown');
            const toggle = document.getElementById('themeToggle');
            if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });
    