 <!-- Preloader -->
 <div class="preloader">
     <img src="assets/img/brand/wasomupfy_loaading.png" class="img-fluid loading-logo" width="90" height="90"
         alt="Loading-wasomupfy" />
 </div>

 <!-- Navbar -->
 <header>
     <nav class="navbar navbar-expand-lg transparent navbar-transparent navbar-dark">
         <div class="container px-3">
             <a class="navbar-brand" href="<?php echo  APP_URL ?>/home" title="Home">
                 <img src="assets/img/brand/wasomupfy_brand.png" width="65" class="img-logo" height="60"
                     alt="Logo <?php echo $siteName; ?>" />
             </a>
             <button class="navbar-toggler offcanvas-nav-btn" type="button"><i class="bi bi-list"></i></button>
             <div class="offcanvas offcanvas-start offcanvas-nav" style="width: 20rem">
                 <div class="offcanvas-header">
                     <a title="Logotipo" href="<?php echo  APP_URL ?>/home">
                         <img width="65" src="assets/img/brand/wasomupfy_brand.png"
                             alt="Logo <?php echo $siteName; ?>" />
                     </a>
                     <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
                 </div>
                 <div class="offcanvas-body pt-0 align-items-center">
                     <ul class="navbar-nav mx-auto align-items-lg-center">
                         <li class="nav-item">
                             <a class="nav-link active" href="home" title="Início">Início</a>
                         </li>
                         <li class="nav-item">
                             <a class="nav-link" href="about" title="Sobre">Sobre</a>
                         </li>
                         <li class="nav-item">
                             <a class="nav-link" href="blog/" title="Blogue" target="_blank" rel="external">Blogue</a>
                         </li>

                         <!-- Planos — dinâmico -->
                         <li class="nav-item dropdown">
                             <a title="Planos" class="nav-link" href="#" id="navbarDropdown" role="button"
                                 data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                 Planos <i data-feather="chevron-down"></i>
                             </a>
                             <div class="dropdown-menu dropdown-menu-md" aria-labelledby="navbarDropdown">
                                 <?php
                                    $navIcons = ['single' => 'fa-music', 'album' => 'fa-compact-disc', 'artist' => 'fa-microphone-lines', 'label' => 'fa-tags'];
                                    foreach ($plans as $p):
                                        $nSlug = $p['slug_plan'];
                                        $nIcon = $navIcons[$nSlug] ?? 'fa-music';
                                        $nPrc  = number_format($p['price_plan'], 0, ',', '.');
                                        $nPer  = $p['type_plan'] === 'subscription' ? '/ano' : '';
                                    ?>
                                     <a title="<?php echo htmlspecialchars($p['name_plan']); ?>"
                                         class="dropdown-item mb-3 text-body" href="plan/<?php echo $nSlug; ?>">
                                         <div class="d-flex align-items-center">
                                             <i class="fa-solid <?php echo $nIcon; ?> text-wasomupfy fs-3"
                                                 style="width:35px"></i>
                                             <div class="ms-3 lh-1">
                                                 <h5 class="mb-1"><?php echo htmlspecialchars($p['name_plan']); ?></h5>
                                                 <p class="mb-0 fs-6">Nosso plano
                                                     <?php echo htmlspecialchars($p['name_plan']); ?> —
                                                     <?php echo $nPrc; ?> Kz<?php echo $nPer; ?></p>
                                             </div>
                                         </div>
                                     </a>
                                 <?php endforeach; ?>
                                 <a title="Todos os planos" class="dropdown-item mb-3 text-body" href="plan/all-plans">
                                     <div class="d-flex align-items-center">
                                         <i class="fa-solid fa-layer-group text-wasomupfy fs-3" style="width:35px"></i>
                                         <div class="ms-3 lh-1">
                                             <h5 class="mb-1">Todos os planos</h5>
                                             <p class="mb-0 fs-6">Todos os nossos planos</p>
                                         </div>
                                     </div>
                                 </a>
                             </div>
                         </li>

                         <li class="nav-item dropdown">
                             <a title="Páginas" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                 aria-expanded="false">
                                 Páginas <i data-feather="chevron-down"></i>
                             </a>
                             <div class="dropdown-menu dropdown-menu-xxl">
                                 <div class="row row-cols-lg-3">
                                     <div class="col">
                                         <div class="dropdown-header">Blog</div>
                                         <a title="Novidades" class="dropdown-item" href="blog/">Novidades</a>
                                         <a title="Passatempo" class="dropdown-item" href="blog/">Passatempo</a>
                                         <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                             <span class="badge bg-warning">Indisponível</span></a>
                                         <div class="mt-3">
                                             <div class="dropdown-header">Sobre</div>
                                             <a title="A nossa marca" class="dropdown-item" href="about?#nossamarca">A
                                                 nossa marca</a>
                                             <a title="Parcerias" class="dropdown-item" href="partnership">Parcerias</a>
                                             <a title="Quem somos" class="dropdown-item"
                                                 href="about#nossa-historia">Quem somos</a>
                                         </div>
                                     </div>
                                     <div class="col">
                                         <div class="mt-3 mt-lg-0">
                                             <div class="dropdown-header">Serviços</div>
                                             <a title="Distribuição de música" class="dropdown-item"
                                                 href="page/services/music-distribution">Distribuição de música</a>
                                             <a title="Promoção de música" class="dropdown-item"
                                                 href="page/services/music-promotion">Promoção de música
                                                 <span class="badge bg-success">Novo</span></a>
                                             <a title="Serviços Personalizados" class="dropdown-item"
                                                 href="page/services/customized-services">Serviços personalizados
                                                 <span class="badge bg-warning">Em breve</span></a>
                                         </div>
                                         <div class="mt-3">
                                             <div class="dropdown-header">Contactos</div>
                                             <a title="Atendimento pelo Facebook" class="dropdown-item"
                                                 href="https://www.facebook.com/m.me/2007900989425052" target="_blank"
                                                 rel="external noopener noreferrer">Atendimento</a>
                                             <a title="Contacta-nos" class="dropdown-item"
                                                 href="contact">Contacta-nos</a>
                                             <a title="Canal WhatsApp" class="dropdown-item"
                                                 href="<?php echo htmlspecialchars($whatsChannel); ?>" target="_blank"
                                                 rel="external noopener noreferrer">Canal
                                                 WhatsApp</a>
                                         </div>
                                     </div>
                                     <div class="col">
                                         <div class="mt-3 mt-lg-0">
                                             <div class="dropdown-header">Sugestões</div>
                                             <a title="Ajuda" class="dropdown-item" href="page/support/help">Ajuda
                                                 <span class="badge bg-success">Novo</span></a>
                                             <a title="Feedback" class="dropdown-item" href="#" data-bs-toggle="modal"
                                                 data-bs-target="#modalFeedback">Feedback</a>
                                             <a title="Indisponível" class="dropdown-item" href="#!">Indisponível
                                                 <span class="badge bg-warning">Indisponível</span></a>
                                             <div class="mt-3">
                                                 <div class="dropdown-header">Ajuda</div>
                                                 <a title="Tutorial" class="dropdown-item"
                                                     href="page/support/tutorial">Tutorial
                                                     <span class="badge bg-success">Novo</span></a>
                                                 <a title="Suporte técnico" class="dropdown-item"
                                                     href="page/support/support">Suporte técnico</a>
                                                 <a title="Perguntas frequentes" class="dropdown-item"
                                                     href="page/support/faq">Perguntas frequentes</a>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </li>

                         <li class="nav-item">
                             <a class="nav-link" href="resources" title="Recursos">Recursos</a>
                         </li>

                         <li class="nav-item dropdown">
                             <a title="Contactar" class="nav-link" href="#" role="button" data-bs-toggle="dropdown"
                                 aria-expanded="false">
                                 Contactar <i data-feather="chevron-down"></i>
                             </a>
                             <ul class="dropdown-menu">
                                 <li><a title="Caixa de mensagem" class="dropdown-item" href="contact">Caixa de
                                         mensagem</a></li>
                                 <?php if (cfg('support_email')): ?>
                                     <li><a title="E-mail" class="dropdown-item"
                                             href="mailto:<?php echo htmlspecialchars(cfg('support_email')); ?>"><?php echo htmlspecialchars(cfg('support_email')); ?></a>
                                     </li>
                                 <?php endif; ?>
                                 <?php if ($whatsNum): ?>
                                     <li><a title="WhatsApp" class="dropdown-item"
                                             href="https://api.whatsapp.com/send/?phone=<?php echo $whatsNum; ?>&text&type=phone_number&app_absent=0">
                                             WhatsApp</a></li>
                                 <?php endif; ?>
                             </ul>
                         </li>
                     </ul>

                     <div class="mt-3 mt-lg-0 d-flex align-items-center">
                         <a title="Sign-in" href="/wasomupfy/login" class="btn btn-secondary mx-2">
                             Entrar <i data-feather="log-in"></i>
                         </a>
                         <?php if ($canRegister): ?>
                             <a title="Sign-up" href="/wasomupfy/register" class="btn btn-wasomupfy">Inscreva-se</a>
                         <?php else: ?>
                             <span class="btn btn-secondary disabled">Inscrições fechadas</span>
                         <?php endif; ?>
                     </div>
                 </div>
             </div>
         </div>
     </nav>
 </header>