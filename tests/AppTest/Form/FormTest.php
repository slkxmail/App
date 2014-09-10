<?php

namespace AppTest\Form;

use App\Form\Element;
use App\Form\Exception\DomainException;
use App\Form\Form;
use AppTest\TestCase as ParentTestCase;
use App\Form\Factory;

class FormTest extends ParentTestCase
{
    public function testCreate()
    {
        $element = new Form('test');
        $this->assertInstanceOf('App\Form\Form', $element);
    }

    public function testSetters()
    {
        $element = new Form('test');

        $element->setAttributes(array('action' => '/'));
        $this->assertEquals('/', $element->getAction());

        $element->setAction('/test/');
        $this->assertEquals('/test/', $element->getAction());

        $element->setAutocomplete('true');
        $this->assertEquals('on', $element->getAutocomplete());

        $this->assertEquals(Form::METHOD_POST, $element->getMethod());
        $element->setMethod(Form::METHOD_GET);
        $this->assertEquals(Form::METHOD_GET, $element->getMethod());

        $element->setEnctype('text/plain');
        $this->assertEquals('text/plain', $element->getEnctype());
    }

    public function testToArray()
    {
        $element = new Element('test');
        $form = new Form('test');
        $form->add($element);

        $formArray = $form->toArray();
        $this->assertInternalType('array', $formArray);
        $this->assertEquals('test', $formArray['name']);
        $this->assertEquals('POST', $formArray['method']);

        $this->assertEquals(true, isset($formArray['elements']));
        //$this->assertEquals('test', $formArray['elements'][0]['name']);
    }

    public function testRender()
    {
        $factory = new Factory();
        $expected = <<<EOS
<form name="test" action="/action/" method="POST">
<input name="test"/>

</form>

EOS;

        $form = $factory->create(array(
            'name' => 'test',
            'type' => 'App\Form\Form',
            'options' => array(
                'view_path' => FIXTURES_PATH . '/Form/decorators/',
            ),
        ));
        $form->setAction('/action/');
        $this->assertEquals(array(FIXTURES_PATH . '/Form/decorators'), $form->getViewPath());

        $element = new Element('test');
        $element->setType('input')
                ->setDecorator('input');

        $form->add($element);
//        $this->assertEquals($expected, $form->render('test_form'));

        // exception
//        $form->render('unknown');
    }
}
