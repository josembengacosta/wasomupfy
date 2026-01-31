/**
 * MELOOU - Landing Page Script
 * Funcionalidades principais:
 * 1. Contagem regressiva
 * 2. Formulário de waitlist
 * 3. Animações e interações
 * 4. Menu mobile
 */

document.addEventListener('DOMContentLoaded', function() {
    // ===== INICIALIZAÇÃO =====
    initApp();
    
    // ===== FUNÇÕES PRINCIPAIS =====
    function initApp() {
        setupMobileMenu();
        setupCountdown();
        setupWaitlistForm();
        setupAnimations();
        updateCurrentYear();
        setupStatCounters();
    }
    
    // ===== MENU MOBILE =====
    function setupMobileMenu() {
        const menuToggle = document.querySelector('.menu-toggle');
        const navLinks = document.querySelector('.nav-links');
        
        if (!menuToggle || !navLinks) return;
        
        menuToggle.addEventListener('click', function() {
            navLinks.classList.toggle('mobile-open');
            const icon = this.querySelector('i');
            
            if (navLinks.classList.contains('mobile-open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
                document.body.style.overflow = 'hidden';
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
                document.body.style.overflow = '';
            }
        });
        
        // Fechar menu ao clicar em um link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('mobile-open');
                menuToggle.querySelector('i').classList.remove('fa-times');
                menuToggle.querySelector('i').classList.add('fa-bars');
                document.body.style.overflow = '';
            });
        });
        
        // Fechar menu ao redimensionar para desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 640) {
                navLinks.classList.remove('mobile-open');
                menuToggle.querySelector('i').classList.remove('fa-times');
                menuToggle.querySelector('i').classList.add('fa-bars');
                document.body.style.overflow = '';
            }
        });
    }
    
    // ===== CONTAGEM REGRESSIVA =====
    function setupCountdown() {
        const daysEl = document.getElementById('days');
        const hoursEl = document.getElementById('hours');
        const minutesEl = document.getElementById('minutes');
        const secondsEl = document.getElementById('seconds');
        
        if (!daysEl || !hoursEl || !minutesEl || !secondsEl) return;
        
        // Data de lançamento: 90 dias a partir de hoje
        const launchDate = new Date();
        launchDate.setDate(launchDate.getDate() + 90);
        
        function updateCountdown() {
            const now = new Date();
            const timeLeft = launchDate - now;
            
            if (timeLeft <= 0) {
                // Lançamento chegou
                daysEl.textContent = '00';
                hoursEl.textContent = '00';
                minutesEl.textContent = '00';
                secondsEl.textContent = '00';
                return;
            }
            
            const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            daysEl.textContent = days.toString().padStart(2, '0');
            hoursEl.textContent = hours.toString().padStart(2, '0');
            minutesEl.textContent = minutes.toString().padStart(2, '0');
            secondsEl.textContent = seconds.toString().padStart(2, '0');
        }
        
        // Atualizar imediatamente e a cada segundo
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
    
    // ===== FORMULÁRIO DE WAITLIST =====
    function setupWaitlistForm() {
        const form = document.getElementById('waitlist-form');
        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('email-error');
        const successMessage = document.getElementById('success-message');
        
        if (!form || !emailInput) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Reset estados
            emailError.style.display = 'none';
            emailInput.classList.remove('error');
            
            // Validação de email
            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!email) {
                showError(emailInput, emailError, 'Por favor, insira o seu email');
                return;
            }
            
            if (!emailRegex.test(email)) {
                showError(emailInput, emailError, 'Por favor, insira um email válido');
                return;
            }
            
            // Simular envio (em produção, aqui seria uma chamada AJAX)
            simulateSubmission();
        });
        
        function showError(input, errorEl, message) {
            errorEl.textContent = message;
            errorEl.style.display = 'block';
            input.classList.add('error');
            input.focus();
        }
        
        function simulateSubmission() {
            // Desabilitar formulário
            const submitBtn = form.querySelector('.btn-submit');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A enviar...';
            submitBtn.disabled = true;
            
            // Simular delay de rede
            setTimeout(() => {
                // Mostrar mensagem de sucesso
                form.style.display = 'none';
                successMessage.style.display = 'flex';
                
                // Resetar formulário (para demonstração)
                setTimeout(() => {
                    form.reset();
                    form.style.display = 'block';
                    successMessage.style.display = 'none';
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    
                    // Feedback visual
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Inscrito!';
                    submitBtn.style.backgroundColor = '#10B981';
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.style.backgroundColor = '';
                    }, 2000);
                }, 5000);
            }, 1500);
            
            // Em produção: enviar para backend
            // fetch('/api/waitlist', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({
            //         email: emailInput.value.trim(),
            //         newsletter: document.getElementById('newsletter').checked
            //     })
            // })
        }
    }
    
    // ===== ANIMAÇÕES =====
    function setupAnimations() {
        // Observador de interseção para animações no scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observar elementos para animação
        document.querySelectorAll('.feature-card, .stat-card, .roadmap-phase').forEach(el => {
            observer.observe(el);
        });
        
        // Animar elementos do hero com delay
        const heroElements = document.querySelectorAll('.hero-badge, .hero-title, .hero-subtitle, .hero-actions');
        heroElements.forEach((el, index) => {
            el.classList.add('fade-in-up', `delay-${index + 1}`);
        });
    }
    
    // ===== ANIMAÇÃO DOS CONTADORES DE ESTATÍSTICAS =====
    function setupStatCounters() {
        const statNumbers = document.querySelectorAll('.stat-number[data-count]');
        
        statNumbers.forEach(stat => {
            const target = parseInt(stat.getAttribute('data-count'));
            const duration = 2000; // 2 segundos
            const step = target / (duration / 16); // 60fps
            
            let current = 0;
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                stat.textContent = Math.floor(current).toLocaleString();
            }, 16);
        });
    }
    
    // ===== ATUALIZAR ANO NO FOOTER =====
    function updateCurrentYear() {
        const yearElement = document.getElementById('current-year');
        if (yearElement) {
            yearElement.textContent = new Date().getFullYear();
        }
    }
    
    // ===== SCROLL SUAVE PARA LINKS INTERNOS =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            if (href === '#') return;
            
            e.preventDefault();
            const targetElement = document.querySelector(href);
            
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // ===== LOG DE INICIALIZAÇÃO =====
    console.log('🎵 MELOOU Landing Page inicializada com sucesso!');
    console.log('🚀 Pronta para produção');
});