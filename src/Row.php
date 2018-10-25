<?php

namespace Quextum\EntityGrid;


use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Database\IRow;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\GroupedSelection;
use Nette\Database\Table\Selection;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;

/**
 * Class GridRow
 * @package Quextum\EntityGrid
 * @method onSuccess(Row $row, Form $form, IRow $item, ArrayHash $values)
 * @method onCancel(Row $row, SubmitButton $button, IRow $item)
 */
class Row extends Section
{

    /** @var  callable[] */
    public $onSuccess;

    /** @var  callable[] */
    public $onCancel;

    /** @var  boolean */
    protected $editMode;

    /** @var  boolean */
    protected $selected;

    /** @var  boolean */
    protected $selectable;

    /** @var  boolean */
    protected $expanded = false;

    /** @var  GroupedSelection|null */
    protected $childes;

    /**
     * GridRow constructor.
     * @param IFormFactory $formFactory
     * @param Column[] $columnDefinitions
     * @param ActiveRow|IRow $item
     */
    public function __construct(IFormFactory $formFactory = null, array $columnDefinitions = [],IRow $item)
    {
        parent::__construct(null, $formFactory);
        $this->templateName = 'row.latte';
        $this->item = $item;
        $this->formFactory = $formFactory;
        $this->columns = $columnDefinitions;
    }

    /**
     * @return GroupedSelection|Selection
     */
    public function getSource():Selection
    {
        return $this->item->related($this->grid->tree);
    }

    /**
     * @param boolean $selected
     * @return Row
     */
    public function setSelected(bool $selected = true):self
    {
        $this->selected = $selected;
        return $this;
    }

    protected function beforeRender():void
    {
        parent::beforeRender();
        $this->template->editable = $this->isEditable();
        $this->template->columns = $this->columns;
        $this->template->item = $this->item;
        $this->template->selectable = $this->selectable = $this->selectable ?? (bool)$this->groupActions;
        $this->template->editMode = isset($this->session->editing[$this->item->id]);
        $this->template->selected = $this->session->selection->has($this->item->id);
        $this->template->expanded = isset($this->session->expandedRows[$this->item->id]);
        $this->template->row = $this;
    }

    /**
     * @param boolean $selectable
     * @return Row
     */
    public function setSelectable(bool $selectable = true): self
    {
        $this->selectable = $selectable;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEditMode(): bool
    {
        return $this->editMode;
    }

    /**
     * @param boolean $editMode
     * @return Row
     */
    public function setEditMode(bool $editMode): self
    {
        $this->editMode = $editMode;
        return $this;
    }

    public function isEditable(): bool
    {
        return (bool)$this->formFactory;
    }

    protected function createComponentForm()
    {
        $form = $this->formFactory->create($this->item);
        $form->addSubmit('submit', '//forms.buttons.submit');
        $form->addSubmit('cancel', '//forms.buttons.cancel')
            ->setValidationScope([])->onClick[] = function (SubmitButton $button) {
            $button->form->onSuccess = null;
            $this->onCancel($this, $button, $this->item);
        };
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
            $this->onSuccess($this, $form, $this->item, $values);
        };
        $form->onError[] = function (Form $form) {
            bdump($form->getErrors());
        };
        $form->setDefaults($this->item->toArray());
        return $form;
    }

    protected function createComponentAction(): Multiplier
    {
        return new Multiplier(function ($name) {
            $action = $this->actions[$name];
            $button = new Button($action);
            $button->onClick[] = function (Button $button,Action $action) {
                $action->perform($this->grid, $button);
            };
            return $button;
        });
    }

    /**
     * @return Selection|null
     */
    public function getChildren():?Selection
    {
        return $this->childes ?: $this->childes = $this->getSource();
    }

    public function getDepth(): int
    {
        /** @var Row $row */
        $row = $this->lookup(static::class, false);
        return $row ? $row->getDepth() + 1 : 0;
    }


    /**
     * @param bool $expanded
     * @return Section
     */
    public function setExpanded(bool $expanded = true): Section
    {
        if (!empty($this->session->expandedRows[$this->item->getPrimary()])) {
            unset($this->session->expandedRows[$this->item->getPrimary()]);
        } else {
            $this->session->expandedRows[$this->item->getPrimary()] = true;
        }

        return $this;
    }

    /**
     * @param bool $expanded
     * @return Section
     */
    public function handleSetExpanded(bool $expanded = true): Section
    {
        return $this->setExpanded($expanded);
    }

    public function handleSetValue(string $column, bool $value)
    {
        $this->grid->setValue($this->item, $column, $value);
        $this->redrawControl('row');
    }

}
