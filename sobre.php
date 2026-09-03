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
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Principal CSS File -->
  <link href="assets/css/sobre.css" rel="stylesheet">
 


  <!--Google fontes, Anton-->
</head>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
  href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Libertinus+Serif+Display&family=Noto+Sans+JP:wght@100..900&family=Noto+Serif:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Story+Script&family=Vend+Sans:ital,wght@0,300..700;1,300..700&display=swap"
  rel="stylesheet">

</head>

<body class="index-page">

  <?php require __DIR__ . '/cabecalho.php'; ?>


  <main class="main">

    <!-- Banner Inicio linha 858-->
    <section id="hero" class="hero section dark-background">
      <video autoplay muted loop width="100%">
        <source src="assets/bannervideo.mp4" type="video/mp4">
      </video>
      <div class="row gy-4">
        <div class="container">
          <div class="" data-aos="zoom-out">

            <h1 class="h1banner">Viva Experiências</h1>
            <p class="pbanner">Democratizando o acesso à cultura e conectando você aos melhores eventos</p>
            <a href="#about"><i class="bi bi-chevron-down"></i></a>
          </div>
        </div>
        <div class="col-lg-6 order-1 order-lg-2 hero-img" data-aos="zoom-out" data-aos-delay="200">
        </div>
      </div>
      </div>

    </section><!-- /Hero Section -->



    <!-- About Section -->
    <section id="about" class="about section">

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
            <div id="ods3" class="service-item">
              <h4>ODS 3</h4>
              <p>Saúde e Bem-Estar</p>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div id="ods4" class="service-item">
              <h4>ODS 4</h4>
              <p>Educação de Qualidade</p>
            </div>
          </div>

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div id="ods16" class="service-item">
              <h4>ODS 16</h4>
              <p>Paz, Justiça e Instituições Efetivas</p>
            </div>
          </div>
        </div>
      </div>

    </section><!-- /Services Section -->

    <!-- Work Process Section -->
    <section id="work-process" class="work-process section">


      <!-- Como Funciona -->
      <div class="container section-title" data-aos="fade-up">
        <h2><span class="verde">Como</span> Funciona</h2>
      </div>

      <div class="container" data-aos="fade-up" data-aos-delay="100">

        <div class="row gy-5">

          <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="steps-item">
              <div class="steps-image">
                <img src="assets/img/services/2.png" alt="Step 1" class="img-fluid" loading="lazy">
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
                <img src="assets/img/services/3.png" alt="Step 2" class="img-fluid" loading="lazy">
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
                <img src="assets/img/services/4.png" alt="Step 3" class="img-fluid" loading="lazy">
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
                <img src="assets/img/services/1.png" alt="Step 3" class="img-fluid" loading="lazy">
              </div>
              <div class="steps-content">
                <h3><span class="rosa">Compartilhe</span></h3>
                <p>Veja avaliações de outros usuários e compartilhe suas experiências</p>
                <div class="steps-features">
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Compartilhamento de Experiências</span>
                  </div>
                  <div class="feature-item">
                    <i class="bi bi-check-circle"></i>
                    <span>Avaliações Confiáveis</span>
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






    <!-- Faq 2 Section -->
    <section id="faq-2" class="faq-2 section">


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
  <script src="assets/js/main.js"></script>

</body>

</html>
