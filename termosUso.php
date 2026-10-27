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
    <title>Termos de Uso - ShowMe</title>
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
<main class="pagina-termos">
    <section class="termos-container" aria-labelledby="tituloTermos">
        <header class="cabecalho-pagina-legal">
            <p class="rotulo-pagina-legal">Regras de uso e convivência</p>
            <h1 id="tituloTermos">Termos de Uso</h1>
            <p class="atualizacao-pagina-legal">Última atualização: 27 de outubro de 2026.</p>
        </header>

        <div class="conteudo-termos">
            <p>
                Estes Termos de Uso regulam o acesso e a utilização da plataforma ShowMe. Ao criar
                uma conta, navegar ou utilizar nossos recursos, você declara que leu, compreendeu e
                aceita estas regras. Se não concordar com algum ponto, não utilize a plataforma.
            </p>
            <p>
                O ShowMe é uma iniciativa de divulgação e descoberta de eventos culturais. Estes
                termos foram elaborados em conformidade com o Marco Civil da Internet
                (Lei nº 12.965/2014), a Lei de Direitos Autorais (Lei nº 9.610/98) e a legislação
                brasileira aplicável.
            </p>

            <h2>1. Definições do sistema</h2>
            <p>Para facilitar a leitura, os termos abaixo têm os seguintes significados:</p>
            <ul>
                <li>
                    <strong>ShowMe:</strong> plataforma digital de divulgação cultural, descoberta
                    de eventos, planejamento de viagens e interação em comunidades.
                </li>
                <li>
                    <strong>Usuário:</strong> pessoa física que acessa a plataforma, cria uma conta
                    ou utiliza qualquer funcionalidade disponível.
                </li>
                <li>
                    <strong>Organizador:</strong> pessoa física ou jurídica responsável pela
                    realização, produção ou divulgação de um evento, incluindo usuários que enviam
                    informações de eventos para análise e aprovação no ShowMe.
                </li>
                <li>
                    <strong>Sessão/Evento:</strong> cada apresentação, edição ou horário de um
                    evento cultural. Um mesmo evento pode ter uma ou várias sessões, em datas e
                    horários diferentes.
                </li>
                <li>
                    <strong>Planejamento de Viagem:</strong> recurso que permite estimar orçamento,
                    transporte, rota, distância, tempo de deslocamento e hospedagem para participar
                    de um evento.
                </li>
                <li>
                    <strong>Comunidade:</strong> espaço vinculado a um evento para publicações,
                    respostas, curtidas, denúncias e compartilhamento de fotos ou vídeos entre
                    usuários.
                </li>
            </ul>

            <h2>2. Escopo do serviço e cadastro</h2>
            <p>
                O ShowMe tem como propósito divulgar eventos culturais, ajudar pessoas a descobrir
                atividades próximas, organizar rotas e custos de viagem e incentivar a troca de
                informações em comunidades. A plataforma não realiza a produção dos eventos, não
                comercializa ingressos e não garante a disponibilidade, a qualidade ou a realização
                das atividades divulgadas.
            </p>
            <p>Para utilizar os recursos que exigem conta, o Usuário deve:</p>
            <ul>
                <li>ter 18 anos ou mais e possuir capacidade legal para aceitar estes termos;</li>
                <li>fornecer informações verdadeiras, completas e atualizadas no cadastro;</li>
                <li>manter a confidencialidade da senha e não compartilhar o acesso com terceiros;</li>
                <li>comunicar imediatamente qualquer uso não autorizado da conta ou suspeita de fraude;</li>
                <li>responder pelas atividades realizadas com suas credenciais, salvo comprovada falha de segurança da plataforma.</li>
            </ul>
            <p>
                O cadastro de menores de 18 anos não é permitido. Caso identifiquemos uma conta
                nessas condições, poderemos suspender ou encerrar o acesso e adotar as medidas de
                proteção previstas na legislação aplicável.
            </p>
            <p>
                O Usuário é responsável pela custódia de sua senha. O ShowMe armazena senhas
                apenas em formato de hash, não sendo possível recuperá-las em texto legível, e
                nunca solicita o envio da senha por e-mail, telefone ou mensagem direta.
            </p>

            <h2>3. Regras de conduta e uso aceitável</h2>
            <p>
                O Usuário compromete-se a utilizar o ShowMe de forma ética, respeitosa e compatível
                com a lei. É expressamente proibido:
            </p>
            <ul>
                <li>publicar conteúdo ilegal, difamatório, calunioso, discriminatório, violento, pornográfico ou que viole direitos de terceiros;</li>
                <li>divulgar spam, correntes, golpes, links maliciosos, propaganda enganosa ou conteúdo repetitivo sem relação com o evento;</li>
                <li>registrar denúncias falsas, artificiais ou com o objetivo de prejudicar outro usuário;</li>
                <li>publicar dados pessoais de terceiros sem autorização ou expor informações privadas;</li>
                <li>tentar acessar contas, áreas restritas, sistemas ou dados sem permissão;</li>
                <li>usar robôs, automações abusivas ou qualquer recurso que comprometa a segurança e o funcionamento da plataforma;</li>
                <li>falsificar identidade, informações de evento, preços, endereços ou canais oficiais de venda.</li>
            </ul>
            <p>
                <strong>Intermediação e responsabilidade:</strong> o ShowMe atua como intermediário
                tecnológico entre usuários, organizadores e comunidades. As opiniões, relatos,
                dicas, críticas e demais conteúdos publicados por usuários são de responsabilidade
                exclusiva de quem os publica e não representam posição oficial da plataforma.
            </p>
            <p>
                Na forma do Marco Civil da Internet, o ShowMe não é autor de conteúdos de terceiros e
                somente poderá responder por eles nas hipóteses previstas em lei, inclusive quando,
                após ordem judicial específica, deixar de adotar as providências de remoção
                cabíveis. A plataforma poderá remover, limitar ou suspender conteúdos e contas que
                violem estes termos, a legislação ou os direitos de outros usuários, sem prejuízo
                das medidas legais cabíveis.
            </p>

            <h2>4. Direitos autorais e propriedade intelectual</h2>
            <p>
                A marca ShowMe, a identidade visual, o logotipo, o código-fonte, a estrutura, os
                textos próprios e os demais elementos da plataforma são de propriedade exclusiva do
                ShowMe ou de seus licenciantes. É proibida a reprodução, distribuição, modificação
                ou exploração comercial sem autorização prévia e expressa.
            </p>
            <p>
                <strong>Conteúdo enviado pelo Usuário:</strong> ao publicar fotos, vídeos, textos ou
                outros materiais na galeria ou nas comunidades, o Usuário declara ser titular dos
                direitos necessários e concede ao ShowMe uma licença não exclusiva, gratuita e
                limitada ao funcionamento da plataforma, para armazenar, exibir, reproduzir e
                adaptar tecnicamente o conteúdo ao formato do site. Essa licença permanece enquanto
                o conteúdo estiver publicado e não transfere a titularidade ao ShowMe.
            </p>
            <p>
                O Usuário é responsável por obter autorização de pessoas retratadas e por respeitar
                direitos autorais, de imagem e de personalidade. Denúncias de violação podem ser
                enviadas para <a href="mailto:showmetcc@gmail.com">showmetcc@gmail.com</a> e serão
                analisadas pela equipe.
            </p>
            <p>
                A reprodução não autorizada de obras protegidas pela Lei de Direitos Autorais é
                proibida. O ShowMe poderá remover conteúdo que, após análise, apresente indícios de
                violação de direitos de terceiros.
            </p>

            <h2>5. Planejamento de viagem, custos e ingressos</h2>
            <p>
                O Planejamento de Viagem é uma ferramenta de organização pessoal. Os valores de
                orçamento, transporte, hospedagem, distância e tempo são estimativas informadas pelo
                próprio Usuário ou calculadas com base em serviços externos e podem variar conforme
                preços, rotas, disponibilidade e condições do momento.
            </p>
            <p>
                O ShowMe não garante que as estimativas correspondam ao custo final da viagem. O
                Usuário deve confirmar valores, horários, endereços e condições diretamente com os
                organizadores, transportadoras, hospedagens e canais oficiais.
            </p>
            <p>
                Quando houver link para compra de ingressos ou reservas, o redirecionamento poderá
                levar a sites de terceiros. A compra, o pagamento, a emissão, o cancelamento e o
                atendimento são realizados diretamente pelo organizador ou pela plataforma oficial
                indicada, com regras próprias. O ShowMe não intermedia pagamentos e não se
                responsabiliza por transações concluídas fora de seu ambiente.
            </p>

            <h2>6. Privacidade e proteção de dados</h2>
            <p>
                O tratamento de dados pessoais é descrito na
                <a href="politicaPrivacidade.php">Política de Privacidade</a>, elaborada em
                conformidade com a LGPD (Lei nº 13.709/2018). Ao utilizar a plataforma, o Usuário
                declara ciência dessa política.
            </p>

            <h2>7. Suspensão, encerramento e alterações</h2>
            <p>
                O ShowMe poderá suspender, limitar ou encerrar contas que violem estes termos, a
                legislação ou que representem risco à segurança da plataforma e de outros usuários.
                O Usuário também pode solicitar o encerramento da própria conta pelos canais de
                atendimento, observadas as obrigações legais de retenção de dados.
            </p>
            <p>
                Estes termos podem ser atualizados para refletir mudanças na plataforma, na
                legislação ou nas práticas de uso. A versão vigente será publicada nesta página com
                a data da última atualização. Alterações relevantes poderão ser comunicadas por
                e-mail cadastrado ou por aviso na plataforma.
            </p>

            <h2>8. Legislação aplicável e foro</h2>
            <p>
                Estes Termos de Uso são regidos pela legislação brasileira. Eventuais controvérsias
                serão resolvidas no foro do domicílio do Usuário, quando aplicável a relação de
                consumo, ou no foro legalmente competente, sem prejuízo dos direitos assegurados
                pelo Código de Defesa do Consumidor.
            </p>

            <h2>9. Contato e atendimento</h2>
            <p>
                Dúvidas sobre estes termos, denúncias de conteúdo ou solicitações relacionadas à
                conta podem ser enviadas para
                <a href="mailto:showmetcc@gmail.com">showmetcc@gmail.com</a>. Também é possível
                utilizar o formulário de contato disponível no
                <a href="index.php#contato">rodapé da plataforma</a> ou enviar mensagem pelo
                <a href="https://www.instagram.com/showmetcc/" target="_blank" rel="noopener noreferrer">Instagram oficial do ShowMe</a>.
            </p>
            <p>
                Para assuntos de privacidade e proteção de dados, consulte a
                <a href="politicaPrivacidade.php">Política de Privacidade</a>.
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
