<?php

namespace Quextum\EntityGrid;

use Nette\Application\UI\Component;
use Nette\Application\UI\Link;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\SmartObject;

/**
 * Class GridRow
 * @package Quextum\EntityGrid
 * @method onClick(Row $row, ActiveRow $item = NULL)
 */
class Action
{

    use SmartObject;

    /** @var  callable[] */
    public $onClick;

    /** @var  string */
    protected $icon;

    /** @var  string */
    protected $label;

    /** @var  string */
    protected $title;

    /** @var  string[] */
    protected $class = ['btn', 'btn-sm', 'btn-secondary', 'ajax'];

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
     * @return string
     */
    public function getIcon()
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
     * @return string
     */
    public function getLabel()
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
     * @return string
     */
    public function getTitle()
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
     * @param Component|TGridComponent $component
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
        $item = $component->lookup(Row::class)->getItem();
        if (\is_array($this->link)) {
            list($destination, $params) = $this->link;
            $params = array_intersect_key($item->toArray(), array_flip($params));
        }
        if (\is_string($this->link)) {
            $destination = $this->link;
            $params = null;
        }
        if (strpos($destination, ':') === 0) {
            $component = $component->getPresenter();
        }
        foreach ($this->params as $key => $value) {
            if (is_numeric($key)) {
                unset($this->params[$key]);
                $this->params[$value] = $item->$value;
            } else {
                $this->params[$key] = $value;
            }
        }

        return new Link($component, $destination, array_merge($params, $this->params));
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
    public function getConfirm()
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
     * @return static
     */
    public function setAjax(bool $ajax)
    {
        $this->ajax = $ajax;
        return $this;
    }
}
