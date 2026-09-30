<?php

/*
 | SISTEMA — navegação (Etapa 4)
 | Fonte única para bottom nav (mobile), rail (tablet) e sidebar (desktop).
 | Troque "route"/"match" se os nomes das suas rotas forem outros.
 */
return [

    'nav' => [
        ['key' => 'status',     'label' => 'Status',     'icon' => 'status',  'route' => 'dashboard',           'match' => 'status',       'atalho' => '1'],
        ['key' => 'missoes',    'label' => 'Missões',    'icon' => 'target',  'route' => 'missoes.index',    'match' => 'missoes.*',    'atalho' => '2'],
        ['key' => 'loja',       'label' => 'Loja',       'icon' => 'shop',    'route' => 'recompensas.index',       'match' => ['loja.*', 'recompensas.*'], 'atalho' => '3'],
        ['key' => 'inventario', 'label' => 'Inventário', 'icon' => 'package', 'route' => 'inventario.index', 'match' => 'inventario.*', 'atalho' => '4'],
        ['key' => 'tesouro',    'label' => 'Tesouro',    'icon' => 'gem',     'route' => 'tesouro.index',    'match' => 'tesouro.*', 'atalho' => '5'],
    ],

    /*
     | Ações rápidas da sidebar (desktop). Abrem o modal (ou focam o campo "foco") se ele
     | existir na tela atual; senão navegam para a tela com ?acao=… e o modal abre ao carregar.
     */
    'acoes' => [
        ['label' => 'Nova missão',  'icon' => 'plus',    'modal' => 'modal-missao', 'route' => 'missoes.index',    'acao' => 'nova-missao', 'atalho' => 'N', 'variant' => 'primary'],
        ['label' => 'Lançar gasto', 'icon' => 'minus',   'modal' => 'modal-gasto',  'route' => 'tesouro.index',    'acao' => 'gasto',       'atalho' => 'G', 'variant' => 'danger'],
        ['label' => 'Novo item',    'icon' => 'package', 'modal' => null,           'route' => 'inventario.index', 'acao' => 'item',        'atalho' => 'I', 'variant' => 'secondary', 'foco' => '#f-novo-item'],
    ],
];
