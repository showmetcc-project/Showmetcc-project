<?php

function catalogoMidiasPerfil(): array
{
    return [
        'foto_perfil' => [
            'Vozes em Destaque' => [
                'icone' => 'bi-mic-fill',
                'imagens' => [
                    ['caminho' => 'assets/img/prontos/perfil/vozes-destaque-01.webp', 'legenda' => 'Voz em cena'],
                    ['caminho' => 'assets/img/prontos/perfil/vozes-destaque-02.webp', 'legenda' => 'Luz do palco'],
                    ['caminho' => 'assets/img/prontos/perfil/vozes-destaque-03.webp', 'legenda' => 'Som ao vivo'],
                ],
            ],
            'Ritmos do Brasil' => [
                'icone' => 'bi-music-note-beamed',
                'imagens' => [
                    ['caminho' => 'assets/img/prontos/perfil/ritmos-brasil-01.webp', 'legenda' => 'Ritmo e voz'],
                    ['caminho' => 'assets/img/prontos/perfil/ritmos-brasil-02.webp', 'legenda' => 'Palco aberto'],
                    ['caminho' => 'assets/img/prontos/perfil/ritmos-brasil-03.webp', 'legenda' => 'Energia musical'],
                ],
            ],
            'Cena Urbana' => [
                'icone' => 'bi-boombox-fill',
                'imagens' => [
                    ['caminho' => 'assets/img/prontos/perfil/cena-urbana-01.webp', 'legenda' => 'Pulso urbano'],
                    ['caminho' => 'assets/img/prontos/perfil/cena-urbana-02.webp', 'legenda' => 'Noite neon'],
                    ['caminho' => 'assets/img/prontos/perfil/cena-urbana-03.webp', 'legenda' => 'Batida da cidade'],
                ],
            ],
        ],
        'foto_banner' => [
            'Grandes Palcos' => [
                'icone' => 'bi-stars',
                'imagens' => [
                    ['caminho' => 'assets/img/prontos/banner/grandes-palcos-01.webp', 'legenda' => 'Palco vibrante'],
                    ['caminho' => 'assets/img/prontos/banner/grandes-palcos-02.webp', 'legenda' => 'Festival de cores'],
                    ['caminho' => 'assets/img/prontos/banner/grandes-palcos-03.webp', 'legenda' => 'Show completo'],
                ],
            ],
            'Multidões' => [
                'icone' => 'bi-people-fill',
                'imagens' => [
                    ['caminho' => 'assets/img/prontos/banner/multidoes-01.webp', 'legenda' => 'Mar de gente'],
                    ['caminho' => 'assets/img/prontos/banner/multidoes-02.webp', 'legenda' => 'Noite de festival'],
                    ['caminho' => 'assets/img/prontos/banner/multidoes-03.webp', 'legenda' => 'Público em sintonia'],
                ],
            ],
            'Luzes da Noite' => [
                'icone' => 'bi-lightning-charge-fill',
                'imagens' => [
                    ['caminho' => 'assets/img/prontos/banner/luzes-noite-01.webp', 'legenda' => 'Luzes no palco'],
                    ['caminho' => 'assets/img/prontos/banner/luzes-noite-02.webp', 'legenda' => 'Cores da noite'],
                    ['caminho' => 'assets/img/prontos/banner/luzes-noite-03.webp', 'legenda' => 'Energia ao vivo'],
                ],
            ],
        ],
    ];
}

function midiaPerfilProntaPermitida(string $campo, string $caminho): bool
{
    $catalogo = catalogoMidiasPerfil();

    if (!isset($catalogo[$campo])) {
        return false;
    }

    foreach ($catalogo[$campo] as $categoria) {
        foreach ($categoria['imagens'] as $imagem) {
            if ($imagem['caminho'] === $caminho) {
                return true;
            }
        }
    }

    return false;
}
