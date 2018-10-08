<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 13.07.18
 * Time: 16:41
 */

namespace Quextum\EntityGrid;

use Kdyby\Translation\DI\ITranslationProvider;
use Kdyby\Translation\Translator;
use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpLiteral;
use Quextum\EntityGrid\Forms\AjaxSelectBox;
use Quextum\EntityGrid\Forms\DropDownCheckboxList;

class GridExtension extends CompilerExtension implements ITranslationProvider
{

    public const defaultGridConfig = [
        'sortable' => false,
        'tree' => false,
        'order' => [],
        'factory' => null,
        'searchFactory' => '@Quextum\EntityGrid\SearchFormFactory',
        'model' => null,
        'detail' => 'detail',
        'columns' => [],
        'template' => 'grid.latte',
        'search' => [],
        'actions' => [],
        'link' => ['id', 'name'],
        'globalActions' => [],
        'groupActions' => []
    ];

    public const defaultSettings = [
        'defaults' => [
            'formatter' => '@grid.formatter'
        ],
        'options' => [
            'bootstrap' => 4
        ],
        'actions' => [
            'link' => [
                'ajax' => false,
                'icon' => 'fas fa-link',
                'class' => [
                    'btn',
                    'btn-sm',
                    'btn-success',
                ],
                'title' => 'link',
                'link' => [
                    ':Front:Entity:detail',
                    [
                        'url',
                    ],
                ],
                'off' => [],
            ],
            'create' => [
                'class' => [
                    'btn',
                    'btn-light',
                    'text-muted',
                ],
                'link' => ':add',
                'icon' => 'fa fa-plus text-success',
                'off' => [],
                'label' => 'create',
                'title' => 'create',
            ],
            'edit' => [
                'icon' => 'fa fa-edit',
                'class' => [
                    'btn',
                    'btn-sm',
                    'btn-light'
                ],
                'title' => 'edit-in-row',
            ],
            'delete' => [
                'icon' => 'fa fa-trash',
                'addClass' => [
                    'btn-danger'
                ],
                'title' => 'delete',
                'confirm' => '//forms.confirm.delete',
            ],
            'groupDelete'=>[
                'icon' => 'fa fa-trash',
                'class' => [
                    'btn',
                    'btn-danger'
                ],
                'label' => 'delete',
                'title' => 'delete',
                'confirm' => '//forms.confirm.delete',
            ],
            'detail' => [
                'icon' => 'fas fa-pen',
                'class' => [
                    'btn',
                    'btn-sm',
                    'btn-primary'
                ],
                'title' => 'detail',
                'link' => [
                    ':detail', ['id'],
                ],
                'off' => [],
            ],
        ],
        'inputs' => [
            'checkbox' => 'addRadioList',
            'like' => 'addText',
            'match' => 'addText',
            'regexp' => 'addText',
            'text' => 'addText',
            'article' => 'addTextArea',
            'datetime' => 'addDateTime',
            'date' => 'addDate',
            'time' => 'addTime',
            'ajaxselect' => 'addAjaxSelectBox',
            'select' => 'addMultiselect',
            'int' => 'addInteger',
            'range' => [
                'addInteger',
                'addInteger'
            ],
            'datetimerange' => [
                'addDateTimeOptional',
                'addDateTimeOptional'
            ],
            'daterange' => [
                'addDate',
                'addDate'
            ],
            'timerange' => [
                'addTime',
                'addTime'
            ]
        ],
        'search' => NULL
    ];

    public static function itemFormatter($items)
    {
        return $items;
    }

    public function loadConfiguration():void
    {
        parent::loadConfiguration();
        $config = $this->getConfig();
        $settings = Helpers::merge($config['_settings']??[], static::defaultSettings);
        $defaults = $settings['defaults']??[];
        unset($config['_settings']);
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('searchFactory'))
            ->setFactory(SearchFormFactory::class);
        foreach ($config as $key => $item) {
            $this->validateConfig(static::defaultGridConfig, $item);
            $c = $config[$key] = Helpers::merge($item, Helpers::merge($defaults, static::defaultGridConfig));
            if (isset($c['search'])) {
                foreach ($c['search'] as $name => $def) {
                    $config[$key]['search'][$name] = $def = new SearchDefinition($name, $def, $settings['inputs']);

                }
            }

        }
        $builder->addDefinition($this->prefix('factory'))
            ->setType(IGridFactory::class)
            ->setFactory(GridFactory::class, ['config' => $config, 'settings' => $settings]);

        $builder->addDefinition($this->prefix('formatter'))
            ->setType(IFormatter::class)
            ->setFactory(Formatter::class, [$settings['imageLink']]);


    }

    public function beforeCompile()
    {
        parent::beforeCompile();
        $builder = $this->getContainerBuilder();
        $builder->getDefinitionByType(Translator::class);

    }


    public function afterCompile(ClassType $classType):void
    {
        $init = $classType->getMethod('initialize');
        foreach ([
                     AjaxSelectBox::class => 'addAjaxSelectBox',
                     DropDownCheckboxList::class => 'addDropDownCheckBoxList'
                 ] as $class => $method) {
            $init->addBody('?::register(?);', [new PhpLiteral($class), $method]);
        }
    }

    /**
     * Return array of directories, that contain resources for translator.
     *
     * @return string[]
     */
    public function getTranslationResources():array
    {
        return [__DIR__ . '/../lang'];
    }
}

