<?php

/**
 * @author: Jakub Patočka, 2018
 */

namespace Quextum\EntityGrid;

use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
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

        //$this->view = $this->detectView();
    }

    public function getFormatter()
    {
        return $this->config['formatter'];
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
        $vp = new Search($this->config, $this->allConfigs, $this->prefix, $this->session);
        $vp->onSuccess[] = function (Search $control, array $search) {
            $this['paginator']->redrawControl();
            $this->redrawControl('selection');
            $this->redrawControl('items');
            $this->session->search = $search;
        };
        $vp->onCancel[] = function (Search $search) {
            $this->redrawControl('control');
        };
        return $vp;
    }

    protected function createComponentGroupAction(): Multiplier
    {
        return new Multiplier(function ($name) {
            $action = $this->groupActions[$name];
            $button = new Button($action);
            $button->onClick[] = function (Button $button) {
                $post = $this->getPresenter()->getHttpRequest()->getPost();
                $post = filter_var_array($post, [
                    'exclude' => FILTER_VALIDATE_BOOLEAN,
                    'ids' => [
                        'filter' => FILTER_VALIDATE_INT,
                        'flags' => FILTER_FORCE_ARRAY | FILTER_REQUIRE_ARRAY,
                    ]
                ]);
                $source = clone $this->source;
                $source->where($post['exclude'] ? 'NOT ' : '' . "{$source->getPrimary()} IN ?", array_filter($post['ids']));
                foreach ($source as $item) {
                    bdump($item);
                    //$button->getAction()->onClick($this,$item);
                }
            };
            return $button;
        });
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


    public function delete(Section $section, ?ActiveRow $row = null)
    {
        if ($section instanceof Row) {
            //$this->deleteEntity($row);
        } elseif ($section instanceof self) {

        }

        $this->redrawControl('items');
    }

    public function deleteEntity(ActiveRow $item): void
    {
        $this->model->delete($item);
    }

    protected function beforeRender():void
    {
        if ($this->session->search) {
            $this->tree = null;
        }
        parent::beforeRender();
    }


}
