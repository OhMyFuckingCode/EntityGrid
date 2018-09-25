<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 25.09.18
 * Time: 11:02
 */

namespace Quextum\EntityGrid;


use Nette\Database\Table\ActiveRow;
use Nette\Utils\ArrayHash;

interface IModel
{

    public function get($id):?ActiveRow;

    public function insert(ArrayHash $values);

    public function update(ActiveRow $row, ArrayHash $values);

    public function delete(ActiveRow $row);

}