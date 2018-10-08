<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 05.10.18
 * Time: 11:34
 */

namespace Quextum\EntityGrid;


use Nette\Database\Table\Selection;

class IdSelection
{

    /** @var  bool */
    public $exclude;

    /** @var  int[] */
    public $ids = [];

    public function set($data)
    {
        $post = filter_var_array($data, [
            'exclude' => FILTER_VALIDATE_BOOLEAN,
            'ids' => [
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_FORCE_ARRAY | FILTER_REQUIRE_ARRAY,
            ]
        ]);
        $this->setExclude($post['exclude']);
        $this->setIds($post['ids']);
        return $this;
    }

    /**
     * @param boolean $exclude
     * @return IdSelection
     */
    public function setExclude($exclude): self
    {
        $this->exclude = (bool)$exclude;
        return $this;
    }

    public function has(int $id):bool
    {
        return isset($this->ids[$id]);
    }

    public function isChecked(int $id): bool
    {
        return $this->exclude !== $this->has($id);
    }

    /**
     * @param \int[] $ids
     * @return IdSelection
     */
    public function setIds($ids): self
    {
        $this->ids = array_flip(array_filter((array)$ids));
        return $this;
    }

    public function filter(Selection $selection): void
    {
        if ($this->exclude) {
            $this->ids && $selection->where("NOT {$selection->getPrimary()} IN ?", array_keys($this->ids));
        } else {
            $selection->where("{$selection->getPrimary()} IN ?", array_keys($this->ids));
        }
    }

}