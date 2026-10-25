<?php

/**
 * Fonte única das categorias oficiais de evento do ShowMe.
 * A ordem definida aqui é a mesma usada nos carrosséis da Home, no
 * formulário de cadastro e no filtro de busca.
 */

/**
 * Retorna as 9 categorias oficiais, na ordem de exibição.
 *
 * @return array<int, string>
 */
function categoriasEventoOficiais(): array
{
    return [
        'Música e Shows',
        'Cinema e Audiovisual',
        'Teatro e Dança',
        'Artes Visuais e Exposições',
        'Literatura',
        'Gastronomia',
        'Oficinas e Workshops',
        'Tradições Regionais',
        'Geek e Cultura Pop',
    ];
}

/**
 * Ícone (Bootstrap Icons) de cada categoria oficial, usado no formulário de cadastro de eventos.
 *
 * @return array<string, string>
 */
function iconesCategoriasEvento(): array
{
    return [
        'Música e Shows' => 'bi-music-note-beamed',
        'Cinema e Audiovisual' => 'bi-film',
        'Teatro e Dança' => 'bi-mask',
        'Artes Visuais e Exposições' => 'bi-palette',
        'Literatura' => 'bi-book',
        'Gastronomia' => 'bi-cup-hot',
        'Oficinas e Workshops' => 'bi-easel2',
        'Tradições Regionais' => 'bi-people',
        'Geek e Cultura Pop' => 'bi-joystick',
    ];
}