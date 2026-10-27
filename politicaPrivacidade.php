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
            <p class="atualizacao-pagina-legal">Última atualização: 27 de outubro de 2026.</p>
        </header>

        <div class="conteudo-termos">
            <p>
                Esta Política de Privacidade explica como o ShowMe coleta, utiliza, armazena,
                compartilha e protege os dados pessoais de quem utiliza a plataforma. O documento
                segue a Lei Geral de Proteção de Dados Pessoais (LGPD, Lei nº 13.709/2018) e o
                Marco Civil da Internet (Lei nº 12.965/2014).
            </p>
            <p>
                Ao criar uma conta, navegar ou utilizar nossos recursos, você declara ter lido e
                compreendido estas informações. O aceite dos
                <a href="termosUso.php">Termos de Uso</a> também é necessário para uso dos recursos
                que exigem cadastro.
            </p>

            <h2>1. Quem somos e como falar conosco</h2>
            <p>
                O ShowMe é uma plataforma de divulgação, descoberta e planejamento de eventos
                culturais. Para as atividades descritas nesta política, o ShowMe atua como
                controlador dos dados pessoais coletados diretamente em suas páginas e sistemas.
            </p>
            <p>
                Canal de privacidade e atendimento:
                <a href="mailto:showmetcc@gmail.com">showmetcc@gmail.com</a>.
            </p>

            <h2>2. Quais dados coletamos</h2>
            <p>Podemos coletar as seguintes categorias de dados:</p>
            <ul>
                <li>
                    <strong>Dados cadastrais:</strong> nome, sobrenome, e-mail, senha armazenada
                    em formato de hash (não legível), tipo de conta e, quando informados, foto de
                    perfil e banner.
                </li>
                <li>
                    <strong>Dados de login com o Google:</strong> identificador da conta Google,
                    nome, e-mail e foto pública autorizada pelo usuário no momento do acesso.
                </li>
                <li>
                    <strong>Conteúdo e interações:</strong> eventos cadastrados, favoritos,
                    planejamentos de viagem, publicações na comunidade, respostas, curtidas,
                    denúncias, fotos e vídeos enviados à galeria e mensagens enviadas pelo
                    formulário de contato.
                </li>
                <li>
                    <strong>Localização:</strong> endereço ou coordenadas informadas ou autorizadas
                    pelo usuário para calcular rotas, distâncias e estimativas de deslocamento. A
                    localização não é monitorada de forma contínua.
                </li>
                <li>
                    <strong>Dados de navegação e uso:</strong> páginas acessadas, recursos
                    utilizados, preferências de acessibilidade, data e hora de acesso, endereço IP,
                    navegador, sistema operacional e informações técnicas do dispositivo.
                </li>
                <li>
                    <strong>Cookies e armazenamento local:</strong> identificadores de sessão,
                    tokens de segurança, preferências e informações necessárias para manter o login
                    e o funcionamento da plataforma.
                </li>
            </ul>

            <h2>3. Para que usamos os dados e como personalizamos sua experiência</h2>
            <p>Os dados são utilizados para:</p>
            <ul>
                <li>criar, autenticar e manter sua conta com segurança;</li>
                <li>personalizar a experiência de planejamento de viagem e salvar seus dados entre acessos;</li>
                <li>exibir eventos próximos e resultados compatíveis com a cidade, localização ou filtros escolhidos;</li>
                <li>permitir login seguro, recuperação de senha e proteção contra fraudes;</li>
                <li>salvar favoritos, planejamentos, preferências de acessibilidade e configurações;</li>
                <li>viabilizar interações na comunidade e moderar conteúdos denunciados;</li>
                <li>responder solicitações enviadas pelos canais de atendimento;</li>
                <li>enviar comunicações operacionais relacionadas à conta, aos eventos ou à segurança;</li>
                <li>melhorar a experiência, a segurança, o desempenho e a estabilidade da plataforma;</li>
                <li>cumprir obrigações legais e exercer direitos em processos administrativos ou judiciais.</li>
            </ul>
            <p>
                A personalização utiliza apenas os dados necessários para entregar o recurso
                solicitado. Não tomamos decisões automatizadas com efeitos jurídicos ou
                significativos sobre o usuário sem possibilidade de revisão humana.
            </p>

            <h2>4. Bases legais do tratamento</h2>
            <p>
                O tratamento pode ocorrer com base na execução de contrato ou de procedimentos
                preliminares, no cumprimento de obrigação legal ou regulatória, no exercício regular
                de direitos, no legítimo interesse para segurança e melhoria do serviço, na proteção
                do crédito, quando aplicável, ou mediante consentimento do titular, conforme a LGPD.
            </p>
            <p>
                Quando o tratamento depender de consentimento, o usuário poderá revogá-lo a qualquer
                momento pelos canais de atendimento, sem afetar a legalidade dos tratamentos
                realizados anteriormente.
            </p>

            <h2>5. Cookies e opção de navegação</h2>
            <p>
                Utilizamos cookies funcionais e mecanismos de armazenamento local para manter o
                login, proteger formulários contra CSRF, registrar a última página acessada e
                guardar preferências de acessibilidade. Esses recursos são necessários para o
                funcionamento seguro da plataforma.
            </p>
            <p>
                O ShowMe não utiliza cookies publicitários próprios e não vende dados de navegação.
                Serviços externos acionados pelo usuário, como Google, OpenStreetMap ou outros
                links de terceiros, podem utilizar cookies e tecnologias próprias, sujeitos às
                políticas desses provedores.
            </p>
            <p>
                O usuário pode gerenciar, bloquear ou apagar cookies nas configurações do navegador.
                O bloqueio de cookies essenciais pode impedir o login, a recuperação de senha e
                outras funcionalidades. As instruções variam conforme o navegador utilizado.
            </p>

            <h2>6. Compartilhamento com terceiros</h2>
            <p>
                <strong>O ShowMe NÃO vende nem cede dados pessoais a terceiros para fins comerciais
                não autorizados.</strong> O compartilhamento ocorre apenas quando necessário para
                operar a plataforma, cumprir obrigações legais ou atender integrações solicitadas
                pelo próprio usuário, como:
            </p>
            <ul>
                <li>provedores de hospedagem, banco de dados, envio de e-mail e infraestrutura;</li>
                <li>Google, quando o usuário opta por entrar com uma conta Google ou conectar o Google Agenda;</li>
                <li>serviços de mapas, geocodificação, rotas e hospedagem consultados durante o planejamento, como OpenStreetMap/Nominatim, CARTO, BrasilAPI e OSRM;</li>
                <li>autoridades públicas, quando houver requisição legal ou necessidade de proteção de direitos.</li>
            </ul>
            <p>
                Alguns provedores podem processar dados fora do Brasil. Nesses casos, buscamos
                adotar salvaguardas contratuais e medidas compatíveis com a LGPD. Links para sites
                de terceiros, como plataformas de ingressos, transporte ou hospedagem, possuem
                políticas de privacidade próprias, e recomendamos a leitura antes de utilizar esses
                serviços.
            </p>

            <h2>7. Armazenamento, logs de acesso e exclusão</h2>
            <p>
                Os dados cadastrais e de uso são mantidos pelo período necessário ao funcionamento
                da conta e ao cumprimento das finalidades descritas nesta política. Também podem ser
                conservados para atender obrigações legais, resolver disputas, prevenir fraudes e
                exercer direitos.
            </p>
            <p>
                Em conformidade com o art. 15 do Marco Civil da Internet, os registros de acesso à aplicação,
                incluindo data e hora, endereço IP e demais dados técnicos necessários, são
                armazenados pelo prazo de <strong>6 (seis) meses</strong>, salvo hipóteses legais de
                retenção por prazo superior, requisição de autoridade competente ou necessidade de
                preservação para investigação.
            </p>
            <p>
                Quando não houver mais necessidade ou base legal para a retenção, os dados serão
                eliminados ou anonimizados de forma segura. A exclusão da conta pode ser solicitada
                pelos canais de atendimento, ressalvados os dados que a lei obrigue a manter.
            </p>

            <h2>8. Segurança das informações</h2>
            <p>
                Adotamos medidas técnicas e administrativas para proteger os dados, incluindo
                controle de acesso, validação de requisições, proteção contra CSRF, restrição de
                permissões e armazenamento de senhas por meio de hash. Nenhum sistema é
                completamente imune a incidentes; por isso, recomendamos o uso de senhas exclusivas,
                a atualização dos dispositivos e a adoção de conexões seguras.
            </p>
            <p>
                Caso ocorra incidente de segurança relevante, adotaremos as providências de
                contenção e comunicação previstas na LGPD e na regulamentação aplicável.
            </p>

            <h2>9. Faixa etária permitida</h2>
            <p>
                A plataforma é destinada a pessoas com <strong>18 anos ou mais</strong>. Não é
                permitido o cadastro de menores de 18 anos. Não coletamos intencionalmente dados de
                crianças ou adolescentes.
            </p>
            <p>
                Caso identifiquemos uma conta criada por menor de idade, poderemos restringir,
                suspender ou eliminar a conta e os dados associados, observadas as obrigações legais
                e a proteção do titular.
            </p>

            <h2>10. Direitos do titular de dados</h2>
            <p>Nos termos da LGPD, o titular pode solicitar:</p>
            <ul>
                <li>confirmação da existência de tratamento e acesso aos dados;</li>
                <li>correção de dados incompletos, inexatos ou desatualizados;</li>
                <li>anonimização, bloqueio ou eliminação de dados desnecessários, excessivos ou tratados em desconformidade;</li>
                <li>portabilidade dos dados, quando aplicável;</li>
                <li>eliminação dos dados tratados com consentimento, ressalvadas as hipóteses legais de retenção;</li>
                <li>informações sobre compartilhamentos e sobre a possibilidade de não fornecer consentimento;</li>
                <li>revogação do consentimento e oposição a tratamentos realizados com base em legítimo interesse, quando cabível;</li>
                <li>revisão de decisões automatizadas, quando aplicável;</li>
                <li>petição perante a Autoridade Nacional de Proteção de Dados (ANPD).</li>
            </ul>
            <p>
                Para exercer seus direitos, envie solicitação para
                <a href="mailto:showmetcc@gmail.com">showmetcc@gmail.com</a>. Poderemos solicitar
                informações adicionais para confirmar sua identidade e proteger sua conta.
                Responderemos nos prazos legais, observado o prazo de até 15 dias para
                confirmação ou acesso, quando aplicável.
            </p>

            <h2>11. Atualizações desta política</h2>
            <p>
                Esta Política de Privacidade pode ser atualizada para refletir mudanças na
                plataforma, na legislação ou nas práticas de tratamento de dados. A versão vigente
                será sempre publicada nesta página, com a data da última atualização.
            </p>
            <p>
                Alterações relevantes serão comunicadas por e-mail cadastrado e/ou por aviso
                destacado na plataforma antes da entrada em vigor, quando possível. O uso
                continuado após a atualização indica ciência da nova versão.
            </p>

            <h2>12. Contato</h2>
            <p>
                Dúvidas sobre esta política, solicitações de titulares ou relatos de incidentes de
                privacidade podem ser enviados para
                <a href="mailto:showmetcc@gmail.com">showmetcc@gmail.com</a>. Também é possível
                utilizar o formulário de contato disponível no
                <a href="index.php#contato">rodapé da plataforma</a> ou enviar mensagem pelo
                <a href="https://www.instagram.com/showmetcc/" target="_blank" rel="noopener noreferrer">Instagram oficial do ShowMe</a>.
            </p>
            <p>
                Consulte também os <a href="termosUso.php">Termos de Uso</a> da plataforma.
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
