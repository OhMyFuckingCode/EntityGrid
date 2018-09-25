<?php

namespace Quextum\EntityGrid;

use App\Common\Controls\BaseControl;

/**
 * Class GridRow
 * @package Quextum\EntityGrid
 * @method onClick(Button $button, Action $action)
 */
class Button extends BaseControl
{
    /** @var  callable[] */
    public $onClick;

    /** @var  Action */
    protected $action;

    /**
     * Button constructor.
     * @param Action $action
     */
    public function __construct(Action $action)
    {
        parent::__construct();
        $this->templateName = 'templates/button.latte';
        $this->action = $action;
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->action = $this->action;
    }


    public function handleClick()
    {
        $this->onClick($this, $this->action);
    }

    /**
     * @return Action
     */
    public function getAction(): Action
    {
        return $this->action;
    }

    /**
     * @param Action $action
     */
    public function setAction(Action $action)
    {
        $this->action = $action;
    }


    public function getRow()
    {
        return $this->lookup(Row::class);
    }

}
