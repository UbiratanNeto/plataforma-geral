<?php
/**
 * painel/includes/menus.php
 * Catálogo único de páginas do painel — fonte de verdade usada pelo sidebar, pela tela de
 * permissões de Cargos, e pelo guard de rota em painel/index.php. Criar uma página nova
 * é editar esse array (ela já aparece no sidebar, agrupada certo, e pronta pra receber
 * permissão) — nenhum outro lugar precisa saber da lista de páginas por conta própria.
 *
 * 'grupo': chave de $grupos abaixo, se a página vive dentro de um submenu (ex.: 'cadastros').
 *          null = item solto direto no menu principal.
 */
return [
    // Ordem de exibição no menu principal — cada item é o slug de uma página solta
    // (grupo: null) ou a chave de um grupo (ex.: 'cadastros'), nunca os dois ao mesmo tempo.
    'ordem_principal' => ['dashboard', 'cadastros', 'chamados', 'clientes', 'usuarios', 'relatorios', 'logs', 'configuracoes'],

    'grupos' => [
        'cadastros' => ['label' => 'Cadastros', 'icone' => 'fa-solid fa-layer-group'],
    ],

    'paginas' => [
        'dashboard'     => ['label' => 'Dashboard',     'icone' => 'fa-solid fa-chart-pie',  'grupo' => null],
        'cargos'        => ['label' => 'Cargos',        'icone' => 'fa-solid fa-briefcase',   'grupo' => 'cadastros'],
        'chamados'      => ['label' => 'Chamados',      'icone' => 'fa-solid fa-ticket',      'grupo' => null],
        'clientes'      => ['label' => 'Clientes',      'icone' => 'fa-solid fa-users',       'grupo' => null],
        'usuarios'      => ['label' => 'Usuários',      'icone' => 'fa-solid fa-user-gear',   'grupo' => null],
        'relatorios'    => ['label' => 'Relatórios',    'icone' => 'fa-solid fa-chart-line',  'grupo' => null],
        'logs'          => ['label' => 'Logs',          'icone' => 'fa-solid fa-clock-rotate-left', 'grupo' => null],
        'configuracoes' => ['label' => 'Configurações', 'icone' => 'fa-solid fa-gears',       'grupo' => null],
    ],
];
