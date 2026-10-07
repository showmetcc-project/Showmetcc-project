<?php

use PHPMailer\PHPMailer\PHPMailer;

function configuracaoEmailShowMe(): array
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    $arquivoConfiguracao = __DIR__ . '/email.php';

    if (!is_file($autoload)) {
        throw new RuntimeException('Dependências de e-mail não instaladas');
    }

    if (!is_file($arquivoConfiguracao)) {
        throw new RuntimeException('Configuração de e-mail não encontrada');
    }

    require_once $autoload;
    $configuracao = require $arquivoConfiguracao;

    if (!is_array($configuracao)) {
        throw new RuntimeException('Configuração de e-mail inválida');
    }

    $porta = filter_var(
        $configuracao['porta'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 65535]]
    );
    $criptografia = strtolower(trim((string) ($configuracao['criptografia'] ?? '')));
    $remetente = trim((string) ($configuracao['remetente_email'] ?? ''));

    if (
        trim((string) ($configuracao['host'] ?? '')) === ''
        || $porta === false
        || trim((string) ($configuracao['usuario'] ?? '')) === ''
        || (string) ($configuracao['senha'] ?? '') === ''
        || filter_var($remetente, FILTER_VALIDATE_EMAIL) === false
        || !in_array($criptografia, ['', 'tls', 'ssl'], true)
    ) {
        throw new RuntimeException('Configuração SMTP incompleta ou inválida');
    }

    $configuracao['porta'] = $porta;
    $configuracao['criptografia'] = $criptografia;
    return $configuracao;
}

function criarMailerShowMe(): PHPMailer
{
    $configuracao = configuracaoEmailShowMe();
    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = trim((string) $configuracao['host']);
    $mailer->Port = (int) $configuracao['porta'];
    $mailer->SMTPAuth = true;
    $mailer->Username = trim((string) $configuracao['usuario']);
    $mailer->Password = (string) $configuracao['senha'];
    $mailer->Timeout = 15;
    $mailer->CharSet = PHPMailer::CHARSET_UTF8;

    if ($configuracao['criptografia'] === 'tls') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($configuracao['criptografia'] === 'ssl') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mailer->SMTPSecure = '';
        $mailer->SMTPAutoTLS = false;
    }

    $nomeRemetente = trim((string) ($configuracao['remetente_nome'] ?? 'ShowMe'));
    $mailer->setFrom(
        trim((string) $configuracao['remetente_email']),
        $nomeRemetente !== '' ? $nomeRemetente : 'ShowMe'
    );

    return $mailer;
}

function urlBaseEmailShowMe(): string
{
    $configuracao = configuracaoEmailShowMe();
    $configurada = rtrim(trim((string) ($configuracao['url_base'] ?? '')), '/');

    if ($configurada !== '') {
        if (filter_var($configurada, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('url_base da configuração de e-mail é inválida');
        }
        return $configurada;
    }

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        throw new RuntimeException('Não foi possível determinar a URL da aplicação');
    }

    $seguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $posicaoApi = strpos($script, '/api/');
    $caminhoBase = $posicaoApi === false ? rtrim(dirname($script), '/') : substr($script, 0, $posicaoApi);

    return ($seguro ? 'https' : 'http') . '://' . $host . $caminhoBase;
}

function enviarEmailModeracaoEventoShowMe(
    string $destinatario,
    string $nomeUsuario,
    string $nomeEvento,
    string $dataEvento,
    string $status,
    ?int $idEvento
): void {
    if (filter_var($destinatario, FILTER_VALIDATE_EMAIL) === false) {
        throw new InvalidArgumentException('E-mail do solicitante inválido');
    }

    if (!in_array($status, ['aprovado', 'recusado'], true)) {
        throw new InvalidArgumentException('Status de moderação inválido');
    }

    $mailer = criarMailerShowMe();
    $mailer->addAddress($destinatario, trim($nomeUsuario));
    $mailer->isHTML(true);

    $nomeSeguro = htmlspecialchars($nomeUsuario !== '' ? $nomeUsuario : 'Olá', ENT_QUOTES, 'UTF-8');
    $eventoSeguro = htmlspecialchars($nomeEvento, ENT_QUOTES, 'UTF-8');

    if ($status === 'aprovado') {
        if ($idEvento === null || $idEvento < 1) {
            throw new InvalidArgumentException('Evento aprovado sem ID válido');
        }

        $data = DateTimeImmutable::createFromFormat('!Y-m-d', $dataEvento);
        $dataFormatada = $data ? $data->format('d/m/Y') : $dataEvento;
        $dataSegura = htmlspecialchars($dataFormatada, ENT_QUOTES, 'UTF-8');
        $link = urlBaseEmailShowMe() . '/detalhesEvento.php?id=' . $idEvento;
        $linkSeguro = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $mailer->Subject = 'Seu evento foi aprovado no ShowMe!';
        $mailer->Body = "<p>{$nomeSeguro},</p>
            <p>Seu evento <strong>{$eventoSeguro}</strong>, marcado para {$dataSegura}, foi aprovado e já está publicado no ShowMe.</p>
            <p><a href=\"{$linkSeguro}\">Ver detalhes do evento</a></p>";
        $mailer->AltBody = "{$nomeUsuario},\n\nSeu evento {$nomeEvento}, marcado para {$dataFormatada}, foi aprovado e já está publicado no ShowMe.\n\nVer detalhes: {$link}";
    } else {
        $mailer->Subject = 'Sobre o seu evento no ShowMe';
        $mailer->Body = "<p>{$nomeSeguro},</p>
            <p>Infelizmente seu evento <strong>{$eventoSeguro}</strong> não foi aprovado pela nossa equipe de moderação.</p>";
        $mailer->AltBody = "{$nomeUsuario},\n\nInfelizmente seu evento {$nomeEvento} não foi aprovado pela nossa equipe de moderação.";
    }

    $mailer->send();
}
