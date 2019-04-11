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
    public function create(BaseGrid $grid, $config, $options):Form;

    /**
     * @param BaseGrid $grid
     * @param Selection $selection
     * @param $config
     * @param ArrayHash $values
     */
    public function apply(BaseGrid $grid, Selection $selection, $config, $values):void;

}