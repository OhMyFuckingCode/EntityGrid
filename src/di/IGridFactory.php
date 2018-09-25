<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 25.09.18
 * Time: 11:02
 */

namespace Quextum\EntityGrid;


use Nette\Database\Table\Selection;

interface IGridFactory
{

    /**
     * @param string $key
     * @param Selection $source
     * @param IModel|null $model
     * @param string|null $prefix prefix for translator.
     * @return EntityGrid
     */
    public function create(string $key, Selection $source, IModel $model = null, string $prefix = null);

}