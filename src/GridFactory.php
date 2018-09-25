<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 24.09.18
 * Time: 16:09
 */

namespace Quextum\EntityGrid;


use App\Common\Model;
use Nette\Database\Table\Selection;
use Nette\DI\Container;

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