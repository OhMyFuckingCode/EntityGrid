<?php

namespace App\AdminModule\Controls\EntityGrid;

use App\Common\Controls\BaseControl;
use App\Common\Forms\BaseFormFactory;
use App\Common\Forms\Form;
use App\Helpers;
use Nette\Database\SqlLiteral;
use Nette\Database\Table\Selection;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\Html;

/**
 * Class GridRow
 * @package App\AdminModule\Controls\EntityGrid
 * @method onSuccess(Search $this, array $values)
 * @method onCancel(Search $this)
 */
class Search extends BaseControl
{
    use TGridComponent;

    /** @var  callable[] */
    public $onSuccess;

    /** @var  callable[] */
    public $onCancel;

    /** @var  array */
    protected $config;

    /** @var  array */
    protected $options;

    /** @var string */
    protected $domain;

    /** @var Selection */
    protected $items;

    /** @var  BaseFormFactory */
    protected $formFactory;

    protected $prefix;

    /**
     * Search constructor.
     * @param array $config
     * @param Selection $items
     * @param BaseFormFactory $factory
     * @param SessionData $values
     */
    public function __construct(array $config, array $options, Selection $items, string $prefix, SessionData $values)
    {
        parent::__construct();
        $this->templateName = 'search.latte';
        $this->config = $config;
        $this->prefix = $prefix;
        $this->session = $values;
        $this->items = $items;
        $this->options = $options;
    }

    /**
     * Creates a searchForm component and setups defaults
     * @return Form
     */
    protected function createComponentSearchForm(): Form
    {
        $form = new Form();
        $form->setTranslator($this->translator->domain($this->prefix));
        $this->setupForm($form);
        $form->onSuccess[] = [$this, 'searchFormSucceeded'];
        $form->setDefaults($this->session->search);
        return $form;
    }

    /**
     * Setups form and creates inputs and buttons of form
     * @param Form $form
     */
    protected function setupForm(Form $form)
    {

        /**
         * creates all inputs
         * @var $column
         * @var $type
         */
        foreach ($this->config['search'] as $column => $type) {
            switch ($type) {
                case 'int':
                    $form->addInteger($column);
                    break;
                case 'select':
                case 'multiselect':
                    $method = $type === 'select' ? 'addSelect' : 'addMultiSelect';
                    $model = $this->grid->getModel();
                    $context = $model->getContext();
                    if ($t = $context->getStructure()->getBelongsToReference($model->getTableName(), $column)) {
                        $belongs = $context->table($t);
                        $form->$method($column, $column, $this->services->getHelpers()->toPairs($belongs->fetchAll()));
                    } else {
                        $items = (clone $this->items)->select('DISTINCT ?', new SqlLiteral($column))->fetchPairs($column, $column);
                        $form->$method($column, $column, Helpers::toHtml($items));
                    }
                    break;
                case 'checkbox':
                    /* $form->addSelect($column, $column, [
                         true => Html::el('div')->addHtml(Html::el('i')->class('fa fa-check-circle')),
                         false => Html::el('div')->addHtml(Html::el('i')->class('fa fa-times-circle'))])
                         ->setPrompt(Html::el('div')->addHtml(Html::el('i')->class('fa fa-circle')));*/
                    $form->addRadioList($column, $column, [
                        null => Html::el('i')->class('fa fa-lg fa-circle text-gray'),
                        true => Html::el('i')->class('fa fa-lg fa-check-circle text-green'),
                        false => Html::el('i')->class('fa fa-lg fa-times-circle text-danger')]);
                    break;
                case 'datetimerange':
                    $cont = $form->addContainer($column);
                    $cont->addDateTime('from');
                    $cont->addDateTime('to');
                    break;
                default:
                    $form->addText($column)->setNullable();
                    break;
            }
        }

        /** if it is bootstrap, setup classes */
        if ($this->options && $this->options['bootstrap']) {
            foreach ($form->getControls() as $control) {
                $control->setAttribute('class', 'form-control');
            }
        }

        /** submit with FontAwesome icons */
        $form->addSubmit('submit', '//forms.buttons.submit')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="fa fa-search" title="hledat"></i>');

        /** if there is some current search, create cancel button */
        //if ($this->session->search) {
        $form->addSubmit('cancel', '//forms.buttons.cancel')
            ->setValidationScope([])
            ->onClick[] = function (SubmitButton $button) {
            $button->form->reset();
            $this->session->search = [];
            $this->onCancel($this);
        };
        $form['cancel']->getControlPrototype()->setName('button')->setHtml('<i class="fa fa-times" title="reset"></i>');
        //}
    }

    /**
     * @param Form $form
     */
    public function searchFormSucceeded(Form $form)
    {
        $this->onSuccess($this, Helpers::array_filter_recursive($form->getValues()));
    }

    /**
     * @param Selection $source
     */
    public function apply(Selection $source)
    {
        if ($this->session->search) {
            foreach ($this->session->search as $key => $value) {
                $type = $this->config['search'][$key]??null;
                switch ($type) {
                    case 'regexp':
                        $source->where("$key REGEXP ?", $value);
                        break;
                    case 'match':
                        $source->where(" MATCH ($key) AGAINST ?", $value);
                        break;
                    case 'like':
                        $source->where("$key LIKE ?", "%$value%");
                        break;
                    case 'datetimerange':
                        if ($from = $value['from']??null) {
                            $source->where("$key >= ? ", $from);
                        }
                        if ($to = $value['to']??null) {
                            $source->where("$key <= ? ", $to);
                        }
                        break;
                    default:
                        $source->where([$key => $value]);
                        break;
                }
            }
        }
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->selectable = $this->parent->isSelectable();
        $this->template->columns = $this->parent->getColumns();
        $this->template->search = $this->session->search;
    }

}
