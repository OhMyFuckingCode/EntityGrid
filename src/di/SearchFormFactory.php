<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 24.09.18
 * Time: 15:28
 */

namespace Quextum\EntityGrid;


use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette\Database\SqlLiteral;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;

class SearchFormFactory implements ISearchFormFactory
{


    /** @var  Context */
    protected $context;

    /**
     * SearchFormFactory constructor.
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }


    /**
     * @param BaseGrid $grid
     * @param array $config
     * @param array $options
     * @return Form
     */
    public function create(BaseGrid $grid,$config,$options):Form
    {
        $form = new Form();

        /** @var SearchDefinition[] $search */
        $search = $config['search'];
        /**
         * creates all inputs
         * @var $column
         * @var $type
         */
        foreach ($search as $_column => $def) {
            $values = null;
            $column = $def->getColumn();
            $method = $def->getMethod();
            switch ($def->getType()) {
                case 'select':
                case 'multiselect':
                    $model = $grid->getModel();
                    $context = $model->getContext();
                    if ($t = $context->getStructure()->getBelongsToReference($model->getTableName(), $column)) {
                        $belongs = $context->table($t);
                        $values = $config['formatter']->entities($belongs, $def);
                    } else {
                        $items = (clone $grid->getSource())->select('DISTINCT ?', new SqlLiteral($column));
                        $values = $config['formatter']->items($items, $def);
                    }
                    break;
                case 'checkbox':
                    $values = [
                        null => Html::el('i')->class('fa fa-lg fa-circle text-gray'),
                        true => Html::el('i')->class('fa fa-lg fa-check-circle text-green'),
                        false => Html::el('i')->class('fa fa-lg fa-times-circle text-danger')
                    ];
                    break;
            }
            $this->addInput($form, $method, $column, $values);
        }
        $options = $options['options'];
        /** if it is bootstrap, setup classes */
        if ($options && $options['bootstrap']) {
            foreach ($form->getControls() as $control) {
                $control->setAttribute('class', 'form-control');
            }
        }
        return $form;
    }

    protected function addInput(Form $form, $method, string $column, $values = null): void
    {
        if (\is_array($method)) {
            $cont = $form->addContainer($column);
            $cont->{$method[0]}(...array_filter(['from', 'from', $values]));
            $cont->{$method[1]}(...array_filter(['to', 'to', $values]));
        } else {
            $form->$method(...array_filter([$column, $column, $values]));
        }
    }

    /**
     * @param Selection $selection
     * @param ArrayHash $values
     */
    public function apply(Selection $selection, $values)
    {
        if ($values) {
            foreach ($values as $key => $value) {
                $type = $this->config['search'][$key]??null;
                switch ($type) {
                    case 'regexp':
                        $selection->where("$key REGEXP ?", $value);
                        break;
                    case 'match':
                        $selection->where(" MATCH ($key) AGAINST ?", $value);
                        break;
                    case 'like':
                        $selection->where("$key LIKE ?", "%$value%");
                        break;
                    case 'range':
                    case 'timerange':
                    case 'daterange':
                    case 'datetimerange':
                        !empty($value['from']) && $selection->where("$key >= ? ", $value['from']);
                        !empty($value['to']) && $selection->where("$key <= ? ", $value['to']);
                        break;
                    default:
                        $selection->where([$key => $value]);
                        break;
                }
            }
        }
    }
}