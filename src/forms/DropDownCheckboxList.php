<?php

namespace Quextum\EntityGrid\Forms;


use Nette\Forms\Container;
use Nette\Forms\Controls\CheckboxList;
use Nette\Utils\Html;

/**
 * Class DropDownCheckboxList
 * @package Quextum\EntityGrid\Forms
 */
class DropDownCheckboxList extends CheckboxList
{

    public static function register(string $fnc): void
    {
        @Container::extensionMethod($fnc, function (Container $container, string $name, ...$args) {
            return $container[$name] = new static(...$args);
        });
    }


    public function getControl()
    {

        $el = Html::el('div', ['class' => 'dropdown']);
        $btn = Html::el('button',
            [
                'id' => $id = $this->getHtmlId() . '-button',
                'class' => 'btn dropdown-toggle',
                'type' => 'button',
                'aria-haspopup' => true,
                'aria-expanded' => false
            ])
            ->data('toggle', 'dropdown')
            ->addText($this->translate($this->caption));
        $el->addHtml($btn);
        $dropDown = Html::el('div', ['class' => 'dropdown-menu', 'aria-labelledby' => $id]);
        $dropDown->addHtml(parent::getControl());
        $el->addHtml($dropDown);
        return $el;
    }

    public function getControlPart($key = null)
    {
        if($key === null){
            return parent::getControl();
        }
        return parent::getControlPart($key);
    }


}
