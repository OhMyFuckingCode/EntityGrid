<?php

namespace App\AdminModule\Controls\EntityGrid;

/**
 * Class TActions
 * @package App\AdminModule\Controls\EntityGrid
 */
trait TGridComponent
{

    /** @var  SessionData */
    protected $session;

    /** @var  string */
    protected $view;

    /** @var  BaseGrid */
    protected $grid;
    /** @var  Section */
    protected $section;

    public function injectTGridComponent()
    {
        $this->monitor(BaseGrid::class, function ($grid) {
            $this->grid = $grid;
            $this->session = $this->grid->getSession();
            $this->gridAttached($grid);
        });
        $this->monitor(Section::class, function ($section) {
            $this->section = $section;
        });
        $this->onBeforeRender[] = function () {
            $this->view = $this->grid->getView();
            $this->template->grid = $this->grid;
            $this->template->section = $this->section;
        };
    }


    protected function gridAttached(BaseGrid $grid)
    {

    }

    /**
     * @return string
     */
    public function getTemplateName(): string
    {
        return "templates/{$this->view}/{$this->templateName}";
    }

    /**
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * @param string $view
     * @return TGridComponent|static
     */
    public function setView(string $view):self
    {
        $this->view = $view;
        return $this;
    }
}
