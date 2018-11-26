<?php

namespace Quextum\EntityGrid;

use Nette\Application\UI\Multiplier;


/**
 * Class TActions
 * @package Quextum\EntityGrid
 */
trait TActions
{

    protected static $ACTION_TYPES = [
        'globalActions', 'groupActions', 'actions'
    ];

    /** @var  Action[] */
    protected $globalActions = [];

    /** @var  Action[] */
    protected $groupActions = [];

    /** @var  Action[] */
    protected $actions = [];

    protected function initTraitActions(): void
    {
        $this->onPresenterAttached[] = function () {
            /** @var BaseGrid $grid */
            $grid = $this->lookup(BaseGrid::class, false);
            if ($grid) {
                $this->globalActions = $grid->getGlobalActions();
                $this->groupActions = $grid->getGroupActions();
                $this->actions = $grid->getActions();
            }
        };
        $this->onBeforeRender[] = function () {
            $this->template->actions = $this->actions;
            $this->template->groupActions = $this->groupActions;
            $this->template->globalActions = $this->globalActions;
        };
    }

    /**
     * @return boolean
     */
    public function isSelectable(): bool
    {
        return (bool)$this->groupActions;
    }

    protected function createComponentAction():Multiplier
    {
        return new Multiplier(function ($action) {
            return new Button($this->actions[$action]);
        });
    }

    protected function createComponentGlobalAction():Multiplier
    {
        return new Multiplier(function ($action) {
            return new Button($this->globalActions[$action]);
        });
    }

    protected function createComponentGroupAction():Multiplier
    {
        return new Multiplier(function ($action) {
            return new Button($this->groupActions[$action]);
        });
    }

    protected function _addAction(string $type, string $name, callable $callback = null): Action
    {
        $action = $this->$type[$name] = new Action();
        if ($callback) {
            $action->onClick[] = $callback;
        }
        return $action;
    }

    /**
     * @return Action[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @return Action[]
     */
    public function getGlobalActions(): array
    {
        return $this->globalActions;
    }

    /**
     * @return Action[]
     */
    public function getGroupActions(): array
    {
        return $this->groupActions;
    }

    public function addActions(array $config, array $defaults)
    {
        foreach (static::$ACTION_TYPES as $type) {
            foreach ($config[$type] as $column => $callback) {
                $action = null;
                if (\is_numeric($column)) {
                    $column = $callback;
                    $action = $this->_addAction($type, $column, [$this, $callback]);
                } elseif ($callback === true) {
                    $action = $this->_addAction($type, $column, [$this, $column]);
                } elseif (\is_callable($callback)) {
                    $action = $this->_addAction($type, $column, $callback);
                } elseif (\is_array($callback)) {
                    $this->_addAction($type, $column)->setArgs(array_merge($defaults[$column]??[],$callback));
                    continue;
                }
                self::applyDefaults($action, $defaults[$column]??[]);
            }
        }
    }

    private static function applyDefaults(Action $action, array $defaults)
    {
        foreach ($defaults as $prop => $value) {
            $method = (strpos($prop, 'set') === false && strpos($prop, 'add') === false) ? 'set' . ucfirst($prop) : $prop;
            $action->$method($value);
        }
    }

}
