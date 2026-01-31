// Função para ativar o botão ao rolar até o final
        function checkScroll() {
            const acceptBtn = document.getElementById('acceptBtn');
            const backToTop = document.getElementById('backToTop');
            const scrollHeight = document.documentElement.scrollHeight;
            const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            const clientHeight = document.documentElement.clientHeight;
            const scrollPosition = scrollTop + clientHeight;

            // Ativa o botão de aceitação quando o usuário rolar até cerca de 95% da página
            if (scrollPosition >= scrollHeight * 0.95) {
                acceptBtn.classList.add('active');
            }

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

        // Função para imprimir os termos
        function printTerms() {
            window.print();
        }

        // Função para simular aceitação dos termos
        function acceptTerms() {
            if (document.getElementById('acceptBtn').classList.contains('active')) {
                /*
                // Validação da assinatura digital (descomente para usar)
                const signatureInput = document.getElementById('signatureInput');
                const signatureError = document.getElementById('signatureError');
                if (!signatureInput || signatureInput.value.trim() === '') {
                    signatureError.style.display = 'block';
                    return;
                }
                localStorage.setItem('termsSignature', signatureInput.value.trim());
                */

                const acceptModal = new bootstrap.Modal(document.getElementById('acceptModal'));
                acceptModal.show();
                // Aqui você pode redirecionar ou salvar a aceitação (ex.: backend ou localStorage)
            }
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