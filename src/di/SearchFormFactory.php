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
    public function create(BaseGrid $grid, $config, $options):Form
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
            $nextArgs = [];
            $column = $def->getColumn();
            switch ($def->getType()) {
                case 'ajaxselect':
                    $context = $this->context;
                    if ($t = $context->getStructure()->getBelongsToReference( $grid->getSource()->getName(), $column)) {
                        $belongs = $context->table($t);
                        $nextArgs[] = $belongs;
                    } else {
                        $items = (clone $grid->getSource())->select('DISTINCT ?', new SqlLiteral($column));
                        $nextArgs[] = $items;
                    }
                    $nextArgs[] = $def->getValue();
                    $nextArgs[] = $def->getLabel();
                    $def->getImage() && $nextArgs[] = function ($item) use ($config, $def) {
                        return $config['formatter']->image($item, $def);
                    };
                    break;
                case 'select':
                    $context = $this->context;
                    if ($t = $context->getStructure()->getBelongsToReference( $grid->getSource()->getName(), $column)) {
                        $belongs = $context->table($t);
                        $nextArgs[] = $config['formatter']->entities($belongs, $def);
                    } else {
                        $items = (clone $grid->getSource())->select('DISTINCT ?', new SqlLiteral($column));
                        $nextArgs[] = $config['formatter']->items($items, $def);
                    }
                    $nextArgs[] = $def->getValue();
                    $nextArgs[] = $def->getLabel();
                    $def->getImage() && $nextArgs[] = function ($item) use ($config, $def) {
                        return $config['formatter']->image($item, $def);
                    };
                    break;
                case 'checkbox':
                    $nextArgs[] = [
                        null => Html::el('i')->class('fa fa-lg fa-circle text-gray'),
                        true => Html::el('i')->class('fa fa-lg fa-check-circle text-green'),
                        false => Html::el('i')->class('fa fa-lg fa-times-circle text-danger')
                    ];
                    break;
            }
            $this->addInput($form, $def->getMethod(), $column, ...$nextArgs);
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

    protected function addInput(Form $form, $method, string $column, ...$nextArgs): void
    {
        if (\is_array($method)) {
            $cont = $form->addContainer($column);
            $cont->{$method[0]}('from', 'from', ...$nextArgs);
            $cont->{$method[1]}('to', 'to', ...$nextArgs);
        } else {
            $form->$method($column, $column, ...$nextArgs);
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