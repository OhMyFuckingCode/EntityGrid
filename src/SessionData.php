<?php

namespace Quextum\EntityGrid;

use Nette\Utils\ArrayHash;

class SessionData extends ArrayHash
{
    /** @var  string[] */
    public $search = [];

    /** @var  string[] */
    public $order = BaseGrid::DEFAULT_ORDER;

    /** @var  int */
    public $limit = BaseGrid::DEFAULT_LIMIT;

    /** @var  boolean[] */
    public $selection = [];

    /** @var boolean[] */
    public $editing = [];

    /** @var boolean[] */
    public $hiddenColumns = [];

    /** @var boolean[] */
    public $expandedRows = [];

}