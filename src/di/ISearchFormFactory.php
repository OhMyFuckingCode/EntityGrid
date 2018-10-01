<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 25.09.18
 * Time: 11:02
 */

namespace Quextum\EntityGrid;


use Nette\Application\UI\Form;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;

interface ISearchFormFactory
{
    /**
     * @param BaseGrid $grid
     * @param array $config
     * @param array $options
     * @return Form
     */
    public function create(BaseGrid $grid,$config,$options):Form;

    /**
     * @param Selection $selection
     * @param ArrayHash $values
     */
    public function apply(Selection $selection, $values);

}