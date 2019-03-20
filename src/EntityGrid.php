<?php

/**
 * @author: Jakub Patočka, 2018
 */

namespace Quextum\EntityGrid;

use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Database\ForeignKeyConstraintViolationException;
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

    /** @var bool */
    protected $groupEdit = false;
    /** @var callable */
    protected $formatterCallback;


    public function __construct(array $allConfigs, array $config, IModel $model, Selection $source, $prefix)
    {
        parent::__construct($model, $source, $prefix);
        $this->config = $config;
        $this->allConfigs = $allConfigs;
        $this->tree = $config['tree'];
        $this->order = $config['order'];
        $this->sortable = $config['sortable'];
        $this->imageLinkProvider = $config['imageLinkProvider'];
        //$this->view = $this->detectView();
    }

    public function getFormatter()
    {
        return $this->config['formatter'];
    }

    /**
     * Metoda pro sestavení definic sloupců.
     */
    protected function startup():void
    {
        parent::startup();
        foreach ($this->config['alias'] as $alias => $tableChain) {
            $this->source->alias($tableChain, $alias);
        }
        foreach ($this->config['columns'] as $column => $type) {
            $col = $this->addColumn($column, $column, null, $type);
            if (\is_array($type)) {
                $col->setArgs($type);
            }
        }
        $this->addActions($this->config, $this->allConfigs['actions']);

    }

    /**
     * @return Search
     */
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
            $button->onClick[] = function (Button $button, Action $action) {
                $post = $this->getPresenter()->getHttpRequest()->getPost();
                if (array_key_exists('ids', $post)) {
                    $this->session->selection->set($post);
                }
                $action->perform($this, $button);
            };
            return $button;
        });
    }


    public function handleShowSelection(bool $show): void
    {
        $this->session->selection->set($this->getPresenter()->getHttpRequest()->getPost());
        $this->session->showSelection = $show;
        $this->redrawControl('bar');
        $this->redrawControl('items');
        $this['paginator']->redrawControl();
    }

    /**
     * @param Row $gridRow
     * @param Form $form
     * @param ActiveRow $row
     * @param ArrayHash $values
     */
    public function editFormSucceeded(Row $gridRow, Form $form, ActiveRow $row, ArrayHash $values): void
    {
        $this->model->update($row, $values);
        $this->setEditing($row->id, FALSE);
        $this->redrawControl('items');
    }

    public function addFormSucceeded(Form $form, ArrayHash $values): void
    {
        $this->model->insert($values);
        $this->redrawControl('items');
    }


    public function edit(Row $gridRow, IRow $row): void
    {
        $gridRow->setEditMode(TRUE);
        $this->setEditing($row->id, TRUE);
        $gridRow->redrawControl('row');
    }



    public function groupEdit(Section $section): void
    {
        if ($this->formFactory) {
            $this->groupEdit = true;
            $this->redrawControl('groupEdit');
        }
    }

    public function createComponentGroupEditForm(): Form
    {
        $factory = new GroupFormFactory($this->formFactory);
        $form = $factory->create();
        $form->setTranslator($this->translator->domain($this->prefix));
        $form->getElementPrototype()->addClass('ajax');
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
            if (\count($values)) {
                foreach ($this->getUserSelection() as $row) {
                    $this->model->update($row,clone $values);
                }
            }
            $this->groupEdit = false;
            $this->redrawControl('items');
            $this->redrawControl('groupEdit');
        };
        $form->onError[] = function (Form $form) {
            $this->redrawControl('groupEditForm');
        };
        $form->addSubmit('submit', '//entityGrid.btn.submit');
        return $form;
    }

    public function delete(Section $section, ?ActiveRow $row = null): void
    {
        $this->deleteEntity($row);
        $this->session->selection->remove($id = $row->getPrimary());
        $this->control->deselect($id);
        $section->redrawItems();
    }

    public function groupDelete(Section $section): void
    {
        foreach ($this->getUserSelection() as $row) {
            $this->delete($section, $row);
        }
    }

    public function deleteEntity(ActiveRow $item): void
    {
        $this->model->delete($item);
    }

    public function setValue(ActiveRow $item, string $column, bool $value): void
    {
        $this->model->update($item, ArrayHash::from([
            $column => $value
        ]));
    }

    protected function beforeRender():void
    {
        if ($this->session->search || $this->session->showSelection) {
            $this->tree = null;
        }
        $this->template->groupEdit = $this->groupEdit;
        parent::beforeRender();
    }

    public function handleCleanSelection():void
    {
        $this->session->selection->clean();
        $this->presenter->terminate();
    }

    public function handleReload():void
    {
        $this->redrawControl();
    }

    public function handleDumpSelection():void
    {
        $this->presenter->terminate();
    }
}
