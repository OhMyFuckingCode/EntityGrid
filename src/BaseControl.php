<?php
/**
 * Created by PhpStorm.
 * User: prosky
 * Date: 26.06.18
 * Time: 13:56
 */

namespace Quextum\EntityGrid;


use App\Common\Controls\TControl;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Localization\ITranslator;
use Nette\Reflection\ClassType;
use Nette\Security\User;
use Nette\Utils\Strings;
use ReflectionMethod;


/**
 * Class BaseControl
 * @package App\Common\Controls
 * @method onBeforeRender(BaseControl $this, Template $template);
 * @method onPresenterAttached(Presenter $presenter);
 * @property Template $template
 */
abstract class BaseControl extends Control
{

    public $onPresenterAttached;
    public $onBeforeRender;
    /** @var  ITranslator */
    protected $translator;
    protected $templateFile;
    protected $templateName = 'template.latte';

    /** @var  SessionData */
    protected $session;

    /** @var  string */
    protected $view;

    /** @var  BaseGrid */
    protected $grid;
    /** @var  Section */
    protected $section;


    protected function gridAttached(BaseGrid $grid)
    {

    }

    /**
     * @return mixed
     */
    public function getTemplateFile()
    {
        return __DIR__ . '/templates/' . $this->grid->detectView() . '/' . $this->templateName;
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


    public function __construct()
    {
        parent::__construct();
        $this->monitor(Presenter::class, function ($presenter) {
            $this->presenterAttached($presenter);
            $this->onPresenterAttached($presenter);
        });
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
        $this->initTraits();
    }

    private function initTraits(): void
    {
        $reflection = new ClassType($this);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PROTECTED) as $method) {
            $name = $method->getName();
            if (Strings::startsWith($name, 'initTrait')) {
                $this->$name();
            }
        }
    }

    /**
     * @param mixed $templateFile
     */
    public function setTemplateFile($templateFile)
    {
        $this->templateFile = $templateFile;
    }

    /**
     * @param string $templateName
     */
    public function setTemplateName(string $templateName)
    {
        $this->templateName = $templateName;
    }

    protected function init()
    {

    }

    /*
     * @return string
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    protected function beforeRender()
    {
        $this->onBeforeRender($this, $this->template);
        if (isset($this->getTemplate()->flashes) && $this->getPresenter()->isAjax()) {
            $this->redrawControl('flashes');
        }
    }

    public function render()
    {
        $this->init();
        $this->beforeRender();
        $this->template->setFile($this->getTemplateFile());
        $this->template->render();
    }

    public function getUser(): User
    {
        return $this->getPresenter()->getUser();
    }


    protected function presenterAttached(Presenter $presenter)
    {
        if (!$this->translator) {
            $this->translator = $presenter->getTranslator();
        }
    }
}