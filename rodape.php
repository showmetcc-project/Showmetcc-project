<footer id="footer" class="footer">
  <div class="footer-line"></div>

  <div class="container footer-top">
    <div class="row gy-4">
      <div class="col-lg-4 col-md-6 footer-about">
        <h4 class="logo-footer">
          <span class="verde">Show</span><span class="rosa">Me</span>
        </h4>
        <p>Democratizando o acesso à cultura desde 2026.</p>
        <div class="social-links">
          <a href="https://www.instagram.com/showmetcc/" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" aria-label="X"><i class="bi bi-twitter-x"></i></a>
          <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <h4>Entre em Contato</h4>
        <form class="footer-contact-form" action="forms/contact.php" method="post">
          <input
            type="text"
            name="nome"
            placeholder="Seu nome"
            aria-label="Seu nome"
            minlength="2"
            maxlength="100"
            required
          >
          <input
            type="email"
            name="email"
            placeholder="Seu e-mail"
            aria-label="Seu e-mail"
            maxlength="254"
            required
          >
          <textarea
            name="mensagem"
            placeholder="Sua mensagem"
            aria-label="Sua mensagem"
            minlength="10"
            maxlength="5000"
            required
          ></textarea>
          <p class="footer-contact-feedback" role="status" aria-live="polite" hidden></p>
          <button type="submit">
            <i class="bi bi-envelope"></i>
            <span>Enviar</span>
          </button>
        </form>
      </div>

      <div class="col-lg-4 col-md-12 footer-links">
        <h4>Informações</h4>
        <ul>
          <li><a href="#">Termos de Uso</a></li>
          <li><a href="#">Política de Privacidade</a></li>
        </ul>
        <p class="copyright-text">© 2026 ShowMe. Todos os direitos reservados.</p>
      </div>
    </div>
  </div>
</footer>

<script src="assets/js/contactForm.js" defer></script>
<?php require_once __DIR__ . '/acessibilidade.php'; ?>
<!-- Scroll Top -->
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><iclass="bi bi-arrow-up-short"></i></a>