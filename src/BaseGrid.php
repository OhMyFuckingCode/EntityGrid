<?php

/**
 * @author: Jakub Patočka, 2018
 */

namespace Quextum\EntityGrid;

use Nette\Database\DriverException;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\InvalidArgumentException;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

/**
 * Class EntityGrid
 * @package Quextum\EntityGrid
 */
class BaseGrid extends Section
{

    public const DEFAULT_LIMIT = 20;
    public const LIMITS = [10, 20, 30, 40, 50, null];

    /** @var IModel */
    protected $model;

    protected $order;

    protected $sortable = false;

    /** @var  string|bool|null */
    protected $title;

    /** @var  Selection|null */
    protected $selection;

    /** @var  Selection|null */
    protected $userSelection;

    /** @var  Control */
    protected $control;

    /** @var  IImageLinkProvider */
    protected $imageLinkProvider;

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

    public function loadState(array $params)
    {
        parent::loadState($params);
        $session = $this->presenter->getSession($this->getSessionSectionName());
        $this->session = $session->data instanceof SessionData ? $session->data : $this->resetSession();
        $this->control = new Control($this->getSessionSectionName());
        $this->resetSession();
    }

    protected function resetSession()
    {
        $session = $this->presenter->getSession($this->getSessionSectionName());
        return $this->session = $session->data = SessionData::from([
            'order' => $this->order ?: [reset($this->columns)->getName() => true]
        ], false);
    }

    public function afterRender():void
    {
        $this->control->send($this->presenter);
    }

    public function isTree(): bool
    {
        return (bool)$this->tree;
    }

    public function addColumn($name, $label, $column = null, $type): Column
    {
        /*$this->loa
        bdump($this->locale,'addColumn');*/
        $column = $column ?: $name;
        $table = Strings::before($column,'.')?:$this->source->getName();

        return $this->columns[$name] = new Column($name, $this->prefix . '.' . $label, $column,$table, $type, $this->locale);
    }

    public function getSessionSectionName():string
    {
        return implode('-', [Strings::webalize($this->getPresenter()->getAction(true)), $this->getUniqueId(), $this->model->getTableName()]);
    }


    /**
     * @return Selection
     */
    public function getSelection():Selection
    {
        if ($this->selection === null) {
            $this->selection = clone $this->source;
        }
        return $this->selection;
    }

    /**
     * @return Selection
     */
    public function getUserSelection():Selection
    {
        if ($this->userSelection === null) {
            $this->userSelection = clone $this->source;
            $this->filterSelection($this->userSelection);
        }
        return $this->userSelection;
    }


    protected function filterSelection(Selection $source): void
    {
        $this->session->selection->filter($source);
    }


    public function loadItems():array
    {
        if ($this->session->showSelection) {
            $source = $this->getUserSelection();
        } else {
            $source = $this->getSelection();
        }
        $this['search']->apply($source);
        if ($this->tree) {
            $source->where($this->tree, NULL);
        }
        $paginator = $this['paginator']->getPaginator();
        $paginator->setItemCount($source->count('*'));
        $source->limit($paginator->getItemsPerPage(), $paginator->getOffset());
        $this->applyOrder($source);
        try {
            return $this->items = $source->fetchPairs($source->getPrimary());
        } catch (DriverException $exception) {
            $this->resetSession();
            throw $exception;
        }

    }

    protected function beforeRender():void
    {
        parent::beforeRender();
        $this->template->showSelection = $this->session->showSelection;
        $this->template->uniqueId = $this->getSessionSectionName();
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
        $this->redrawControl('controls');
        $this->redrawControl('items');
        $this->redrawControl('header');
    }


    protected function createComponentPaginator():VisualPaginator
    {
        $vp = new VisualPaginator();
        $vp->onChange[] = function () {
            $this->redrawControl('items');
        };
        $paginator = $vp->getPaginator();
        try {
            $paginator->setItemCount($this->getSelection()->count());
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

    public function setValue(ActiveRow $item, string $column, bool $value): void
    {
        $this->model->update($item, ArrayHash::from([
            $column => $value
        ]));
    }

    /**
     * @return IImageLinkProvider
     */
    public function getImageLinkProvider(): ?IImageLinkProvider
    {
        return $this->imageLinkProvider;
    }

    /**
     * @param IImageLinkProvider $imageLinkProvider
     */
    public function setImageLinkProvider(?IImageLinkProvider $imageLinkProvider)
    {
        $this->imageLinkProvider = $imageLinkProvider;
    }


}

