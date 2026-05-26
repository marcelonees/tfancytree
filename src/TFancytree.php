<?php

namespace MarceloNees\TFancytree;

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Exception;

class TFancytree extends TElement
{
    private $id;
    private $source;
    private $height;
    private $width;
    private $autoCollapse;
    private $checkbox;
    private $selectMode;
    private $editable;
    private $dragDrop;
    private $clickAction;
    private $onClickAction;
    private $onSelectAction;
    private $onActivateAction;
    private $selectedKeys;
    private $expandedKeys;
    private static $scriptsIncluded = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->id = 'tfancytree_' . mt_rand(1000000000, 1999999999);
        $this->{'id'} = $this->id;
        $this->selectMode = 2;
        $this->clickAction = 'activate';

        // Registrar assets do componente
        $this->registerAssets();
    }

    /**
     * Register required assets
     */
    private function registerAssets()
    {
        if (!self::$scriptsIncluded) {
            // Registrar CSS e JS do componente
            parent::addStyle('fancytree-css', 'vendor/marcelonees/tfancytree/assets/css/ui.fancytree.min.css');
            parent::addScript('fancytree-js', 'vendor/marcelonees/tfancytree/assets/js/jquery.fancytree-all-deps.min.js');

            // jQuery UI (dependência)
            parent::addStyle('jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css');
            parent::addScript('jquery-ui', 'https://code.jquery.com/ui/1.13.2/jquery-ui.min.js');

            self::$scriptsIncluded = true;
        }
    }

    /**
     * Set the tree source data
     */
    public function setSource($source)
    {
        $this->source = $source;
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
     * Set onActivate action
     */
    public function setOnActivate(TAction $action)
    {
        $this->onActivateAction = $action;
    }

    /**
     * Build node from array
     */
    private function buildNode($node)
    {
        $fancyNode = [];

        if (isset($node['id'])) {
            $fancyNode['key'] = $node['id'];
        } elseif (isset($node['key'])) {
            $fancyNode['key'] = $node['key'];
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

        return $fancyNode;
    }

    /**
     * Show the widget
     */
    public function show()
    {
        if (empty($this->source)) {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before ^2', __METHOD__ . '::setSource', 'show'));
        }

        $config = [
            'checkbox' => $this->checkbox,
            'selectMode' => $this->selectMode,
            'clickAction' => $this->clickAction
        ];

        if ($this->dragDrop) {
            $config['dnd'] = ['autoExpandMS' => 1000];
        }

        if ($this->selectedKeys) {
            $config['selectedKeys'] = $this->selectedKeys;
        }

        if ($this->expandedKeys) {
            $config['expandedKeys'] = $this->expandedKeys;
        }

        $events = [];

        if ($this->onClickAction) {
            $serializedAction = $this->onClickAction->serialize(false);
            $events[] = "click: function(event, data) { __adianti_ajax_exec('{$serializedAction}&key=' + data.node.key); }";
        }

        if ($this->onSelectAction) {
            $serializedAction = $this->onSelectAction->serialize(false);
            $events[] = "select: function(event, data) { var keys = data.tree.getSelectedKeys(); __adianti_ajax_exec('{$serializedAction}&keys=' + JSON.stringify(keys)); }";
        }

        $sourceScript = "source: " . json_encode(array_map([$this, 'buildNode'], $this->source));
        $configScript = json_encode($config);
        $eventsScript = !empty($events) ? ", " . implode(", ", $events) : "";

        $script = new TElement('script');
        $script->{'type'} = 'text/javascript';
        $script->add(
            <<<JS
        $(function() {
            $("#{$this->id}").fancytree({
                {$sourceScript},
                {$configScript}
                {$eventsScript}
            });
        });
JS
        );

        parent::add($script);
        parent::show();
    }
}
