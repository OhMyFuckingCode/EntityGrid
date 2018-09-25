<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 25.09.18
 * Time: 11:02
 */

namespace Quextum\EntityGrid;


use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;

interface IFormFactory
{
    /**
     * @param array $config
     */
    public function create($config);

    /**
     * @param Selection $selection
     * @param ArrayHash $values
     */
    public function apply($selection, $values);

}