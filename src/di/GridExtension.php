<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 13.07.18
 * Time: 16:41
 */

namespace Quextum\EntityGrid;

use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;
use Nette\Utils\Strings;

class GridExtension extends CompilerExtension
{

    public const defaults = [
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

    public static function itemFormatter($items)
    {
        return $items;
    }

    public function loadConfiguration()
    {
        parent::loadConfiguration();
        $config = $this->getConfig();
        $settings = $config['_settings']??[];
        $defaults = $settings['defaults']??[];
        unset($config['_settings']);
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('searchFactory'))
            ->setFactory(SearchFormFactory::class);
        foreach ($config as $key => $item) {
            if (!Strings::startsWith($key, '_')) {
                $this->validateConfig(static::defaults, $item);
                $config[$key] = Helpers::merge($item,Helpers::merge($defaults,static::defaults));
            }
        }
        $builder->addDefinition($this->prefix('factory'))
            ->setType(IGridFactory::class)
            ->setFactory(GridFactory::class, ['config' => $config,'settings'=>$settings]);
    }


}

