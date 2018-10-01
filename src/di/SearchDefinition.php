<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 01.10.18
 * Time: 11:40
 */

namespace Quextum\EntityGrid;


class SearchDefinition
{

    /** @var string  */
    protected $name;

    /** @var string  */
    protected $column;

    /** @var string[]|string  */
    protected $type;

    /** @var string */
    protected $method = 'addText';

    /** @var  string */
    protected $value = 'id';

    /** @var  string|null */
    protected $label;

    /** @var  string|bool */
    protected $image = false;

    /**
     * SearchDefinition constructor.
     * @param $name
     * @param array|string $def
     * @param array $methods
     */
    public function __construct(string $name, $def, ?array $methods)
    {
        $this->name = $name;
        if (\is_array($def)) {
            foreach ($def as $key => $value) {
                $this->$key = $value;
            }
            $this->column = $def['column']??$name;
            $this->type = $def['type'];
            $this->method = $def['method']??$methods[$this->type]??$this->method;
        } else {
            $this->type = $def;
            $this->column = $name;
            $this->method = $def['method']??$methods[$this->type]??$this->method;
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @param mixed $column
     */
    public function setColumn($column)
    {
        $this->column = $column;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed|null
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed|null $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value)
    {
        $this->value = $value;
    }

    /**
     * @return null|string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param null|string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return bool|string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param bool|string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

}