<?php
require_once '../vendor/autoload.php';

use MarceloNees\Fancytree\TFancytree;
use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;

// Dados de exemplo
$data = [
    [
        'id' => '1',
        'title' => 'Departamentos',
        'children' => [
            ['id' => '1-1', 'title' => 'TI'],
            ['id' => '1-2', 'title' => 'RH'],
            ['id' => '1-3', 'title' => 'Financeiro']
        ]
    ],
    [
        'id' => '2',
        'title' => 'Projetos',
        'children' => [
            ['id' => '2-1', 'title' => 'Projeto A'],
            ['id' => '2-2', 'title' => 'Projeto B']
        ]
    ]
];

// Criar árvore
$tree = new TFancytree();
$tree->setSource($data);
$tree->setWidth('100%');
$tree->setHeight(400);
$tree->setCheckbox(true);
$tree->setSelectMode(2); // Múltipla seleção

// Eventos
$onClick = new TAction(function ($params) {
    echo "Clicou no nó: " . $params['key'];
});
$tree->setOnClick($onClick);

// Exibir
$tree->show();
