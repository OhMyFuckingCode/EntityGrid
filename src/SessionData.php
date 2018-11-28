<?php

namespace Quextum\EntityGrid;

use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;

class SessionData extends ArrayHash
{
    /** @var  string[] */
    public $search = [];

    /** @var  string[] */
    public $order;

    /** @var  int */
    public $limit = BaseGrid::DEFAULT_LIMIT;

    /** @var IdSelection */
    public $selection;

    /** @var  boolean */
    public $showSelection = false;

    /** @var boolean[] */
    public $editing = [];

    /** @var boolean[] */
    public $hiddenColumns = [];

    /** @var boolean[] */
    public $expandedRows = [];

    public $locale = null;

    /**
     * SessionData constructor.
     */
    public function __construct()
    {
        $this->selection = new IdSelection();
    }





}