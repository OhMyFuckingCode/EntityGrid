<?php

namespace Quextum\EntityGrid;


use Nette\Application\UI\Form;
use Nette\Database\SqlLiteral;
use Nette\Database\Table\Selection;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

/**
 * Class GridRow
 * @package Quextum\EntityGrid
 * @method onSuccess(Search $this, array $values)
 * @method onCancel(Search $this)
 */
class Search extends BaseControl
{

    /** @var  callable[] */
    public $onSuccess;

    /** @var  callable[] */
    public $onCancel;

    /** @var  array */
    protected $config;

    /** @var  array */
    protected $options;

    /** @var  ISearchFormFactory */
    protected $formFactory;

    protected $prefix;

    /**
     * Search constructor.
     * @param array $config
     * @param array $options
     * @param string $prefix
     * @param SessionData $values
     */
    public function __construct(array $config, array $options, string $prefix, SessionData $values)
    {
        parent::__construct();
        $this->config = $config;
        $this->prefix = $prefix;
        $this->session = $values;
        $this->options = $options;
        $this->templateName = 'search.latte';
        $this->formFactory = $this->config['searchFactory'];
    }

    /**
     * Creates a searchForm component and setups defaults
     * @return Form
     */
    protected function createComponentSearchForm(): Form
    {
        $form = $this->setupForm();
        if ($this->translator && !$form->getTranslator()) {
            $form->setTranslator($this->translator->domain($this->prefix));
        }
        $form->onSuccess[] = [$this, 'searchFormSucceeded'];
        $form->setDefaults($this->session->search);
        return $form;
    }



    /**
     * Setups form and creates inputs and buttons of form
     */
    protected function setupForm():Form
    {
        $form = $this->formFactory->create($this->grid,$this->config,$this->options);
        /** submit with FontAwesome icons */
        $form->addSubmit('submit', '//forms.buttons.submit')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-search" title="hledat"></i>');
        $form->addSubmit('cancel', '//forms.buttons.cancel')
            ->setValidationScope([])
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-times" title="reset"></i>');
        $form['cancel']->onClick[] = function (SubmitButton $button) {
            $button->form->reset();
            $this->session->search = [];
            $this->onCancel($this);
        };
        return $form;
    }

    /**
     * @param Form $form
     */
    public function searchFormSucceeded(Form $form): void
    {
        $this->onSuccess($this, Helpers::array_filter_recursive($form->getValues()));
    }

    /**
     * @param Selection $source
     */
    public function apply(Selection $source): void
    {
        $this->formFactory->apply($source, $this->session->search);
    }

    protected function beforeRender():void
    {
        parent::beforeRender();
        $this->template->selectable = $this->parent->isSelectable();
        $this->template->columns = $this->parent->getColumns();
        $this->template->search = $this->session->search;
    }

}
