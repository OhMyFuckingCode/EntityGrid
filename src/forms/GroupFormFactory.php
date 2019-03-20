<?php

namespace Quextum\EntityGrid;


use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\IControl;
use Nette\Utils\ArrayHash;

class GroupFormFactory
{


    /** @var  IFormFactory */
    protected $originalFactory;

    /** @var string */
    protected $prefix;

    /**
     * GroupFormFactory constructor.
     * @param IFormFactory $originalFactory
     */
    public function __construct(IFormFactory $originalFactory)
    {
        $this->originalFactory = $originalFactory;
    }


    public function create():Form
    {
        $original = $this->originalFactory->create(new Row());
        /** @var Form $form */
        $form = new class extends Form
        {
            public function getValues($asArray = false)
            {
                $values = $asArray ? [] : new ArrayHash;
                $original = parent::getValues($asArray);
                unset($original['id']);
                bdump($original);
                self::parseValues($original);
                bdump($original);
                return $original;
            }

            private static function parseValues(&$values): void
            {
                foreach ($values as $name => $value) {
                    if(self::isset($value, '_edit')){
                        if($value['_edit']){
                            $values[$name]=$value['_value'];
                        }else{
                            unset($values[$name]);
                        }
                    }elseif($value instanceof \Traversable){
                        static::parseValues($value);
                        if(\count($value)){
                            unset($values[$name]);
                        }
                    }
                }
            }

            private static function isset($array, string $offset):?bool
            {
                if (\is_object($array)) {
                    return property_exists($array, $offset);
                }
                if (\is_array($array)) {
                    return array_key_exists($offset, $array);
                }
                return null;
            }
        };

        self::processContainer($form, $original);
        $form->addHidden('id');
        return $form;

    }

    public static function processContainer(Container $newContainer, Container $oldContainer): void
    {
        foreach ($oldContainer->getComponents() as $name => $component) {
            if ($component instanceof Container) {
                self::processContainer($newContainer->addContainer($name), $component);
            } else {
                self::createInput($newContainer, $oldContainer, $name, $component);
            }
        }
    }

    public static function createInput(Container $newContainer, Container $oldContainer, string $name, IControl $component): void
    {
        if (!$component->isOmitted() && (!method_exists($component,'isDisabled') || !$component->isDisabled())) {
            //$component->setParent(null);
            $oldContainer->removeComponent($component);
            $container = $newContainer->addContainer($name);

            $edit = $container->addCheckbox('_edit', 'edit');
            if (method_exists($component,'isRequired') &&  $component->isRequired()) {
                $component->setRequired(false);
                $component->addConditionOn($edit, Form::FILLED)
                    ->addRule(Form::FILLED);
            }
            $container['_value'] = $component;
            //$edit->addCondition(Form::FILLED)->toggle($component->getHtmlId());
        }
    }
}