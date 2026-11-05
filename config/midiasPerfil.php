<?php

function catalogoMidiasPerfil(): array
{
    return [
        'foto_perfil' => [
            'Vozes em Destaque' => [
                'icone' => 'bi-mic-fill',
                'imagens' => [
                    ['caminho' => 'assets/img/prontos/perfil/1.png', 'legenda' => 'RI'],
                    ['caminho' => 'assets/img/prontos/perfil/2.png', 'legenda' => 'JV'],
                    ['caminho' => 'assets/img/prontos/perfil/3.png', 'legenda' => 'CP'],
                    ['caminho' => 'assets/img/prontos/perfil/4.png', 'legenda' => 'AV'],
                    ['caminho' => 'assets/img/prontos/perfil/5.png', 'legenda' => 'LO'],
                    ['caminho' => 'assets/img/prontos/perfil/6.png', 'legenda' => 'THI'],
                    ['caminho' => 'assets/img/prontos/perfil/7.png', 'legenda' => 'PA'],

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
