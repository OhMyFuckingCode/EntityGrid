<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 25.09.18
 * Time: 11:02
 */

namespace Quextum\EntityGrid;


use Nette\Application\UI\Form;
use Nette\Database\IRow;

interface IFormFactory
{

    /**
     * @param IRow $row
     * @return Form
     */
    public function create(IRow $row = null);



}