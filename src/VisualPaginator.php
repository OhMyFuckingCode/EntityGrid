<?php

/**
 * Nette Framework Extras
 *
 * This source file is subject to the New BSD License.
 *
 * For more information please see http://extras.nettephp.com
 *
 * @copyright  Copyright (c) 2009 David Grudl
 * @license    New BSD License
 * @link       http://extras.nettephp.com
 * @package    Nette Extras
 * @version    $Id: VisualPaginator.php 4 2009-07-14 15:22:02Z david@grudl.com $
 */
namespace Quextum\EntityGrid;

use Nette;
use Nette\Application\UI\Component;
use Nette\Application\UI\Presenter;
use Nette\Utils\Paginator;

/**
 * Visual paginator control.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2009 David Grudl
 * @package    Nette Extras
 * @property-read \Nette\Utils\Paginator $paginator
 * @method onChange(int $page)
 */
class VisualPaginator extends BaseControl
{
    /** @var Paginator */
    private $paginator;

    /** @persistent */
    public $page;

    /** @var  callable */
    public $onChange;

    public function __construct()
    {
        parent::__construct();
        $this->templateName = 'pagination.latte';
    }


    /**
     * @return Nette\Utils\Paginator
     */
    public function getPaginator(): Paginator
    {
        if (!$this->paginator) {
            $this->paginator = new Paginator;
        }
        return $this->paginator;
    }

    protected function beforeRender():void
    {
        parent::beforeRender();
        $paginator = $this->getPaginator();
        $page = $paginator->page;
        if ($paginator->pageCount < 2) {
            $steps = array($page);

        } else {
            $arr = range(max($paginator->firstPage, $page - 3), min($paginator->lastPage, $page + 3));
            $count = 4;
            $quotient = ($paginator->pageCount - 1) / $count;
            for ($i = 0; $i <= $count; $i++) {
                $arr[] = round($quotient * $i) + $paginator->firstPage;
            }
            sort($arr);
            $steps = array_values(array_unique($arr));
        }

        $this->template->steps = $steps;
        $this->template->paginator = $paginator;
        $this->template->path = $this->lookupPath(Presenter::class) . Component::NAME_SEPARATOR;
    }

    /**
     * Loads state informations.
     * @param  array
     * @return void
     */
    public function loadState(array $params):void
    {
        parent::loadState($params);
        $this->getPaginator()->page = $this->page;

    }

    public function handleGoTo(?int $page): void
    {
        $this->page = $page;
        $this->onChange($page);
        $this->redrawControl();
    }

}