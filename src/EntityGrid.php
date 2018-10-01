<?php

/**
 * @author: Jakub Patočka, 2018
 */

namespace Quextum\EntityGrid;

use Nette\Application\UI\Form;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;


/**
 * Class EntityGrid
 * @package Quextum\EntityGrid
 */
class EntityGrid extends BaseGrid
{

    /** @var  array */
    protected $config;

    /** @var  array */
    protected $allConfigs;

    public function __construct(array $allConfigs, array $config, IModel $model, Selection $source, $prefix)
    {
        parent::__construct($model, $source, $prefix);
        $this->config = $config;
        $this->allConfigs = $allConfigs;
        $this->tree = $config['tree'];
        $this->order = $config['order'];
        $this->sortable = $config['sortable'];
        $this->view = $this->detectView();
    }

    /**
     * Metoda pro sestavení definic sloupců.
     */
    protected function startup()
    {
        parent::startup();
        foreach ($this->config['columns'] as $column => $type) {
            $col = $this->addColumn($column, $column, null, $type);
            if (\is_array($type)) {
                $col->setArgs($type);
            }
        }
        foreach (static::$ACTION_TYPES as $type) {
            foreach ($this->config[$type] as $column => $callback) {
                $action = null;
                if (\is_numeric($column)) {
                    $column = $callback;
                    $action = $this->_addAction($type, $column, [$this, $callback]);
                } elseif ($callback === true) {
                    $action = $this->_addAction($type, $column, [$this, $column]);
                } elseif (\is_callable($callback)) {
                    $action = $this->_addAction($type, $column, $callback);
                } elseif (\is_array($callback)) {
                    $action = $this->_addAction($type, $column)->setArgs($callback);
                }
                // Aplikace předdefinovanch akcí
                if (isset($this->allConfigs['actions'][$column])) {
                    foreach ($this->allConfigs['actions'][$column] as $prop => $value) {
                        $method = (strpos($prop, 'set') === false && strpos($prop, 'add') === false) ? 'set' . ucfirst($prop) : $prop;
                        $action->$method($value);
                    }
                }
            }
        }
    }


    protected function createComponentSearch(): Search
    {
        $vp = new Search($this->config, $this->allConfigs , $this->prefix, $this->session);
        $vp->onSuccess[] = function (Search $control, array $search) {
            if (\boolval($this->session->search) !== \boolval($search)) {
                $this->redrawControl('control');
            } else {
                $this->redrawControl('paginator');
                $this->redrawControl('items');
            }
            $this->session->search = $search;
        };
        $vp->onCancel[] = function (Search $search) {
            $this->redrawControl('control');
        };
        return $vp;
    }

    /**
     * @param Row $gridRow
     * @param Form $form
     * @param ActiveRow $row
     * @param ArrayHash $values
     */
    public function editFormSucceeded(Row $gridRow, Form $form, ActiveRow $row, ArrayHash $values)
    {
        $this->model->update($row, $values);
        $this->setEditing($row->id, FALSE);
        $this->redrawControl('items');
    }

    public function addFormSucceeded(Form $form, ArrayHash $values)
    {
        $this->model->insert($values);
        $this->redrawControl('items');
    }


    public function edit(Row $gridRow, IRow $row)
    {
        $gridRow->setEditMode(TRUE);
        $this->setEditing($row->id, TRUE);
        $gridRow->redrawControl('row');
    }

    public function delete(Row $gridRow, ActiveRow $row)
    {
        $this->deleteEntity($row);
        $this->redrawControl('items');
    }

    public function deleteEntity(ActiveRow $item)
    {
        $this->model->delete($item);
        $this->redrawControl('items');
    }

    protected function beforeRender():void
    {
        if ($this->session->search) {
            $this->tree = null;
            $this->setView(static::GRID_VIEW);
        }
        parent::beforeRender();
    }


}
