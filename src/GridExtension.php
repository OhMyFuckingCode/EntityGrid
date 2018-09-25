<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 13.07.18
 * Time: 16:41
 */

namespace Quextum\EntityGrid;


use App\Common\Model;
use Nette\Database\Table\Selection;
use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;
use Nette\DI\Container;
use Nette\Utils\Strings;

class GridExtension extends CompilerExtension
{

    const defaults = [
        'sortable' => false,
        'tree' => false,
        'order' => [],
        'factory' => null,
        'searchFactory' => SearchFormFactory::class,
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
        $defaults = $config['_defaults'];
        $builder = $this->getContainerBuilder();
        foreach ($config as $key => $item) {
            if (!Strings::startsWith($key, '_')) {
                $this->validateConfig(static::defaults, $item);
                $config[$key] = Helpers::merge($item,Helpers::merge($defaults,static::defaults));
            }
        }
        $builder->addDefinition($this->prefix('factory'))
            ->setFactory(GridFactory::class, ['config' => $config]);
    }


}

