<?php

namespace AppTest\Form;

use App\Form\Exception\DomainException;
use AppTest\TestCase as ParentTestCase;
use App\Form\Factory;

class FactoryTest extends ParentTestCase
{
    /**
     * @expectedException DomainException
     */
    public function testCreate()
    {
        $factory = new Factory;

        $element = $factory->create(array('type' => 'App\Form\Element', 'name'=>'test', 'options' => array('option' => 'option_value'), 'attributes' => array('attr' => 'attr_value')));
        $this->assertInstanceOf('App\Form\Element', $element);
        $this->assertEquals('test', $element->getName());
        $this->assertEquals('option_value', $element->getOption('option'));
        $this->assertEquals('attr_value', $element->getAttribute('attr'));


        $element = $factory->create(array('type' => 'App\Form\Fieldset', 'name'=>'test', 'options' => array('option' => 'option_value'), 'attributes' => array('attr' => 'attr_value')));
        $this->assertInstanceOf('App\Form\Fieldset', $element);
        $this->assertEquals('test', $element->getName());
        $this->assertEquals('option_value', $element->getOption('option'));
        $this->assertEquals('attr_value', $element->getAttribute('attr'));

        $element = $factory->createFieldset(array('name'=>'test', 'options' => array('option' => 'option_value'), 'attributes' => array('attr' => 'attr_value')));
        $this->assertInstanceOf('App\Form\Fieldset', $element);
        $this->assertEquals('test', $element->getName());
        $this->assertEquals('option_value', $element->getOption('option'));
        $this->assertEquals('attr_value', $element->getAttribute('attr'));

        $element = $factory->createElement(array('name'=>'test', 'options' => array('option' => 'option_value'), 'attributes' => array('attr' => 'attr_value')));
        $this->assertInstanceOf('App\Form\Element', $element);
        $this->assertEquals('test', $element->getName());
        $this->assertEquals('option_value', $element->getOption('option'));
        $this->assertEquals('attr_value', $element->getAttribute('attr'));

        $element = $factory->createForm(array('name'=>'test', 'options' => array('option' => 'option_value'), 'attributes' => array('attr' => 'attr_value')));
        $this->assertInstanceOf('App\Form\Form', $element);
        $this->assertEquals('test', $element->getName());
        $this->assertEquals('option_value', $element->getOption('option'));
        $this->assertEquals('attr_value', $element->getAttribute('attr'));

        $element = $factory->create(array('type' => 'App\Form\Form', 'name'=>'test', 'options' => array('option' => 'option_value'), 'attributes' => array('attr' => 'attr_value')));
        $this->assertInstanceOf('App\Form\Form', $element);
        $this->assertEquals('test', $element->getName());
        $this->assertEquals('option_value', $element->getOption('option'));
        $this->assertEquals('attr_value', $element->getAttribute('attr'));

        // Exception
        $factory->create(array('type' => 'App\Form\Forms'));
    }
}
