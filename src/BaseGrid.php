<?php

/**
 * @author: Jakub Patočka, 2018
 */

namespace App\AdminModule\Controls\EntityGrid;

use App\AdminModule\Components\VisualPaginator;
use App\Common\Model;
use Nette\Application\UI\Presenter;
use Nette\Database\DriverException;
use Nette\Database\Table\Selection;
use Nette\InvalidArgumentException;

/**
 * Class EntityGrid
 * @package Jax_p\EntityGrid
 */
class BaseGrid extends Section
{


    const DEFAULT_ORDER = ['id' => 'ASC'];
    const DEFAULT_LIMIT = 20;
    const LIMITS = [10, 20, 30, 40, 50, null];

    /** @var Model */
    protected $model;

    protected $order = self::DEFAULT_ORDER;

    protected $sortable = false;


    /**
     * EntityGrid constructor.
     * @param Model $model
     * @param Selection $source
     * @param $prefix
     */
    public function __construct(Model $model, Selection $source, $prefix)
    {
        $prefix = $prefix ?: 'entities.' . \get_class($model)::TABLE;
        parent::__construct($source, $prefix);
        $this->templateName = 'template.latte';
        $this->model = $model;
        $this->grid = $this;
        $this->section = $this;
    }

    protected function presenterAttached(Presenter $presenter)
    {
        $session = $this->presenter->getSession($this->getSessionSectionName());
        $this->session = $session->data ?? $session->data = new SessionData([
                'order' => $this->order
            ]);
        parent::presenterAttached($presenter);
    }

    protected function startup()
    {

    }

    public function isTree(): bool
    {
        return $this->view === static::TREE_VIEW;
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

    protected function beforeRender()
    {
        parent::beforeRender();
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
        $this->template->selectable = $this->isSelectable();
        $this->template->columns = $this->columns;
        $this->template->hiddenColumns = $this->session->hiddenColumns;
        $this->template->session = $this->session;
        $this->template->presenter = $this->getPresenter();
    }

    public function handleOrder(array $order)
    {
        $this->session->order = filter_var_array($order, FILTER_VALIDATE_BOOLEAN);
    }

    public function handleHideCol(string $col, bool $hide = true)
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
     */
    public function setSortable(bool $sortable)
    {
        $this->sortable = $sortable;
    }

    /**
     * @return boolean
     */
    public function isSortable(): bool
    {
        return $this->sortable && $this->session->order === ['order' => true];
    }

    public function handleSort(array $order, int $id = null, int $from = null, int $to = null)
    {
        $toItem = $fromItem = null;
        $order = filter_var_array($order, FILTER_VALIDATE_INT);
        if (!$item = $this->model->get($id)->fetch()) {
            throw new InvalidArgumentException('nic');
        }
        if ($from && !$fromItem = $this->model->get($from)->fetch()) {
            throw new InvalidArgumentException('nic');
        }
        if ($to && !$toItem = $this->model->get($to)->fetch()) {
            throw new InvalidArgumentException('nic');
        }
        $this->model->setOrder($item, $order, $fromItem, $toItem);
        $this->redrawControl('items');
    }

    /**
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
