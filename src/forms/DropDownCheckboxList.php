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


    public function getControl():Html
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
        $dropDown = Html::el('div', ['class' => 'dropdown-menu dropdown-checkbox-list  x-hidden', 'aria-labelledby' => $id]);

        $items = $this->getItems();
        reset($items);
        $items = $this->translate($items);

        foreach ($items as $key=>$option) {
            $item = Html::el('div')->class('custom-control custom-checkbox d-contents');
            $item->addHtml($this->getControlPart($key)->class('custom-control-input'));
            $item->addHtml($this->getLabelPart($key)->class('dropdown-item custom-control-label'));
            //bdump($option,$key);
            $dropDown->addHtml($item);
        }



        $el->addHtml($dropDown);
        return $el;
    }

    public function getControlPart($key = null):Html
    {
        if ($key === null) {
            return parent::getControl();
        }
        return parent::getControlPart($key);
    }


}
