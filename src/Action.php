<?php

namespace Quextum\EntityGrid;

use Nette\Application\UI\Component;
use Nette\Application\UI\Link;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\SmartObject;
use Nette\Utils\Callback;

/**
 * Class GridRow
 * @package Quextum\EntityGrid
 * @method onClick(Section $section,?ActiveRow $item = null)
 */
class Action
{

    use SmartObject;

    const DEFAULT_CLASSES = ['btn', 'btn-secondary'];

    /** @var  callable[] */
    public $onClick;

    /** @var  string|null */
    protected $icon;

    /** @var  string|null */
    protected $label;

    /** @var  string|null */
    protected $title;

    /** @var  string[] */
    protected $class = self::DEFAULT_CLASSES;

    /** @var  string[] */
    protected $data = [];

    /** @var  Link */
    protected $link;

    /** @var bool */
    protected $renderable = true;

    /** @var  bool */
    protected $escape = true;

    /** @var  string|null */
    protected $confirm;

    /** @var string[] */
    protected $off = ['history'];

    /** @var array  */
    protected $params = [];

    /** @var bool  */
    protected $ajax = true;

    /** @var  string|null */
    protected $callback;

    /** @var  array */
    protected $attrs;

    /**
     * Button constructor.
     * @param string $icon
     * @param string $label
     * @param string $title
     * @param Link $link
     */
    public function __construct($label = null, $icon = null, $title = null, Link $link = null)
    {
        $this->icon = $icon;
        $this->label = $label;
        $this->title = $title;
        $this->link = $link;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return Action
     */
    public function setIcon(string $icon): Action
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return Action
     */
    public function setLabel(string $label): Action
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Action
     */
    public function setTitle(string $title): Action
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param Component|BaseControl $component
     * @return Link|string
     */
    public function getLink(Component $component)
    {
        if (!$this->link) {
            return null;
        }
        if ($this->link instanceof Link) {
            return $this->link;
        }
        /** @var Row $row */
        $row = $component->lookup(Row::class,false);
        if($row){
            /** @var ActiveRow $item */
            $item = $row->getItem();
        }
        $params = null;
        if (\is_array($this->link)) {
            list($destination, $params) = $this->link;
            isset($item) && $params = array_intersect_key($item->toArray(), array_flip($params));
        }
        if (\is_string($this->link)) {
            $destination = $this->link;
            //$params = null;
        }
        if (strpos($destination, ':') === 0) {
            $component = $component->getPresenter();
        }
        foreach ($this->params as $key => $value) {
            if (is_numeric($key) && isset($item)) {
                unset($this->params[$key]);
                $this->params[$value] = $item->$value;
            } else {
                $this->params[$key] = $value;
            }
        }

        return new Link($component, $destination, array_merge((array)$params, $this->params));
    }

    /**
     * @param Link|string|array $link
     * @return Action
     */
    public function setLink($link): Action
    {
        $this->link = $link;
        return $this;
    }

    /**
     * @return \string[]
     */
    public function getClass(): array
    {
        return $this->class;
    }

    /**
     * @return \string[]
     */
    public function getData(): array
    {
        return $this->data;
    }
    /**
     * @param \string[] $class
     * @return Action
     */
    public function setClass(array $class): Action
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @param \string[] $class
     * @return Action
     */
    public function addClass(array $class): Action
    {
        $this->class = array_merge($this->class, $class);
        return $this;
    }

    /**
     * @param \string[] $data
     * @return Action
     */
    public function addData(array $data): Action
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    /**
     * @return boolean
     */
    public function isRenderable(): bool
    {
        return $this->renderable;
    }

    /**
     * @param boolean $renderable
     * @return Action
     */
    public function setRenderable(bool $renderable): Action
    {
        $this->renderable = $renderable;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEscaped(): bool
    {
        return $this->escape;
    }

    public function setArgs(array $args)
    {
        foreach ($args as $prop => $arg) {
            $this->{'set'.ucfirst($prop)}($arg);
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConfirm(): ?string
    {
        return $this->confirm;
    }

    /**
     * @param string|null $confirm
     * @return static
     */
    public function setConfirm(string $confirm = null)
    {
        $this->confirm = $confirm;
        return $this;
    }

    /**
     * @return \string[]
     */
    public function getOff(): array
    {
        return $this->off;
    }

    /**
     * @param \string[] $off
     * @return static
     */
    public function setOff(array $off)
    {
        $this->off = $off;
        return $this;
    }

    /**
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * @return boolean
     */
    public function isAjax(): bool
    {
        return $this->ajax;
    }

    /**
     * @param boolean $ajax
     * @return Action
     */
    public function setAjax(bool $ajax): Action
    {
        $this->ajax = $ajax;
        return $this;
    }

    /**
     * @param \callable[] $onClick
     * @return Action
     */
    public function setOnClick(array $onClick): Action
    {
        $this->onClick = $onClick;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getCallback(): ?string
    {
        return $this->callback;
    }

    /**
     * @param null|string $callback
     * @return Action
     */
    public function setCallback($callback): Action
    {
        $this->callback = $callback;
        return $this;
    }

    public function perform(EntityGrid $grid,Section $section,?ActiveRow $row):void
    {
        if($this->onClick){
            $this->onClick($section,$row);
        }
        if(\is_string($this->callback)){
            Callback::toReflection([$section,$this->callback])->invokeArgs($section,array_merge(['item'=>$row],$this->params));
        }elseif(\is_callable($this->callback)){
            Callback::toReflection($this->callback)->invokeArgs($section,array_merge(['item'=>$row],$this->params));
        }
    }

    /**
     * @return array
     */
    public function getAttrs(): ?array
    {
        return $this->attrs;
    }

    /**
     * @param array $attrs
     * @return Action
     */
    public function setAttrs(?array $attrs): Action
    {
        $this->attrs = $attrs;
        return $this;
    }


}
