<?php

namespace Quextum\EntityGrid;

use Nette\Application\UI\Component;
use Nette\Application\UI\Link;
use Nette\Database\IRow;
use Nette\Database\Table\ActiveRow;
use Nette\SmartObject;
use Nette\Utils\Strings;

/**
 * Class GridRow
 * @package Quextum\EntityGrid
 */
class Column
{

    use SmartObject;

    /** @var  string */
    protected $name;

    /** @var  string */
    protected $column;

    /** @var  string */
    protected $label;

    /** @var  string */
    public $type;

    /** @var  bool */
    protected $hidden = false;

    /** @var  string|null */
    protected $ref;

    /** @var  string|null */
    protected $related;

    /** @var  bool|null */
    protected $order;

    /** @var  string|null */
    protected $link;

    /** @var  string[] */
    protected $params;

    /** @var  callable */
    protected $renderer;

    /** @var  array */
    protected $filters = [];

    /** @var  string|null */
    protected $block;

    /** @var  string|null */
    protected $template;

    public $position;

    /** @var  bool */
    protected $escape = true;

    /** @var  string */
    protected $class;

    /**
     * GridColumn constructor.
     * @param string $name
     * @param string $label
     * @param string $column
     * @param string|array $type
     */
    public function __construct($name, $label, $column, $type)
    {
        $this->name = $name;
        $this->column = $column;
        $this->label = $label;
        if (\is_string($type)) {
            $this->type = $this->checkType($type, $column);
        } elseif (\is_array($type)) {
            foreach ($type as $key => $value) {
                $this->$key = ($key == 'type') ? $this->checkType($value, $column) : $value;
            }
        }
    }

    private function checkType($type, $column)
    {
        if (Strings::startsWith($type, ':')) {
            list($this->related, $this->column) = explode('.', Strings::after($type, ':')) + [$column];
        }
        if (!$this->related && Strings::contains($type, '.')) {
            list($this->ref, $this->column) = explode('.', $type);
        }
        return $type;
    }

    /**
     * @param string $link
     * @param array $params
     * @return $this|Column
     */
    public function setLink(string $link, array $params = []): self
    {
        $this->link = $link;
        $this->params = $params;
        return $this;
    }

    /**
     * @param Component $control
     * @param IRow $row
     * @return null|string
     */
    public function getLink(Component $control, IRow $row)
    {
        if (!$this->link) return null;

        if ($this->link === true) {
            return $row->{$this->column};
        }

        if (\is_array($this->link)) {
            list($this->link, $this->params) = $this->link;
        }
        if (Strings::startsWith($this->link, ':')) {
            $control = $control->getPresenter();
        }
        $link = new Link($control, $this->link);
        if ($this->params) {
            foreach ($this->params as $param) {
                $link->setParameter($param, $row->$param);
            }
        }

        return $link;
    }

    /**
     * @param boolean $hidden
     * @return $this|Column
     */
    public function setHidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param mixed $ref
     * @return $this|Column
     */
    public function setRef($ref): self
    {
        $this->ref = $ref;
        return $this;
    }

    /**
     * @param mixed $related
     * @return $this|Column
     */
    public function setRelated($related): self
    {
        $this->related = $related;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * @return mixed
     */
    public function getRelated()
    {
        return $this->related;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Column
     */
    public function setName(string $name): Column
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @param string $column
     * @return Column
     */
    public function setColumn(string $column): Column
    {
        $this->column = $column;
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
     * @return Column
     */
    public function setLabel(string $label): Column
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @param mixed $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return bool|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param string $type
     * @return $this|Column
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param ActiveRow $row
     * @return mixed|ActiveRow|\Nette\Database\Table\GroupedSelection|null
     */
    public function getValue(ActiveRow $row)
    {
        if ($this->type === 'self') {
            if($this->name !== $this->column){
                return $row->{$this->column}??NULL;
            }
            return $row;
        }
        if ($this->ref) {
            return $row->ref($this->ref, $this->column);
        }
        if ($this->related) {
            return $row->related($this->related, $this->column);
        }
        return $row->{$this->column}??NULL;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     * @return Column
     */
    public function setFilters(array $filters): Column
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * @param string $name
     * @param array $args
     */
    public function addFilter(string $name, array $args = [])
    {
        $this->filters[$name] = $args;
    }

    /**
     * @return string
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @param string $block
     * @return Column
     */
    public function setBlock(string $block): Column
    {
        $this->block = $block;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     * @return Column
     */
    public function setTemplate(string $template): Column
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEscaped(): bool
    {
        return $this->escape;
    }

    /**
     * @param bool $bool
     */
    public function setEscape(bool $bool)
    {
        $this->escape = $bool;
    }

    public function setArgs(array $args)
    {
        foreach ($args as $prop => $arg) {
            if (property_exists($this, $prop)) {
                $this->$prop = $arg;
            }
        }
    }

    /**
     * @return string
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(?string $class)
    {
        $this->class = $class;
    }
}
