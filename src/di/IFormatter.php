<?php

namespace Quextum\EntityGrid;


interface IFormatter
{


    public function entities(\Traversable $items, SearchDefinition $column);

    public function items(\Traversable $items, SearchDefinition $column);

    public function image($entity, SearchDefinition $column);


}