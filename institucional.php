<?php session_start(); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Showme</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/showme.png" rel="icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- CSS específico da página -->
  <link href="assets/css/institucional.css" rel="stylesheet">
 


  <!-- CSS compartilhado por último para preservar os cabeçalhos -->
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page pagina-sobre">

  <?php
  $tipoCabecalho = 'B';
  require __DIR__ . '/cabecalho.php';
  ?>


  <main class="main">

    <!-- Banner Inicio linha 858-->
    <section id="hero" class="hero section dark-background">
      <video autoplay muted loop width="100%">
        <source src="assets/video/bannerVideo.mp4" type="video/mp4">
      </video>
      <div class="row gy-4">
        <div class="container">
          <div class="" data-aos="zoom-out">

            <h1 class="h1banner">Viva Experiências</h1>
            <p class="pbanner">Democratizando o acesso à cultura e conectando você aos melhores eventos</p>
            <a href="#sobre"><i class="bi bi-chevron-down"></i></a>
          </div>
        </div>
        <div class="col-lg-6 order-1 order-lg-2 hero-img" data-aos="zoom-out" data-aos-delay="200">
        </div>
      </div>
      </div>

    </section><!-- /Hero Section -->



    <!-- About Section -->
    <section id="about" class="about section">
      <span id="sobre" class="ancora-secao" aria-hidden="true"></span>

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">


        <h2>Sobre o <span class="verde">Show</span><span class="rosa">Me</span></h2>
      </div><!-- End Section Title -->

      <div class="container">
        <div class="row gy-4">
          <div class="col-12 content" data-aos="fade-up" data-aos-delay="100">
            <p class="pabout">
              O ShowMe nasceu com a missão de democratizar o acesso à cultura, removendo barreiras e facilitando o
              planejamento completo da sua experiência cultural. Acreditamos que todos devem ter acesso a shows, eventos
              e experiências que enriquecem nossas vidas.
            </p>
          </div>
        </div>
      </div>

    </section><!-- /About Section -->


    <!-- ODS Section -->
    <section id="services" class="services section light-background">

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <button type="button" id="ods3" class="service-item ods-card" data-ods-modal="modalOds3" aria-controls="modalOds3" aria-haspopup="dialog">
              <h4>ODS 3</h4>
              <p>Saúde e Bem-Estar</p>
            </button>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <button type="button" id="ods4" class="service-item ods-card" data-ods-modal="modalOds4" aria-controls="modalOds4" aria-haspopup="dialog">
              <h4>ODS 4</h4>
              <p>Educação de Qualidade</p>
            </button>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <button type="button" id="ods10" class="service-item ods-card" data-ods-modal="modalOds10" aria-controls="modalOds10" aria-haspopup="dialog">
              <h4>ODS 10</h4>
              <p>Redução das Desigualdades</p>
            </button>
          </div>
        </div>
      </div>

    </section><!-- /Services Section -->

    <?php require __DIR__ . '/odsModais.php'; ?>

    <!-- Work Process Section -->
    <section id="work-process" class="work-process section">
      <span id="como-funciona" class="ancora-secao" aria-hidden="true"></span>


      <!-- Como Funciona -->
      <div class="container section-title" data-aos="fade-up">
        <h2><span class="verde">Como</span> Funciona</h2>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-5">

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="steps-item">
              <div class="steps-image">
                <span class="icone-etapa icone-etapa-descubra" role="img" aria-label="Ícone musical"></span>
              </div>
              <div class="steps-content">
                <h3><span class="verde">Descubra Eventos</span></h3>
                <p>Explore eventos culturais perto de você ou em qualquer lugar do Brasil</p>
                <div class="steps-features">
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Mais Acessibilidade</span>
                  </div>
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Eventos Próximos</span>
                  </div>
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Diversas Categorias</span>
                  </div>
                </div>
              </div>
            </div><!-- End Steps Item -->
          </div>

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="steps-item">
              <div class="steps-image">
                <span class="icone-etapa icone-etapa-planeje" role="img" aria-label="Ícone de calendário"></span>
              </div>
              <div class="steps-content">
                <h3><span class="rosa"> Planeje Viagens</span></h3>
                <p>Calcule transporte, hospedagem e organize seu orçamento completo</p>
                <div class="steps-features">
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Cálculo de Gastos</span>
                  </div>
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Planejamento Completo</span>
                  </div>
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Organização Simplificada</span>
                  </div>
                </div>
              </div>
            </div><!-- End Steps Item -->
          </div>

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="steps-item">
              <div class="steps-image">
                <span class="icone-etapa icone-etapa-rotas" role="img" aria-label="Ícone de localização"></span>
              </div>
              <div class="steps-content">
                <h3><span class="verde"> Rotas e Locais</span> </h3>
                <p>Veja rotas otimizadas e descubra o melhor caminho até o evento</p>
                <div class="steps-features">
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Locais Verificados</span>
                  </div>
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Descubra Locais</span>
                  </div>
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Locais Recomendados</span>
                  </div>
                </div>
              </div>
            </div><!-- End Steps Item -->
          </div>

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="steps-item">
              <div class="steps-image">
                <span class="icone-etapa icone-etapa-compartilhe" role="img" aria-label="Ícone de pessoas"></span>
              </div>
              <div class="steps-content">
                <h3><span class="rosa">Compartilhe</span></h3>
                <p>Converse nas comunidades dos eventos e compartilhe suas experiências</p>
                <div class="steps-features">
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Compartilhamento de Experiências</span>
                  </div>
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Conversas por evento</span>
                  </div>
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Comunidade Ativa</span>
                  </div>
                </div>
              </div>
            </div><!-- End Steps Item -->
          </div>


        </div>

      </div>

    </section><!-- /Work Process Section -->






    <!-- Clube de Fidelidade Section -->
    <section id="fidelidade" class="clube-fidelidade section">
      <div class="container section-title" data-aos="fade-up">
        <h2>
          <span class="verde">Clube de Fidelidade</span>
          <span class="rosa">ShowMe+</span>
        </h2>
        <p class="clube-fidelidade-introducao">
          Viva mais experiências culturais com o ShowMe+. Assinantes têm acesso a descontos e cupons em parceiros,
          redução ou isenção de determinadas taxas quando houver integração comercial com parceiros, sorteios e
          experiências exclusivas em eventos, além de alertas antecipados sobre vendas, eventos e oportunidades.
        </p>
      </div>

      <div class="container clube-fidelidade-planos">
        <div class="row g-4 justify-content-center">
          <div class="col-12 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <article class="plano-fidelidade plano-mensal">
              <h3>Plano mensal</h3>
              <p class="plano-fidelidade-subtitulo">Flexibilidade para acompanhar seu ritmo.</p>

              <p class="plano-fidelidade-preco">
                <strong>R$ 15,90</strong>
                <span>/ mês</span>
              </p>
              <p class="plano-fidelidade-cobranca">Cobrança mensal.</p>

              <div class="plano-fidelidade-divisor" aria-hidden="true"></div>

              <p class="plano-fidelidade-beneficio">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>Todos os benefícios do ShowMe+</span>
              </p>
            </article>
          </div>

          <div class="col-12 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <article class="plano-fidelidade plano-anual">
              <span class="plano-fidelidade-badge">Economize R$ 36/ano</span>

              <h3>Plano anual</h3>
              <p class="plano-fidelidade-subtitulo">Os mesmos benefícios, por menos.</p>

              <p class="plano-fidelidade-preco">
                <strong>R$ 12,90</strong>
                <span>/ mês</span>
              </p>
              <p class="plano-fidelidade-cobranca">R$ 154,80 cobrados de uma só vez por ano.</p>

              <div class="plano-fidelidade-divisor" aria-hidden="true"></div>

              <p class="plano-fidelidade-beneficio">
                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                <span>Todos os benefícios do ShowMe+</span>
              </p>
            </article>
          </div>
        </div>
      </div>
    </section><!-- /Clube de Fidelidade Section -->

    <!-- Faq 2 Section -->
    <section id="faq-2" class="faq-2 section">
      <span id="perguntas" class="ancora-secao" aria-hidden="true"></span>


      <div class="container section-title" data-aos="fade-up">
        <h2>Perguntas <span class="verde">Frequentes</span></h2>
      </div>

      <div class="container">

        <div class="row justify-content-center">

          <div class="col-lg-10">

            <div class="faq-container">


              <div class="faq-item faq-active" data-aos="fade-up" data-aos-delay="200">

                <i class="faq-icon bi bi-question-circle"></i>

                <h3>Como funciona o ShowMe?</h3>

                <i class="faq-toggle bi bi-chevron-right"></i>

                <div class="faq-content">
                  <p>
                    O ShowMe conecta você a eventos culturais e ajuda a planejar toda a experiência, desde transporte
                    até hospedagem.
                  </p>
                </div>

              </div>

              <!-- Pergunta2 -->
              <div class="faq-item" data-aos="fade-up" data-aos-delay="300">

                <i class="faq-icon bi bi-question-circle"></i>

                <h3>É gratuito?</h3>

                <i class="faq-toggle bi bi-chevron-right"></i>

                <div class="faq-content">
                  <p>
                    Sim! O ShowMe é totalmente gratuito. Você só paga pelos ingressos, transporte e hospedagem que
                    escolher.
                  </p>
                </div>

              </div>

              <!-- 3 -->
              <div class="faq-item" data-aos="fade-up" data-aos-delay="400">

                <i class="faq-icon bi bi-question-circle"></i>

                <h3>Posso comprar ingressos pelo site?</h3>

                <i class="faq-toggle bi bi-chevron-right"></i>

                <div class="faq-content">
                  <p>
                    O ShowMe não vende ingressos diretamente, mas direciona você para os canais oficiais de compra.
                  </p>
                </div>

              </div>


              <div class="faq-item" data-aos="fade-up" data-aos-delay="500">

                <i class="faq-icon bi bi-question-circle"></i>

                <h3>Como faço para salvar eventos?</h3>

                <i class="faq-toggle bi bi-chevron-right"></i>

                <div class="faq-content">
                  <p>
                    Crie uma conta e você poderá favoritar eventos e montar planejamentos completos para suas viagens e
                    passeios.
                  </p>
                </div>

              </div>

            </div>

          </div>

        </div>

      </div>

    </section>


  </main>

  <?php require __DIR__ . '/rodape.php'; ?>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i
      class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/waypoints/noframework.waypoints.js"></script>
  <script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>
  <script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/odsModais.js"></script>
  <script src="assets/js/main.js"></script>

</body>

</html>
