<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 24.09.18
 * Time: 16:09
 */

namespace Quextum\EntityGrid;

use Nette\Database\Table\Selection;
use Nette\DI\Container;

class GridFactory implements IGridFactory
{

    /**
     * Configuration of grids
     * @var array
     */
    protected $config;

    /**
     * Global configuration and definitions
     * @var array
     */
    protected $settings;

    /** @var  Container */
    protected $context;

    /**
     * GridFactory constructor.
     * @param Container $context
     * @param array $config
     * @param array $settings
     */
    public function __construct(Container $context, array $config, array $settings)
    {
        $this->context = $context;
        $this->config = $config;
        $this->settings = $settings;
    }

    /**
     * @param string $key
     * @param Selection $source
     * @param IModel|null $model
     * @param string|null $prefix
     * @return EntityGrid
     */
    public function create(string $key, Selection $source, IModel $model = null, string $prefix = null)
    {
        $config = $this->config[$key];
        $class = $config['class']??EntityGrid::class;
        $model = $model ?: $this->context->getByType($config['model']);
        //$prefix = $prefix ?: 'forms'.get_class($this->model)::TABLE;
        //$factory = $factory ?: $config['factory']?$this->context->getByType($config['factory'],false):null;
        return new $class($this->settings, $config, $model, $source, $prefix);
    }
}