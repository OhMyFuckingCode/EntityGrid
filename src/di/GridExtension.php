<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 13.07.18
 * Time: 16:41
 */

namespace Quextum\EntityGrid;

use Kdyby\Translation\DI\ITranslationProvider;
use Kdyby\Translation\Dumper\NeonFileDumper;
use Kdyby\Translation\Translator;
use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;
use Nette\Neon\Neon;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpLiteral;
use Nette\Utils\FileSystem;
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
        'groupActions' => [],
        'alias' => [],
    ];

    public static function itemFormatter($items)
    {
        return $items;
    }

    public function loadConfiguration():void
    {
        parent::loadConfiguration();
        $defaults = $this->loadFromFile(__DIR__.'/defaults.neon');
        $config = $this->getConfig();
        $settings = Helpers::merge($config['_settings']??[], $defaults);
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

        $provider = $builder->addDefinition($this->prefix('imageLinkProvider'))
            ->setType(IImageLinkProvider::class)
            ->setFactory(ImageLinkProvider::class);

        $builder->addDefinition($this->prefix('formatter'))
            ->setType(IFormatter::class)
            ->setFactory(Formatter::class, [$provider]);

        $builder->addDefinition($this->prefix('factory'))
            ->setType(IGridFactory::class)
            ->setFactory(GridFactory::class, ['config' => $config, 'settings' => $settings]);
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

