 // Theme Toggle with Persistence
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');

        // Função para aplicar o tema
        function applyTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-mode');
                document.body.classList.remove('light-mode');
                themeIcon.classList.remove('bi-sun');
                themeIcon.classList.add('bi-moon');
            } else {
                document.body.classList.add('light-mode');
                document.body.classList.remove('dark-mode');
                themeIcon.classList.remove('bi-moon');
                themeIcon.classList.add('bi-sun');
            }
        }

        // Carregar tema salvo ou usar preferência do sistema como fallback
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                applyTheme(savedTheme);
            } else {
                // Fallback para preferência do sistema
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                applyTheme(prefersDark ? 'dark' : 'light');
            }
        });

        // Alternar tema ao clicar no botão e salvar no localStorage
        themeToggle.addEventListener('click', () => {
            const isDarkMode = document.body.classList.contains('dark-mode');
            const newTheme = isDarkMode ? 'light' : 'dark';
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });