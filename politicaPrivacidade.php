<?php
require_once __DIR__ . '/config/sessao.php';
iniciarSessaoSegura();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <title>Política de Privacidade - ShowMe</title>
    <link href="assets/img/showme.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/paginasLegais.css" rel="stylesheet">
</head>
<body>
<?php
$tipoCabecalho = 'D';
$configuracaoCabecalho = [
    'fallback' => 'index.php',
];
require __DIR__ . '/cabecalho.php';
?>
<main class="pagina-termos pagina-privacidade">
    <section class="termos-container" aria-labelledby="tituloPrivacidade">
        <header class="cabecalho-pagina-legal">
            <p class="rotulo-pagina-legal">Privacidade e proteção de dados</p>
            <h1 id="tituloPrivacidade">Política de Privacidade</h1>
            <p class="atualizacao-pagina-legal">Última atualização: 23 de outubro de 2026.</p>
        </header>

        <div class="conteudo-termos">
            <p>
                Esta Política de Privacidade explica como o ShowMe coleta, utiliza, armazena e
                protege os dados pessoais de quem utiliza a plataforma. Ao criar uma conta ou
                utilizar nossos recursos, você declara ter lido e compreendido estas informações.
            </p>

            <h2>1. Quem somos</h2>
            <p>
                O ShowMe é uma plataforma de divulgação, descoberta e planejamento de eventos
                culturais. Para fins desta política, o ShowMe atua como controlador dos dados
                pessoais tratados em suas próprias atividades.
            </p>
            <p>
                Canal de contato para assuntos de privacidade:
                <a href="mailto:showmetcc@gmail.com">showmetcc@gmail.com</a>.
            </p>

            <h2>2. Quais dados coletamos</h2>
            <p>Podemos coletar as seguintes categorias de dados:</p>
            <ul>
                <li>
                    <strong>Dados de cadastro:</strong> nome, sobrenome, e-mail, senha
                    armazenada em formato criptografado, tipo de conta e, quando informados,
                    foto de perfil e banner.
                </li>
                <li>
                    <strong>Dados de login com o Google:</strong> identificador da conta Google,
                    nome, e-mail e foto pública autorizada pelo usuário no momento do acesso.
                </li>
                <li>
                    <strong>Conteúdo e interações:</strong> eventos cadastrados, favoritos,
                    planejamentos de viagem, publicações na comunidade, respostas, curtidas,
                    denúncias e mensagens enviadas pelo formulário de contato.
                </li>
                <li>
                    <strong>Localização:</strong> quando você utiliza recursos de mapa, rota ou
                    “usar minha localização”, podemos processar o endereço ou as coordenadas
                    necessárias para calcular o trajeto. A localização não é monitorada de forma
                    contínua.
                </li>
                <li>
                    <strong>Dados técnicos:</strong> endereço IP, data e hora de acesso,
                    navegador, informações do dispositivo e registros necessários para segurança,
                    diagnóstico de falhas e prevenção a abusos.
                </li>
            </ul>

            <h2>3. Como utilizamos os dados</h2>
            <p>Os dados são utilizados para:</p>
            <ul>
                <li>criar, autenticar e manter sua conta;</li>
                <li>permitir a divulgação, busca e organização de eventos culturais;</li>
                <li>salvar favoritos, planejamentos e preferências de acessibilidade;</li>
                <li>viabilizar interações na comunidade e moderar conteúdos denunciados;</li>
                <li>responder solicitações enviadas pelo formulário de contato;</li>
                <li>enviar comunicações operacionais relacionadas à conta ou aos eventos;</li>
                <li>melhorar a experiência, a segurança e o desempenho da plataforma;</li>
                <li>cumprir obrigações legais e exercer direitos em processos administrativos ou judiciais.</li>
            </ul>

            <h2>4. Bases legais</h2>
            <p>
                O tratamento pode ocorrer com base na execução de contrato ou de procedimentos
                preliminares, no cumprimento de obrigação legal, no exercício regular de direitos,
                no legítimo interesse para segurança e melhoria do serviço ou mediante
                consentimento do titular, conforme a Lei Geral de Proteção de Dados Pessoais
                (Lei nº 13.709/2018).
            </p>

            <h2>5. Compartilhamento de dados</h2>
            <p>
                O ShowMe não vende dados pessoais. O compartilhamento ocorre apenas quando
                necessário para o funcionamento da plataforma, como:
            </p>
            <ul>
                <li>
                    provedores de hospedagem, banco de dados, envio de e-mail e infraestrutura;
                </li>
                <li>
                    Google, quando você opta por entrar com uma conta Google ou conectar o
                    Google Agenda;
                </li>
                <li>
                    serviços de mapas, geocodificação, rotas e hospedagem consultados durante o
                    planejamento de viagem, como OpenStreetMap/Nominatim, CARTO, BrasilAPI e OSRM;
                </li>
                <li>
                    autoridades públicas, quando houver requisição legal ou necessidade de
                    proteção de direitos.
                </li>
            </ul>
            <p>
                Links para sites de terceiros, como plataformas de hospedagem, transporte ou
                ingressos, possuem políticas de privacidade próprias. Recomendamos a leitura
                desses documentos antes de utilizar os serviços.
            </p>

            <h2>6. Cookies e armazenamento local</h2>
            <p>
                Utilizamos cookies de sessão e mecanismos de armazenamento local para manter o
                login, proteger formulários contra CSRF, registrar a última página acessada e
                guardar preferências de acessibilidade. Esses recursos podem ser bloqueados nas
                configurações do navegador, mas algumas funcionalidades podem deixar de funcionar.
            </p>

            <h2>7. Segurança das informações</h2>
            <p>
                Adotamos medidas técnicas e administrativas para proteger os dados, incluindo
                controle de acesso, validação de requisições, proteção contra CSRF e
                armazenamento de senhas por meio de hash. Nenhum sistema é completamente imune a
                incidentes; por isso, recomendamos o uso de senhas exclusivas e a adoção de
                conexões seguras.
            </p>

            <h2>8. Retenção e exclusão</h2>
            <p>
                Os dados são mantidos pelo período necessário ao funcionamento da conta e ao
                cumprimento das finalidades descritas nesta política. Também podem ser
                conservados para atender obrigações legais, resolver disputas e prevenir fraudes.
                Quando não houver mais necessidade ou base legal para a retenção, os dados serão
                eliminados ou anonimizados.
            </p>

            <h2>9. Seus direitos</h2>
            <p>Você pode solicitar, conforme a LGPD:</p>
            <ul>
                <li>confirmação da existência de tratamento e acesso aos dados;</li>
                <li>correção de dados incompletos, inexatos ou desatualizados;</li>
                <li>anonimização, bloqueio ou eliminação de dados desnecessários ou excessivos;</li>
                <li>eliminação da conta e dos dados associados, ressalvadas as hipóteses legais de retenção;</li>
                <li>portabilidade dos dados, quando aplicável;</li>
                <li>informações sobre compartilhamentos e sobre a possibilidade de não consentir;</li>
                <li>revogação do consentimento e oposição a tratamentos realizados com base em legítimo interesse, quando cabível.</li>
            </ul>
            <p>
                Para exercer seus direitos, entre em contato pelo e-mail
                <a href="mailto:showmetcc@gmail.com">showmetcc@gmail.com</a>. Poderemos solicitar
                informações adicionais para confirmar sua identidade e proteger sua conta.
            </p>

            <h2>10. Crianças e adolescentes</h2>
            <p>
                A plataforma não é direcionada intencionalmente a crianças. Caso identifiquemos
                uma conta de criança criada sem a participação ou o consentimento exigido pela
                legislação aplicável, poderemos adotar medidas para restringir ou eliminar a conta.
            </p>

            <h2>11. Alterações nesta política</h2>
            <p>
                Esta Política de Privacidade pode ser atualizada para refletir mudanças na
                plataforma, na legislação ou nas práticas de tratamento de dados. A versão vigente
                será sempre publicada nesta página, com a data da última atualização.
            </p>

            <h2>12. Contato</h2>
            <p>
                Dúvidas sobre esta política ou sobre o tratamento de dados pessoais podem ser
                enviadas para <a href="mailto:showmetcc@gmail.com">showmetcc@gmail.com</a>.
                Consulte também os <a href="termos.php">Termos de Uso</a> da plataforma.
            </p>
        </div>

        <a class="termos-voltar" href="index.php" data-voltar-fallback="index.php">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Voltar
        </a>
    </section>
</main>
</body>
</html>