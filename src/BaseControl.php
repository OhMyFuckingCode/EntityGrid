<?php

namespace Quextum\EntityGrid;


use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Localization\ITranslator;
use Nette\Security\User;


/**
 * Class BaseControl
 * @package App\Common\Controls
 * @method onBeforeRender(BaseControl $this, Template $template);
 * @method onPresenterAttached(Presenter $presenter);
 * @property Template $template
 */
abstract class BaseControl extends Control
{
    /** @var  callable[] */
    public $onPresenterAttached;

    /** @var  callable[] */
    public $onBeforeRender;

    /** @var  ITranslator|null */
    protected $translator;

    /** @var string */
    protected $templateName = 'template.latte';

    /** @var  SessionData */
    protected $session;

    /** @var  string */
    protected $view;

    /** @var  BaseGrid */
    protected $grid;

    /** @var  Section */
    protected $section;

    public function __construct()
    {
        parent::__construct();
        $this->monitor(Presenter::class, function (Presenter $presenter) {
            $this->presenterAttached($presenter);
            $this->onPresenterAttached($presenter);
        });
        $this->monitor(BaseGrid::class, function (BaseGrid $grid) {
            $this->grid = $grid;
            $this->session = $grid->getSession();
            $this->translator = $grid->getTranslator();
            $this->gridAttached($grid);
        });
        $this->monitor(Section::class, function (Section $section) {
            $this->section = $section;
        });
        $this->onBeforeRender[] = function () {
            $this->view = $this->grid->getView();
            $this->template->grid = $this->grid;
            $this->template->section = $this->section;
        };
    }

    protected function presenterAttached(Presenter $presenter):void
    {

    }

    protected function gridAttached(BaseGrid $grid): void
    {

    }

    /**
     * @return mixed
     */
    public function getTemplateFile()
    {
        return __DIR__ . '/templates/' . ($this->view ? "$this->view/" : '') . $this->templateName;
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
     * @return BaseControl|static
     */
    public function setView(string $view):self
    {
        $this->view = $view;
        return $this;
    }


    /**
     * @param mixed $templateFile
     * @return static
     */
    public function setTemplateFile($templateFile)
    {
        $this->templateFile = $templateFile;
        return $this;
    }

    /**
     * @param string $templateName
     * @return static
     */
    public function setTemplateName(string $templateName)
    {
        $this->templateName = $templateName;
        return $this;
    }


    /**
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    protected function beforeRender():void
    {
        $this->onBeforeRender($this, $this->template);
        if (isset($this->template->flashes) && $this->getPresenter()->isAjax()) {
            $this->redrawControl('flashes');
        }
    }

    public function render():void
    {
        $this->init();
        $this->beforeRender();
        $this->template->setFile($this->getTemplateFile());
        $this->template->render();
    }

    protected function init():void
    {

    }

    public function getUser(): User
    {
        return $this->getPresenter()->getUser();
    }

    public function lookupRow():?Row
    {
        return $this->lookup(Row::class, false);
    }


    /**
     * @return ITranslator
     */
    public function getTranslator(): ?ITranslator
    {
        return $this->translator;
    }

    /**
     * @param ITranslator $translator
     * @return static
     */
    public function setTranslator(?ITranslator $translator)
    {
        $this->translator = $translator;
        return $this;
    }
}