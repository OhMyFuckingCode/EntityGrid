<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 27.09.18
 * Time: 14:26
 */

namespace Quextum\EntityGrid;

use Brabijan\Images\Image;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Html;

class Formatter implements IFormatter
{

    /** @var  IImageLinkProvider */
    protected $provider;

    /**
     * Formatter constructor.
     * @param IImageLinkProvider $provider
     */
    public function __construct(IImageLinkProvider $provider)
    {
        $this->provider = $provider;
    }


    public function item(ActiveRow $item, SearchDefinition $column)
    {
        $label = $column->getLabel();
        $value = $column->getValue();
        if ($label) {
            $text = $item->$label ?: $item->$value;
        }elseif($id = $item->getPrimary(false) ){
            $text = '#'.$id;
        }else{
            $text = array_values($item->toArray())[0];
        }
        return Html::el()->setText($text);
    }

    public function entity(ActiveRow $item, SearchDefinition $column)
    {
        $label = $column->getLabel();
        $value = $column->getValue();
        $el = Html::el('div');
        if ($img = $this->image($item, $column)) {
            $el->addHtml($img)->data('image', $img);
        }
        if ($label) {
            $el->addText($item->$label ?: $item->$value);
        }
        return $el;
    }

    public function entities(Selection $items, SearchDefinition $column)
    {

        $ret = [];
        $id = $column->getValue();
        foreach ($items as $item) {
            $ret[$item->$id] = $this->entity($item, $column);
        }
        return $ret;

    }

    public function items(Selection $items, SearchDefinition $column)
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
        if ($image = $column->getImage()) {
            return Html::el('img')->setAttribute('src',$this->provider->provide($image,30,30,\Nette\Utils\Image::EXACT));
        }
        return null;
    }
}