<footer id="footer" class="footer">
  <div class="footer-line"></div>

  <div class="container footer-top">
    <div class="row gy-4">
      <div class="col-lg-4 col-md-6 footer-about">
        <h4 class="logo-footer">
          <span class="verde">Show</span><span class="rosa">Me</span>
        </h4>
        <p>Democratizando o acesso à cultura desde 2026.</p>
        <p><a href="<?= isset($_SESSION['id_user']) ? 'inicio.php' : 'index.php' ?>">Voltar</a> para o início</p>

        <div class="social-links">
          <a href="https://www.instagram.com/showmetcc/" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" aria-label="X"><i class="bi bi-twitter-x"></i></a>
          <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
        </div>
      </div>

      <div class="col-lg-4 col-md-6">
        <h4>Entre em Contato</h4>
        <form class="footer-contact-form">
          <input type="email" placeholder="Seu e-mail" aria-label="Seu e-mail">
          <textarea placeholder="Sua mensagem" aria-label="Sua mensagem"></textarea>
          <button type="submit">
            <i class="bi bi-envelope"></i>
            Enviar
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
