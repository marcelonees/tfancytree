<?php

namespace MarceloNees\TFancytree;

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TStyle;
use Exception;

class TFancytree extends TElement
{
    private $id;
    private $source;
    private $width = '100%';
    private $height = '500px';
    private $checkbox;
    private $selectMode;
    private $dragDrop;
    private $onClickAction;
    private $onSelectAction;
    private $onLazyLoadAction;
    private $selectedKeys;
    private $expandedKeys;

    public function __construct()
    {
        parent::__construct('div');
        $this->id = 'tfancytree_' . mt_rand(1000000000, 1999999999);
        $this->selectMode = 3;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function setSize($width, $height)
    {
        $this->width = is_numeric($width) ? "{$width}px" : $width;
        $this->height = is_numeric($height) ? "{$height}px" : $height;
    }

    public function setWidth($width)
    {
        $this->width = is_numeric($width) ? "{$width}px" : $width;
    }

    public function setHeight($height)
    {
        $this->height = is_numeric($height) ? "{$height}px" : $height;
    }

    public function setCheckbox($checkbox)
    {
        $this->checkbox = $checkbox;
    }

    public function setSelectMode($mode)
    {
        $this->selectMode = $mode;
    }

    public function setDragDrop($dragDrop)
    {
        $this->dragDrop = $dragDrop;
    }

    public function setSelectedKeys($keys)
    {
        $this->selectedKeys = $keys;
    }

    public function setExpandedKeys($keys)
    {
        $this->expandedKeys = $keys;
    }

    public function setOnClick(TAction $action)
    {
        $this->onClickAction = $action;
    }

    public function setOnSelect(TAction $action)
    {
        $this->onSelectAction = $action;
    }

    public function setOnLazyLoad(TAction $action)
    {
        $this->onLazyLoadAction = $action;
    }

    public function createTree()
    {
        $treeId = $this->id;

        /* Usa os dados diretamente, sem processamento */
        $sourceJson = json_encode($this->source);
        $hasCheckbox = $this->checkbox ? 'true' : 'false';
        $selectMode = (int) $this->selectMode;

        /* Evento de clique */
        $clickHandler = '';
        if ($this->onClickAction) {
            $serializedAction = $this->onClickAction->serialize(false);
            $clickHandler = ",
            click: function(event, data) {
                __adianti_ajax_exec('{$serializedAction}&key=' + encodeURIComponent(data.node.key));
            }
            ";
        }

        /* Evento de selecao */
        $selectHandler = '';
        if ($this->onSelectAction) {
            $serializedAction = $this->onSelectAction->serialize(false);
            $selectHandler = ",
            select: function(event, data) {
                var keys = JSON.stringify(data.tree.getSelectedKeys());
                __adianti_ajax_exec('{$serializedAction}&keys=' + keys);
            }
            ";
        }

        /* Evento de lazy loading */
        $lazyHandler = '';
        if ($this->onLazyLoadAction) {
            $serializedAction = $this->onLazyLoadAction->serialize(false);
            $lazyHandler = ",
            lazyLoad: function(event, data) {
                var key = encodeURIComponent(data.node.key);
                $.getJSON('index.php?{$serializedAction}&key=' + key, function(response) {
                    data.result = response;
                });
            }
            ";
        }

        /* Carrega CSS */
        TStyle::importFromFile('vendor/marcelonees/tfancytree/assets/jquery-ui/css/jquery-ui.css');
        TStyle::importFromFile('vendor/marcelonees/tfancytree/assets/fancytree/css/ui.fancytree.min.css');
        TStyle::importFromFile('vendor/marcelonees/tfancytree/assets/fancytree/css/fancytree-custom.css');

        /* Script de inicializacao */
        TScript::create("
        $(document).ready(function() {
            var scripts = [
                'vendor/marcelonees/tfancytree/assets/jquery-ui/js/jquery-ui.min.js',
                'vendor/marcelonees/tfancytree/assets/fancytree/js/jquery.fancytree-all-deps.min.js'
            ];
            var loaded = 0;
            
            function loadTree() {
                $('#{$treeId}').fancytree({
                    extensions: ['glyph', 'filter'],
                    filter: {
                        counter: false,
                        mode: 'hide'
                    },
                    checkbox: {$hasCheckbox},
                    selectMode: {$selectMode},
                    glyph: {
                        preset: 'awesome5',
                        map: {}
                    },
                    source: {$sourceJson}
                    {$clickHandler}
                    {$selectHandler}
                    {$lazyHandler}
                });
                console.log('Arvore inicializada: {$treeId}');
                
                /* Configurar radiogroups se existirem */
                $('#{$treeId}').on('nodeClick', function(event, data) {
                    var node = data.node;
                    if (node.data.radiogroup === true && node.parent) {
                        var siblings = node.parent.children;
                        for (var i = 0; i < siblings.length; i++) {
                            if (siblings[i] !== node && siblings[i].selected) {
                                siblings[i].setSelected(false);
                            }
                        }
                    }
                });
            }
            
            function checkLoaded() {
                loaded++;
                if (loaded === scripts.length) {
                    if (typeof $.fn.fancytree !== 'undefined') {
                        loadTree();
                    } else {
                        console.error('Fancytree nao carregado');
                    }
                }
            }
            
            for (var i = 0; i < scripts.length; i++) {
                $.getScript(scripts[i]).always(checkLoaded);
            }
        });
        ");
    }

    public function show()
    {
        if (empty($this->source)) {
            throw new Exception((string) AdiantiCoreTranslator::translate('You must call ^1 before ^2', __METHOD__ . '::setSource', 'show'));
        }

        /* Estilo do container */
        $style = new TStyle("#{$this->id}");
        $style->width = $this->width;
        $style->height = $this->height;
        $style->border = '1px solid #ccc';
        $style->show();

        /* Container da arvore - usa classe generica fancytree-tree */
        $content = new TElement('div');
        $content->id = $this->id;
        $content->class = 'fancytree-tree';

        parent::add($content);
        $this->createTree();
        parent::show();
    }
}
