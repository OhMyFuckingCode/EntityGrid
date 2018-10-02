<?php

namespace Quextum\EntityGrid\Forms;

use Nette\Application\UI\ISignalReceiver;
use Nette\Application\UI\Presenter;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Forms\Container;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Utils\Html;

/**
 * Class DropDownCheckboxList
 * @package Quextum\EntityGrid\Forms
 * @method onFilter(AjaxSelectBox $input, Selection $selection, ?string $search)
 */
class AjaxSelectBox extends MultiSelectBox implements ISignalReceiver
{

    /** @var  callable[] */
    public $onFilter;

    const LOAD_SIGNAL = 'load';

    /** @var  string */
    protected $valueField;

    /** @var  string */
    protected $labelField;

    /** @var  string[] */
    protected $searchFields;

    /** @var  callable|null */
    protected $imageCallback;

    /** @var  int */
    protected $limit = 50;

    /** @var Selection */
    protected $selection;

    /** @var  array */
    protected $jsOptions = [];

    public function __construct(string $label, Selection $items, string $valueField, string $labelFiled, ?callable $imageCallback = null, ?array $searchFields = null)
    {
        parent::__construct($label, []);
        $this->controlPrototype->data('provide', 'select');
        $this->selection = $items;
        $this->valueField = $valueField;
        $this->labelField = $labelFiled;
        $this->imageCallback = $imageCallback;
        $this->searchFields = $searchFields ?? [$valueField, $labelFiled];
        $this->onFilter = [function (AjaxSelectBox $box, Selection $selection, ?string $search) {
            if ($search) {
                $val = "%$search%";
                $x = [];
                foreach ($box->getSearchFields() as $item) {
                    $x["$item LIKE ?"] = $val;
                }
                $selection->whereOr($x);
            }
        }];
    }

    public function setPickerOption(string $key, $value)
    {
        $this->jsOptions[$key] = $value;
    }

    public static function register(string $fnc): void
    {
        @Container::extensionMethod($fnc, function (Container $container, string $name, ...$args) {
            return $container[$name] = new static(...$args);
        });
    }

    public function getControl():Html
    {
        $control = parent::getControl();
        /*$control->data('selectr',[

        ]);*/
        /** @var Presenter $presenter */
        $presenter = $this->lookup(Presenter::class);
        $do = $this->lookupPath(Presenter::class) . self::NAME_SEPARATOR . static::LOAD_SIGNAL;
        $control->data('select', array_filter(array_merge([
            'searchHighlight' => true,
            'ajax' => $presenter->link('this', [Presenter::SIGNAL_KEY => $do]),
            'placeholderText' => $this->translate($control->placeholder??'//entityGrid.select.placeholder'),
            'searchPlaceholder' => $this->translate('//entityGrid.search'),
            'searchText' => $this->translate('//entityGrid.no-results'),
        ], $this->jsOptions), function ($val) {
            return $val !== null;
        }));

        return $control;
    }

    /**
     * @return string
     */
    public function getValueField(): string
    {
        return $this->valueField;
    }

    /**
     * @param string $valueField
     * @return AjaxSelectBox
     */
    public function setValueField(string $valueField): AjaxSelectBox
    {
        $this->valueField = $valueField;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelField(): string
    {
        return $this->labelField;
    }

    /**
     * @param string $labelField
     * @return AjaxSelectBox
     */
    public function setLabelField(string $labelField): AjaxSelectBox
    {
        $this->labelField = $labelField;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getImageCallback():?callable
    {
        return $this->imageCallback;
    }

    /**
     * @param callable|null $imageCallback
     * @return AjaxSelectBox
     */
    public function setImageCallback(?callable $imageCallback = null): AjaxSelectBox
    {
        $this->imageCallback = $imageCallback;
        return $this;
    }

    /**
     * @return Selection
     */
    public function getSelection(): Selection
    {
        return $this->selection;
    }

    /**
     * @param Selection $selection
     * @return AjaxSelectBox
     */
    public function setSelection(Selection $selection): AjaxSelectBox
    {
        $this->selection = $selection;
        return $this;
    }

    /**
     * @return callable|\string[]
     */
    public function getSearchFields()
    {
        return $this->searchFields;
    }

    /**
     * @param string[] $searchFields
     * @return AjaxSelectBox
     */
    public function setSearchFields(array $searchFields): AjaxSelectBox
    {
        $this->searchFields = $searchFields;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return AjaxSelectBox
     */
    public function setLimit(int $limit): AjaxSelectBox
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param  string
     * @return void
     */
    public function signalReceived($signal):void
    {
        if ($signal === static::LOAD_SIGNAL) {
            /** @var Presenter $presenter */
            $presenter = $this->lookup(Presenter::class);
            $request = $presenter->getHttpRequest();
            $response = $this->loadData($request->getQuery('search'));
            $presenter->sendJson($response);
        }
    }


    protected function loadData(?string $search = null)
    {
        $selection = clone $this->selection;
        $this->onFilter($this, $selection, $search);
        $response = [];
        foreach ($selection->limit($this->limit) as $item) {
            /** @var ActiveRow $item */
            $val = [
                'value' => $value = $this->valueField ? $item->{$this->valueField} : $item->getPrimary(),
                'text' => $text = ($this->labelField ? $item->{$this->labelField} : $item->label??$item->title??$item->name??null) . '#' . $value
            ];
            if ($this->imageCallback) {
                $val['innerHTML'] = (string)Html::el()
                    ->addHtml(($this->imageCallback)($item))
                    ->addHtml('&nbsp;')
                    ->addText($text);
            }
            $response[$value] = $val;
        }
        return $response;
    }
}
