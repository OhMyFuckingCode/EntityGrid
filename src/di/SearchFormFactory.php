<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 24.09.18
 * Time: 15:28
 */

namespace Quextum\EntityGrid;


use App\Utils\Date;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette\Database\SqlLiteral;
use Nette\Database\Table\Selection;
use Nette\Forms\Controls\ChoiceControl;
use Nette\Forms\Controls\MultiChoiceControl;
use Nette\Forms\IControl;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Nette\Utils\Strings;

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
            preg_match('/(:?(?<table>\w+)(:(?<over>\w+))?\.)?(?<column>\w+)/', $column, $m);
            list('table' => $table, 'over' => $over, 'column' => $column) = $m;
            switch ($def->getType()) {
                case 'ajaxselect':
                    $context = $this->context;
                    if ($table || ($table = $context->getStructure()->getBelongsToReference($grid->getSource()->getName())[$column]??null)) {
                        $belongs = $context->table($table);
                        if ($over) {
                            $source = $grid->getSource();
                            $belongs->joinWhere($over, [$column => "{$source->getName()}.{$source->getPrimary()}"]);
                        }
                        $nextArgs[] = $belongs;
                    } else {
                        $items = $grid->getSource()->createSelectionInstance()->select('DISTINCT ?', new SqlLiteral($column));
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
                    if ($table || ($table = $context->getStructure()->getBelongsToReference($grid->getSource()->getName(), $column))) {
                        $belongs = $context->table($table);
                        $nextArgs[] = $config['formatter']->entities($belongs, $def);
                    } else {
                        $items = $grid->getSource()->createSelectionInstance()->select('DISTINCT ?', new SqlLiteral($column));
                        $nextArgs[] = $config['formatter']->items($items, $def);
                    }
                  //  $nextArgs[] = $def->getValue();
                   // $nextArgs[] = $def->getLabel();
                    $def->getImage() && $nextArgs[] = static function ($item) use ($config, $def) {
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
            $input = $this->addInput($form, $def->getMethod(), $column, ...$nextArgs);

        }
        $options = $options['options'];
        /** if it is bootstrap, setup classes */
        if ($options && $options['bootstrap']) {
            foreach ($form->getControls() as $control) {
                $control->setAttribute('class', 'form-control');
            }
        }
        foreach ($form->getControls() as $control) {
            switch (true){
                case $control instanceof MultiChoiceControl:
                    $control->setHtmlAttribute('size',1);
                case $control instanceof ChoiceControl:
                    $control->checkDefaultValue(false);
            }
        }
        $form->onError[] = static function (Form $form) {
            bdump($form->getErrors());
        };

        return $form;
    }

    /**
     * @param Form $form
     * @param $method
     * @param string $column
     * @param array ...$nextArgs
     * @return \Nette\Forms\Container|IControl
     */
    protected function addInput(Form $form, $method, string $column, ...$nextArgs)
    {

        if (\is_array($method)) {
            $cont = $form->addContainer($column);
            $cont->{$method[0]}('from', 'from', ...$nextArgs);
            $cont->{$method[1]}('to', 'to', ...$nextArgs);
            return $cont;
        }
        return $form->$method(Strings::after($column, '.') ?: $column, $column, ...$nextArgs);
    }

    /**
     * @param BaseGrid $grid
     * @param Selection $selection
     * @param array $config
     * @param ArrayHash $values
     */
    public function apply(BaseGrid $grid, Selection $selection, $config, $values):void
    {
        if ($values) {

            foreach ($values as $key => $value) {
                $def = $config['search'][$key];
                $column = (string)$grid->getColumns()[$key];

                /* $column = $def->getColumn();
                 preg_match('/(:?(?<table>\w+)(:(?<over>\w+))?\.)?(?<column>\w+)/', $column, $m);
                 list('table' => $table, 'over' => $over, 'column' => $col) = $m;

                 if (!$table && !$over) {
                     $column = $selection->getName() . '.' . $column;
                 }*/

                if ($value === null || $value === 'null') {
                    $selection->where([$column => null]);
                    continue;
                }
                if ($value === 'not null') {
                    $selection->where(["$column IS NOT NULL"]);
                    continue;
                }

                // Search napříč joined table
                /*if (empty($config['search'][$key])) {
                    foreach ($config['columns'] as $s_key => $type) {
                        if (strpos($s_key, ".$key") !== false) {
                            $key = $s_key;
                        }
                    }
                }*/

                switch ($def->getType()) {
                    case 'regexp':
                        $selection->where("$column REGEXP ?", $value);
                        break;
                    case 'match':
                        $selection->where(" MATCH ($column) AGAINST (?)", $value);
                        break;
                    case 'like':
                        $selection->where("$column LIKE ?", "%$value%");
                        break;
                    case 'range':
                    case 'timerange':
                    case 'daterange':
                    case 'datetimerange':
                        if (!empty($value['from'])) {
                            if ($value['from'] instanceof Date) {
                                $column = "DATE($column)";
                            }
                            if (empty($value['to'])) {
                                $selection->where("$column = ? ", $value['from']);
                            } else {
                                $selection->where("$column >= ? ", $value['from']);
                            }
                        }
                        if (!empty($value['to'])) {
                            if ($value['to'] instanceof Date) {
                                $column = "DATE($column)";
                            }
                            $selection->where("$column <= ? ", $value['to']);
                        }
                        break;
                    default:
                        $selection->where([$column => $value]);
                        break;
                }
            }
        }
    }
}