<?php

namespace Quextum\EntityGrid;


interface IFormatter
{


    public function entity($entity, SearchDefinition $column);

    public function item($entity, SearchDefinition $column);

    public function entities(\Traversable $items, SearchDefinition $column);

    public function items(\Traversable $items, SearchDefinition $column);

    public function image($entity, SearchDefinition $column);


}