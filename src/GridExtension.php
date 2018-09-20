<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 13.07.18
 * Time: 16:41
 */

namespace App\AdminModule\Controls\EntityGrid;


use App\Common\Model;
use Nette\Database\Table\Selection;
use Nette\DI\CompilerExtension;
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

    public function loadConfiguration()
    {
        parent::loadConfiguration();
        $config = $this->getConfig();
        $builder = $this->getContainerBuilder();
        foreach ($config as $key => $item) {
            if (!Strings::startsWith($key, '_')) {
                $this->validateConfig(static::defaults, $item);
                $config[$key] = array_merge(static::defaults, $item);
            }
        }
        $builder->addDefinition($this->prefix('factory'))
            ->setFactory(GridFactory::class, ['config' => $config]);
    }


}

class GridFactory
{

    /** @var array */
    protected $config;

    /** @var  Container */
    protected $context;

    /**
     * GridFactory constructor.
     * @param array $config
     * @param Container $context
     */
    public function __construct(Container $context, array $config)
    {
        $this->config = $config;
        $this->context = $context;
    }

    /**
     * @param string $key
     * @param Selection $source
     * @param Model|null $model
     * @param string|null $prefix
     * @return EntityGrid
     */
    public function create(string $key, Selection $source, Model $model = null, string $prefix = null)
    {
        $config = $this->config[$key];
        $class = $config['class']??EntityGrid::class;
        $model = $model ?: $this->context->getByType($config['model']);
        //$prefix = $prefix ?: 'forms'.get_class($this->model)::TABLE;
        //$factory = $factory ?: $config['factory']?$this->context->getByType($config['factory'],false):null;
        return new $class($this->config, $config, $model, $source, $prefix);
    }
}