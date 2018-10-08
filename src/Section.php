<?php

/**
 * @author: Jakub Patočka, 2018
 */

namespace Quextum\EntityGrid;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Multiplier;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Response;

/**
 * Class EntityGrid
 * @package Quextum\EntityGrid
 */
class Section extends BaseControl
{
    use TActions;

    //public const TREE_VIEW = 'tree';
    //public const GRID_VIEW = 'grid';

    /** @var  ActiveRow */
    protected $item;

    /** @var  ActiveRow[] */
    protected $items;

    /** @var Selection|null */
    protected $source;

    /** @var  IFormFactory */
    protected $formFactory;

    /** @var Column[] */
    protected $columns = [];

    /** @var  string */
    protected $tree;

    /** @var  string */
    protected $prefix;


    /**
     * Section constructor.
     * @param Selection $source
     * @param $prefix
     */
    public function __construct(Selection $source = null, $prefix)
    {
        parent::__construct();
        $this->templateName = 'section.latte';
        $this->source = $source;
        $this->prefix = $prefix;
        $this->initTraitActions();
    }

    protected function presenterAttached(Presenter $presenter):void
    {
        parent::presenterAttached($presenter);
        $this->startup();
    }

    /**
     * @param Column[] $columns
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @param Selection $source
     * @return static
     */
    public function setSource(Selection $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @return IFormFactory
     */
    public function getFormFactory(): IFormFactory
    {
        return $this->formFactory;
    }

    /**
     * @param IFormFactory $formFactory
     * @return static
     */
    public function setFormFactory(IFormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
        return $this;
    }

    protected function createComponentRow(): Multiplier
    {
        return new Multiplier(function (int $id) {
            $row = new Row($this->formFactory, $this->columns, $this->fetchItem($id));
            $row->setSelectable($this->isSelectable());
            $row->onSuccess[] = [$this, 'editFormSucceeded'];
            $row->onCancel[] = [$this, 'endEditing'];
            return $row;
        });
    }

    protected function createComponentNew(): Row
    {
        $row = new Row($this->formFactory, $this->columns, $this->grid->getModel()->create());
        $row->onSuccess[] = [$this, 'addFormSucceeded'];
        $row->onCancel[] = [$this, 'endCreating'];
        return $row;
    }


    protected function startup()
    {

    }

    /**
     * @return Selection|null
     */
    public function getSource():?Selection
    {
        return $this->source;
    }

    /**
     * @return Selection|null
     */
    public function getSelection():?Selection
    {
        return $this->source ? clone $this->source : null;
    }

    protected function applyOrder(Selection $source)
    {
        if ($order = $this->session->order) {
            foreach ($order as $column => $direction) {
                $source->order("$column " . ($direction ? 'ASC' : 'DESC'));
            }
        }
    }

    /**
     * @return ActiveRow[]
     */
    public function loadItems():array
    {
        $source = $this->getSelection();
        $this->applyOrder($source);
        return $this->items = $source->fetchPairs($source->getPrimary());
    }

    /**
     * @param int $id
     * @return false|ActiveRow
     */
    public function fetchItem(int $id)
    {
        return $this->items[$id]??$this->getSource()->get($id);
    }

    /**
     * @return ActiveRow|null
     */
    public function getItem():?ActiveRow
    {
        return $this->item;
    }

    /**
     * @param ActiveRow $item
     * @return Section
     */
    public function setItem(ActiveRow $item): Section
    {
        $this->item = $item;
        return $this;
    }


    public function getItems():array
    {
        if (!$this->source) {
            return [];
        }
        return $this->items ?: $this->loadItems();
    }

    protected function beforeRender():void
    {
        parent::beforeRender();
        foreach ($this->columns as $name => $definition) {
            $definition->setHidden(isset($this->session->hiddenColumns[$name]));
            if (isset($this->session->order[$name])) {
                $definition->setOrder($this->session->order[$name]);
            }
        }
        $this->template->selectable = $this->isSelectable();
        $this->template->selection = $this->session->selection;
        $this->template->columns = $this->columns;
        $this->template->limit = $this->session->limit;
        $this->template->hiddenColumns = $this->session->hiddenColumns;
        $this->template->session = $this->session;
        $this->template->expanded = $this->isExpanded();
    }

    public function handleOrder(array $order)
    {
        $this->session->order = $order;
    }

    public function handleHideCol(string $col, bool $hide = true)
    {
        if ($hide) {
            $this->session->hiddenColumns[$col] = TRUE;
        } else {
            unset($this->session->hiddenColumns[$col]);
        }
        $this->redrawControl('header');
        $this->redrawControl('items');
    }
/*
    public function handleSetSelected(int $id, bool $selected = true)
    {
        $empty = (bool)$this->session->selection;
        if ($selected) {
            $this->session->selection[$id] = TRUE;
        } else {
            unset($this->session->selection[$id]);
        }
        if ($empty !== $this->session->selection) {
            $this->redrawControl('groupActions');
        } else {
            $this->presenter->terminate();
        }

    }*/

    public function handleResetOrder()
    {
        $this->session->order = BaseGrid::DEFAULT_ORDER;
    }

    public function handleLimit(?int $limit): void
    {
        if (!\in_array($limit, BaseGrid::LIMITS, true)) {
            throw new BadRequestException(Response::S406_NOT_ACCEPTABLE);
        }
        $this->session->limit = $limit;
        $this->redrawControl('items');
        $this->redrawControl('paginator');
    }

    public function setEditing(int $id, bool $editing = true)
    {
        if ($editing) {
            $this->session->editing[$id] = true;
        } else {
            unset($this->session->editing[$id]);
        }

    }

    /**
     * @return string
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @param string $tree
     * @return Section
     */
    public function setTree(string $tree): Section
    {
        $this->tree = $tree;
        return $this;
    }


    public function endEditing(Row $gridRow, SubmitButton $button, IRow $row)
    {
        $this->setEditing($row->id, false);
        $gridRow->setEditMode(false);
        $gridRow->redrawControl('row');
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return SessionData
     */
    public function getSession(): SessionData
    {
        return $this->session;
    }

    /**
     * @return boolean
     */
    public function isExpanded(): bool
    {
        return $this->item ? isset($this->session->expandedRows[$this->item->getPrimary()]) : true;
    }

    /**
     * @param int[] $order
     * @param int $id
     * @param int $from
     * @param int $to
     */
    public function handleSort(array $order, int $id = null, int $from = null, int $to = null)
    {
        //$order = filter_var_array($order,FILTER_VALIDATE_INT);
        $this->grid->handleSort(...\func_get_args());
    }


}