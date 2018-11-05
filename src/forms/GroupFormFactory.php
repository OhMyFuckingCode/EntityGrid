<?php

namespace Quextum\EntityGrid;


use Nette\Application\UI\Form;
use Nette\Database\Row;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\ArrayHash;

class GroupFormFactory
{


    /** @var  IFormFactory */
    protected $originalFactory;

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
                foreach (parent::getValues($asArray) as $name => $value) {
                    if ($value['edit'] ) {
                        $values[$name] = $value['value'];
                    }
                }
                return $values;
            }
        };

        /**
         * @var string $name
         * @var BaseControl $component
         */
        foreach ($original->getComponents() as $name => $component) {
            if(!$component->isDisabled() && !$component->isOmitted()){
                $component->setParent(null);
                $container = $form->addContainer($name);
                $edit = $container->addCheckbox('edit', 'edit');
                if ($component->isRequired()) {
                    $component->setRequired(false);
                    $component->addConditionOn($edit, Form::FILLED)
                        ->addRule(Form::FILLED);
                }
                $container['value'] = $component;
            }
        }
        $container->addHidden('id');
        return $form;

    }
}