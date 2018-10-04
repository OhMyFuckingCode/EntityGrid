<?php

namespace Quextum\EntityGrid;


use Kdyby\Translation\Translator;
use Nette\Application\UI\Component;
use Nette\Localization\ITranslator;
use Nette\Utils\Html;


/**
 * Class GridRow
 * @package Quextum\EntityGrid
 * @method onClick(Button $button, Action $action)
 */
class Button extends Component
{
    /** @var  callable[] */
    public $onClick;

    /** @var  Action */
    protected $action;

    protected $control;

    /** @var  ITranslator|Translator */
    protected $translator;

    /**
     * Button constructor.
     * @param Action $action
     */
    public function __construct(Action $action)
    {
        parent::__construct();
        $this->control = Html::el('a');
        $this->action = $action;
        $this->monitor(BaseControl::class, [$this, 'onAttached']);
    }

    public function onAttached(BaseControl $grid): void
    {
        $this->setTranslator($grid->getTranslator());
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

    public function getControl():Html
    {
        $control = clone $this->control;
        $action = $this->action;
        //Link
        $link = $action->getLink($this) ?: $this->link('Click!');
        $control->href($link);
        //Classes
        $class = $action->getClass();
        $class && $control->addClass($class);
        $action->isAjax() && $control->addClass('ajax');
        //Data
        foreach ($action->getData() as $d => $v) {
            $control->data($d, $v);
        }
        // Confirm
        $confirm = $action->getConfirm();
        $confirm && $control->data('confirm', $this->translate($confirm));
        // Title
        $title = $action->getTitle();
        $title && $control->title($this->translate($title));
        //Off
        foreach ($action->getOff() as $off) {
            $control->data('naja-' . $off, 'off');
        }
        //Label, Icon
        $icon = $action->getIcon();
        $label = $action->getLabel();

        $icon && $control->addHtml(Html::el('i')->class($icon)->addClass($label ? 'mr-2' : null));
        $label && $control->addText($this->translate($label));

        return $control;
    }

    private function translate($key)
    {
        return $this->translator ? $this->translator->translate($key) : $key;
    }

    /**
     * @param Translator|ITranslator $translator
     * @return static
     */
    public function setTranslator($translator)
    {
        if ($translator instanceof Translator) {
            $translator = $translator->domain('entityGrid.btn');
        }
        $this->translator = $translator;
        return $this;
    }

    public function render():void
    {
        echo (string)$this->getControl();
    }

}
