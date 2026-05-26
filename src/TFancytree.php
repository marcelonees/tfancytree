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
    private $height;
    private $width;
    private $checkbox;
    private $selectMode;
    private $dragDrop;
    private $onClickAction;
    private $onSelectAction;
    private $selectedKeys;
    private $expandedKeys;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->id = 'tfancytree_' . mt_rand(1000000000, 1999999999);
        $this->{'id'} = $this->id;
        $this->selectMode = 2;
    }

    /**
     * Set the tree source data
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Set size
     */
    public function setSize($width, $height)
    {
        $this->setWidth($width);
        $this->setHeight($height);
    }

    /**
     * Set height
     */
    public function setHeight($height)
    {
        $this->height = $height;
        if (is_numeric($height)) {
            $this->{'style'} = "height: {$height}px; overflow: auto;";
        } else {
            $this->{'style'} = "height: {$height}; overflow: auto;";
        }
    }

    /**
     * Set width
     */
    public function setWidth($width)
    {
        $this->width = $width;
        if (is_numeric($width)) {
            $this->{'style'} = (isset($this->{'style'}) ? $this->{'style'} : '') . "width: {$width}px;";
        } else {
            $this->{'style'} = (isset($this->{'style'}) ? $this->{'style'} : '') . "width: {$width};";
        }
    }

    /**
     * Enable checkboxes
     */
    public function setCheckbox($checkbox)
    {
        $this->checkbox = $checkbox;
    }

    /**
     * Set selection mode
     */
    public function setSelectMode($mode)
    {
        $this->selectMode = $mode;
    }

    /**
     * Enable drag & drop
     */
    public function setDragDrop($dragDrop)
    {
        $this->dragDrop = $dragDrop;
    }

    /**
     * Set selected keys
     */
    public function setSelectedKeys($keys)
    {
        $this->selectedKeys = $keys;
    }

    /**
     * Set expanded keys
     */
    public function setExpandedKeys($keys)
    {
        $this->expandedKeys = $keys;
    }

    /**
     * Set onClick action
     */
    public function setOnClick(TAction $action)
    {
        $this->onClickAction = $action;
    }

    /**
     * Set onSelect action
     */
    public function setOnSelect(TAction $action)
    {
        $this->onSelectAction = $action;
    }

    /**
     * Build node from array
     */
    private function buildNode($node)
    {
        $fancyNode = [];

        if (isset($node['id'])) {
            $fancyNode['key'] = (string) $node['id'];
        } elseif (isset($node['key'])) {
            $fancyNode['key'] = (string) $node['key'];
        }

        if (isset($node['text'])) {
            $fancyNode['title'] = $node['text'];
        } elseif (isset($node['title'])) {
            $fancyNode['title'] = $node['title'];
        }

        if (isset($node['children']) && !empty($node['children'])) {
            $fancyNode['children'] = [];
            foreach ($node['children'] as $child) {
                $fancyNode['children'][] = $this->buildNode($child);
            }
        } elseif (isset($node['lazy']) && $node['lazy'] === true) {
            $fancyNode['lazy'] = true;
        }

        if (isset($node['expanded']) && $node['expanded']) {
            $fancyNode['expanded'] = true;
        }

        if (isset($node['selected']) && $node['selected']) {
            $fancyNode['selected'] = true;
        }

        if (isset($node['icon'])) {
            $fancyNode['icon'] = $node['icon'];
        }

        if (isset($node['folder'])) {
            $fancyNode['folder'] = $node['folder'];
        }

        return $fancyNode;
    }

    /**
     * Create the tree (similar to createMap)
     */
    public function createTree()
    {
        // Verifica se os arquivos necessários existem
        $requiredFiles = [
            'vendor/marcelonees/tfancytree/assets/jquery-ui/css/jquery-ui.css',
            'vendor/marcelonees/tfancytree/assets/jquery-ui/js/jquery-ui.min.js',
            'vendor/marcelonees/tfancytree/assets/fancytree/css/ui.fancytree.min.css',
            'vendor/marcelonees/tfancytree/assets/fancytree/js/jquery.fancytree-all-deps.min.js'
        ];

        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                throw new Exception("Arquivo necessário não encontrado: {$file}");
            }
        }

        // Processar source
        $processedSource = [];
        foreach ($this->source as $node) {
            $processedSource[] = $this->buildNode($node);
        }
        $sourceJson = json_encode($processedSource);

        // Preparar configuração
        $config = [
            'source' => $processedSource,
            'checkbox' => $this->checkbox === true,
            'selectMode' => (int) $this->selectMode
        ];

        if ($this->dragDrop) {
            $config['dnd'] = ['autoExpandMS' => 1000];
        }

        if ($this->selectedKeys && is_array($this->selectedKeys)) {
            $config['selectedKeys'] = $this->selectedKeys;
        }

        if ($this->expandedKeys && is_array($this->expandedKeys)) {
            $config['expandedKeys'] = $this->expandedKeys;
        }

        $configJson = json_encode($config);

        // Preparar eventos
        $events = [];
        if ($this->onClickAction) {
            $serializedAction = $this->onClickAction->serialize(false);
            $events[] = "click: function(event, data) { __adianti_ajax_exec('{$serializedAction}&key=' + encodeURIComponent(data.node.key)); }";
        }

        if ($this->onSelectAction) {
            $serializedAction = $this->onSelectAction->serialize(false);
            $events[] = "select: function(event, data) { var keys = data.tree.getSelectedKeys(); __adianti_ajax_exec('{$serializedAction}&keys=' + JSON.stringify(keys)); }";
        }

        $eventsStr = !empty($events) ? ', ' . implode(', ', $events) : '';

        // Garante que o CSS seja carregado primeiro
        TStyle::importFromFile('vendor/marcelonees/tfancytree/assets/jquery-ui/css/jquery-ui.css');
        TStyle::importFromFile('vendor/marcelonees/tfancytree/assets/fancytree/css/ui.fancytree.min.css');

        // Cria o sistema de carregamento robusto
        TScript::create("
        /* Função principal de inicialização */
        function initializeTree_{$this->id}() {
            try {
                $('#{$this->id}').fancytree({$configJson}{$eventsStr});
                console.log('Fancytree inicializado com sucesso!');
            } catch(initError) {
                console.error('Erro na inicialização da árvore:', initError);
                $('#{$this->id}').html('<div style=\"padding:20px;color:red;\">Erro ao carregar a árvore</div>');
            }
        }
        
        /* Carrega os scripts necessários em ordem */
        var requiredScripts = [
            'vendor/marcelonees/tfancytree/assets/jquery-ui/js/jquery-ui.min.js',
            'vendor/marcelonees/tfancytree/assets/fancytree/js/jquery.fancytree-all-deps.min.js'
        ];
        
        /* Função para carregar scripts sequencialmente */
        function loadScript(scripts, callback) {
            if (scripts.length === 0) {
                callback();
                return;
            }
            
            var currentScript = scripts.shift();
            $.getScript(currentScript)
                .done(function() {
                    console.log('Script carregado:', currentScript);
                    loadScript(scripts, callback);
                })
                .fail(function() {
                    console.error('Falha ao carregar:', currentScript);
                    loadScript(scripts, callback);
                });
        }
        
        /* Inicia o processo de carregamento */
        loadScript(requiredScripts, function() {
            /* Verifica se todos os requisitos estão carregados */
            if (typeof $.fn.fancytree === 'undefined') {
                console.error('Fancytree não carregado corretamente');
                $('#{$this->id}').html('<div style=\"padding:20px;color:red;\">Biblioteca Fancytree não carregada</div>');
                return;
            }
            
            /* Executa a inicialização */
            setTimeout(initializeTree_{$this->id}, 100);
        });
        ");
    }

    /**
     * Show the widget
     */
    public function show()
    {
        if (empty($this->source)) {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before ^2', __METHOD__ . '::setSource', 'show'));
        }

        // Adiciona estilo à div
        $currentStyle = isset($this->{'style'}) ? $this->{'style'} : '';
        $this->{'style'} = $currentStyle . ';border:1px solid #ddd;background:#fff;';

        // Mostra a div
        parent::show();

        // Cria a árvore
        $this->createTree();
    }
}
