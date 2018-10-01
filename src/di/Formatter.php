<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 27.09.18
 * Time: 14:26
 */

namespace Quextum\EntityGrid;

use Nette\Utils\Html;

class Formatter implements IFormatter
{

    /** @var  callable */
    protected $imageLinkFactory;

    /** @var  array */
    protected $imageLinkParams;

    /**
     * Formatter constructor.
     * @param array $imageLinkFactory
     */
    public function __construct(array $imageLinkFactory)
    {
        $this->imageLinkFactory = \array_slice($imageLinkFactory, 0, 2);
        $this->imageLinkParams = \array_slice($imageLinkFactory, 2);
    }


    public function item($item, SearchDefinition $column)
    {
        $label = $column->getLabel();
        $value = $column->getValue();
        $el = Html::el('div');
        if ($img = $this->image($item, $column)) {
            $el->addHtml($img);
        }
        if ($label) {
            $el->addText($item->$label?:$item->$value);
        }
        return $el;
    }

    public function entity($item, SearchDefinition $column)
    {
        $label = $column->getLabel();
        $value = $column->getValue();
        $el = Html::el('div');
        if ($img = $this->image($item, $column)) {
            $el->addHtml($img)->data('image',$img);
        }
        if ($label) {
            $el->addText($item->$label?:$item->$value);
        }
        return $el;
    }

    public function entities(\Traversable $items, SearchDefinition $column)
    {

        $ret = [];
        $id = $column->getValue();
        foreach ($items as $item) {
            $ret[$item->$id] = $this->entity($item, $column);
        }
        return $ret;

    }

    public function items(\Traversable $items, SearchDefinition $column)
    {
        $ret = [];
        $id = $column->getColumn();
        foreach ($items as $item) {
            $ret[$item->$id] = $this->item($item, $column);
        }
        return $ret;

    }

    public function image($item, SearchDefinition $column)
    {
        $image = $column->getImage();
        if ($image) {
            $img = Html::el('img');
            if ($image === true) {
                $img->src = ($this->imageLinkFactory)($item,...$this->imageLinkParams);
            } else {
                $img->src = ($this->imageLinkFactory)($item->$image,...$this->imageLinkParams);
            }
            return $img;
        }
        return null;
    }
}