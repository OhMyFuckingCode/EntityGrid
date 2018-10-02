<?php

/**
 * @author: Jakub Patočka, 2018
 */

namespace Quextum\EntityGrid;

use Nette\Application\UI\Presenter;
use Nette\Database\DriverException;
use Nette\Database\Table\Selection;
use Nette\InvalidArgumentException;

/**
 * Class EntityGrid
 * @package Quextum\EntityGrid
 */
class BaseGrid extends Section
{


    public const DEFAULT_ORDER = ['id' => true];
    public const DEFAULT_LIMIT = 20;
    public const LIMITS = [10, 20, 30, 40, 50, null];

    /** @var IModel */
    protected $model;

    protected $order = self::DEFAULT_ORDER;

    protected $sortable = false;
    /** @var  string|bool|null */
    protected $title;


    /**
     * EntityGrid constructor.
     * @param IModel $model
     * @param Selection $source
     * @param $prefix
     */
    public function __construct(IModel $model, Selection $source, $prefix)
    {
        parent::__construct($source, $prefix ?: 'entities.' . $model::TABLE);
        $this->templateName = 'template.latte';
        $this->model = $model;
        $this->grid = $this;
        $this->section = $this;
    }

    protected function presenterAttached(Presenter $presenter):void
    {
        $session = $this->presenter->getSession($this->getSessionSectionName());
        $this->session = $session->data instanceof SessionData ? $session->data : $session->data = new SessionData([
            'order' => $this->order
        ]);
        parent::presenterAttached($presenter);
    }


    public function isTree(): bool
    {
        return (bool)$this->tree;
    }

    public function addColumn($name, $label, $column = null, $type): Column
    {
        return $this->columns[$name] = new Column($name, $this->prefix . '.' . $label, $column ?: $name, $type);
    }

    public function getSessionSectionName():string
    {
        return $this->getUniqueId() . $this->model->getTableName();
    }

    public function loadItems()
    {
        if ($this->tree) {
            $this->source->where($this->tree, NULL);
        }
        $this['search']->apply($this->source);
        $this->applyOrder($this->source);
        $paginator = $this['paginator']->getPaginator();
        $paginator->setItemCount($this->source->count());
        $this->source->limit($paginator->getItemsPerPage(), $paginator->getOffset());
        return $this->items = $this->source->fetchPairs('id');
    }

    protected function beforeRender():void
    {
        parent::beforeRender();
        $this->template->title = $this->title;
        $iterator = 1;
        foreach ($this->columns as $name => $definition) {
            $definition->setHidden($hidden = isset($this->session->hiddenColumns[$name]));
            if (!$hidden && $this->isTree()) {
                $definition->position = $iterator;
                $iterator++;
            }
            if (isset($this->session->order[$name])) {
                $definition->setOrder($this->session->order[$name]);
            }
        }
    }

    public function handleOrder(array $order):void
    {
        $this->session->order = filter_var_array($order, FILTER_VALIDATE_BOOLEAN);
    }

    public function handleHideCol(string $col, bool $hide = true):void
    {
        if ($hide) {
            $this->session->hiddenColumns[$col] = TRUE;
        } else {
            unset($this->session->hiddenColumns[$col]);
        }
        $this->redrawControl('header');
        $this->redrawControl('controls');
        $this->redrawControl('items');
    }


    protected function createComponentPaginator():VisualPaginator
    {
        $vp = new VisualPaginator();
        $paginator = $vp->getPaginator();
        try {
            $paginator->setItemCount($this->source->count());
        } catch (DriverException $e) {
            $this->session->search = [];
            $this->session->order = [];
            throw $e;
        }
        $paginator->setItemsPerPage($this->session->limit);
        return $vp;
    }


    /**
     * @param boolean $sortable
     * @return static
     */
    public function setSortable(bool $sortable)
    {
        $this->sortable = $sortable;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isSortable(): bool
    {
        if (!method_exists($this->model, 'setOrder')) {
            return false;
        }
        return $this->sortable && $this->session->order === ['order' => true];
    }

    public function handleSort(array $order, int $id = null, int $from = null, int $to = null):void
    {
        $toItem = $fromItem = null;
        $order = filter_var_array($order, FILTER_VALIDATE_INT);
        if (!$item = $this->model->get($id)) {
            throw new InvalidArgumentException('nic');
        }
        if ($from && !$fromItem = $this->model->get($from)) {
            throw new InvalidArgumentException('nic');
        }
        if ($to && !$toItem = $this->model->get($to)) {
            throw new InvalidArgumentException('nic');
        }
        $this->model->setOrder($item, $order, $fromItem, $toItem);
        $this->redrawControl('items');
    }

    /**
     * @return IModel
     */
    public function getModel(): IModel
    {
        return $this->model;
    }

    /**
     * @return bool|null|string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param bool|null|string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }


}

