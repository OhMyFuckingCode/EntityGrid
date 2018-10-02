<?php

namespace Quextum\EntityGrid;


use Nette\Database\Table\Selection;

interface IFormatter
{


    public function entities(Selection $items, SearchDefinition $column);

    public function items(Selection $items, SearchDefinition $column);

    public function image($entity, SearchDefinition $column);


}