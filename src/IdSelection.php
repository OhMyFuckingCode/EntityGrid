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


    /** @var  int[] */
    public $ids = [];

    public function set($data)
    {
        $post = filter_var_array($data, [
            'ids' => [
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_FORCE_ARRAY | FILTER_REQUIRE_ARRAY,
            ]
        ]);
        $this->setIds($post['ids']);
        return $this;
    }

    public function add($id): self
    {
        $this->ids[$id] = true;
        return $this;
    }

    public function remove($id): self
    {
        unset($this->ids[$id]);
        return $this;
    }

    public function has($id):bool
    {
        return isset($this->ids[$id]);
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

    /**
     * @param \int[] $ids
     * @return IdSelection
     */
    public function addIds($ids): self
    {
        $this->ids = array_merge(array_flip($ids));
        return $this;
    }

    /**
     * @param \int[] $ids
     * @return IdSelection
     */
    public function removeIds($ids): self
    {
        $this->ids = array_intersect_key($this->ids, array_flip($ids));
        return $this;
    }

    public function filter(Selection $selection): void
    {
        $selection->where([$selection->getPrimary() => $this->ids()]);
    }

    public function ids():array
    {
        return array_keys($this->ids);
    }

    public function clean()
    {
        $this->ids = [];
        return $this;
    }

}