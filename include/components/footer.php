<!-- ══ Footer ════════════════════════════════════════════════════════════ -->
<footer class="bg-light-100 pt-7" role="contentinfo" aria-label="Rodapé do site">
    <div class="container">
        <!-- Newsletter -->
        <div class="row align-items-center mb-7 border-bottom border-white-10 pb-5">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h3 class="fw-bold mb-1">Junte-se a +10.000 Artistas</h3>
                <p class="lead text-muted mb-0">
                    Receba dicas de marketing, novidades da indústria e ofertas exclusivas.
                </p>
            </div>
            <div class="col-lg-6">
                <form action="#" class="row g-2">
                    <div class="col-sm-8">
                        <input type="email" class="form-control border-0 text-muted py-3" autocomplete="email" required
                            placeholder="Seu melhor e-mail" />
                    </div>
                    <div class="col-sm-4">
                        <button class="btn btn-wasomupfy w-100 py-3 fw-bold">Inscrever</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Links -->
        <nav aria-label="Navegação do rodapé">
            <div class="row g-5" id="ft-links">
                <!-- Logo + Redes -->
                <div class="col-lg-3 col-12">
                    <a href="home" class="d-inline-block mb-4 navbar-brand">
                        <img src="assets/img/brand/wasomupfy_brand.png" alt="<?php echo $siteName; ?>" width="65"
                            class="img-logo" height="60" />
                    </a>
                    <p class="lead text-muted small mb-4">
                        Levamos a música angolana para o mundo. Distribuição digital, marketing e gestão de carreira
                        num só lugar.
                    </p>
                    <div class="d-flex gap-3" role="list" aria-label="Redes sociais">
                        <?php if (cfg('instagram_url')): ?>
                        <a href="<?php echo htmlspecialchars(cfg('instagram_url')); ?>" target="_blank"
                            rel="external noopener noreferrer" aria-label="Instagram"
                            class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (cfg('facebook_url')): ?>
                        <a href="<?php echo htmlspecialchars(cfg('facebook_url')); ?>" target="_blank"
                            rel="external noopener noreferrer" aria-label="Facebook"
                            class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (cfg('youtube_url')): ?>
                        <a href="<?php echo htmlspecialchars(cfg('youtube_url')); ?>" target="_blank"
                            rel="external noopener noreferrer" aria-label="YouTube"
                            class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (cfg('linkedin_url')): ?>
                        <a href="<?php echo htmlspecialchars(cfg('linkedin_url')); ?>" target="_blank"
                            rel="external noopener noreferrer" aria-label="LinkedIn"
                            class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($whatsNum): ?>
                        <a href="https://wa.me/<?php echo $whatsNum; ?>" target="_blank"
                            rel="external noopener noreferrer" aria-label="WhatsApp"
                            class="btn btn-wasomupfy btn-social rounded-circle p-2" role="listitem">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Empresa -->
                <div class="col-lg-3 col-6">
                    <h3 class="fw-bold mb-3">Empresa</h3>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><a href="about" class="text-reset text-decoration-none hover-white">Sobre</a>
                        </li>
                        <li class="mb-2"><a href="about#nossamarca"
                                class="text-reset text-decoration-none hover-white">A nossa marca</a></li>
                        <li class="mb-2"><a href="plan/all-plans"
                                class="text-reset text-decoration-none hover-white">Planos</a></li>
                        <li class="mb-2"><a href="page/services/customized-services"
                                class="text-reset text-decoration-none hover-white">Serviços Premium</a></li>
                    </ul>
                </div>

                <!-- Suporte -->
                <div class="col-lg-3 col-6">
                    <h3 class="fw-bold mb-3">Suporte</h3>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <a href="https://www.facebook.com/m.me/2007900989425052" target="_blank"
                                rel="external noopener noreferrer"
                                class="text-reset text-decoration-none hover-white">Atendimento</a>
                        </li>
                        <li class="mb-2"><a href="page/support/help"
                                class="text-reset text-decoration-none hover-white">Ajuda</a></li>
                        <li class="mb-2"><a href="contact"
                                class="text-reset text-decoration-none hover-white">Contacta-nos</a></li>
                        <?php if ($whatsNum): ?>
                        <li class="mb-2">
                            <a href="https://wa.me/<?php echo $whatsNum; ?>"
                                class="text-reset text-decoration-none hover-white">WhatsApp</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Contacto -->
                <div class="col-lg-3 col-12">
                    <h3 class="fw-bold mb-3">Contacto</h3>
                    <ul class="list-unstyled mb-0 text-muted small">
                        <li class="mb-3 d-flex">
                            <span><?php echo htmlspecialchars(cfg('company_country', 'Angola')); ?> —
                                <?php echo htmlspecialchars(cfg('company_city', 'Luanda')); ?></span>
                        </li>
                        <?php if (cfg('info_email')): ?>
                        <li class="mb-3 d-flex">
                            <a href="mailto:<?php echo htmlspecialchars(cfg('info_email')); ?>"
                                class="text-reset text-decoration-none"><?php echo htmlspecialchars(cfg('info_email')); ?></a>
                        </li>
                        <?php endif; ?>
                        <?php if (cfg('support_email')): ?>
                        <li class="mb-3 d-flex">
                            <a href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"
                                class="text-reset text-decoration-none"><?php echo htmlspecialchars(cfg('support_email')); ?></a>
                        </li>
                        <?php endif; ?>
                        <li class="d-flex"><span>Seg — Sex: 08h às 17h</span></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Copyright -->
        <div class="row py-4 mt-6 border-top border-white-10 align-items-center">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <p class="text-muted small mb-0">
                    &copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. Todos os direitos reservados.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <ul class="list-inline mb-0 small">
                    <li class="list-inline-item">
                        <a href="page/politicies/privacy" class="text-reset text-decoration-none">Política de
                            Privacidade</a>
                    </li>
                    <li class="list-inline-item mx-2 text-white-10">|</li>
                    <li class="list-inline-item">
                        <a href="page/politicies/terms" class="text-reset text-decoration-none">Termos de Uso</a>
                    </li>
                    <li class="list-inline-item mx-2 text-white-10">|</li>
                    <li class="list-inline-item">
                        <a href="page/politicies/cookies" class="text-reset text-decoration-none">Cookies</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>