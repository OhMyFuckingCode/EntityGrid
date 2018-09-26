<?php

namespace Quextum\EntityGrid;


//use Nette\Application\UI\Component;
use Nette\Utils\Html;


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

    protected $control;

    /**
     * Button constructor.
     * @param Action $action
     */
    public function __construct(Action $action)
    {
        parent::__construct();
        $this->control = Html::el('a');
        $this->templateName = 'button.latte';
        $this->action = $action;
    }

    protected function beforeRender():void
    {
        parent::beforeRender();
        $this->view = null;
        $this->template->action = $this->action;
        $this->template->setTranslator($this->translator->domain('entityGrid.btn'));
    }


    public function handleClick():void
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
     * @return static
     */
    public function setAction(Action $action)
    {
        $this->action = $action;
        return $this;
    }


    public function getRow()
    {
        return $this->lookup(Row::class);
    }

    /*public function getControl():Html
      {
          $control = clone $this->control;
          $action = $this->action;
          $link = $action->getLink($this->getParent())?:$this->link('Click!');
          $control->href($link);
          $label = $action->getLabel();
          $control->addClass($action->getClass());
          $action->isAjax() && $control->addClass('ajax');
          $confirm = $action->getConfirm();
          $confirm && $control->data('confirm',$confirm);

          $title =  $action->getTitle();
          $title && $control->title($title);

          foreach ($action->getOff() as $off) {
              $control->data('naja-'.$off,'off');
          }
          $icon = $action->getIcon();
          $icon && $control->addHtml(Html::el('i')->class($icon)->addClass($label?'mr-2':null));
          $control->addText($label);

          return $control;
      }

      public function render()
      {
          echo (string)$this->getControl();
      }
  */
}
