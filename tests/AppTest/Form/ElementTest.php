<?php

namespace AppTest\Form;

require_once FIXTURES_PATH . '/Form/TestModel.php';

use App\Filter\Filter;
use App\Form\Exception\InvalidArgumentException;
use App\Form\Factory;
use App\Form\Fieldset;
use App\Form\Form;
use App\Validator\Validator;
use AppTest\TestCase as ParentTestCase;
use App\Form\Element;
use Zend\Validator\NotEmpty;

class ElementTest extends ParentTestCase
{
    /**
     * @group nnn1
     */
    public function testIsValid()
    {
        $element = new Element('test');

        $validator = Validator::getValidatorInstance('Zend\Validator\StringLength', array('max' => 10));
        $this->assertInstanceOf('\Zend\Validator\StringLength', $validator);
        $element->addValidator($validator);
        $element->setValue('This is very long string');
        $this->assertFalse($element->isValid());

        $element = new Element('test');
        $element->setAllowEmpty(false);
        $this->assertFalse($element->getAllowEmpty());
        $this->assertFalse($element->isValid());


        $factory = new Factory();
        $element = $factory->create(array(
            'type' => 'App\Form\Element',
            'options' => array(
                'view_path' => FIXTURES_PATH . '/Form/decorators/',
                'multiple' => true,
                'validators' => array(
                    'Zend\Validator\Digits'
                )
            ),
            'attributes' => array(
                'id'   => 'id_name',
                'name' => 'var_name',
                'class' => 'input p_input',
                'value' => '<some value "here">',
                'label' => 'Some label name',
                'label_class' => 'test'
            )
        ));

        $element->setOptions(array('model_link' => array('Model\TestModel')));

        // True if empty value
        $this->assertTrue($element->setAllowEmpty(true)->isValid());
        $this->assertTrue($element->setValue('34')->isValid());

        $element = new Element('test');
        $element->setValue('test');

        $this->assertTrue($element->isValid());

        $element->addValidator('Zend\Validator\StringLength', array('min' => 1, 'max' => 2));
        $this->assertFalse($element->isValid());

        $element->setModelLink('Model\TestModel', 'test');
        $this->assertFalse($element->isValid());

        $element->setModelLink('Model\TestModel');
        $this->assertFalse($element->isValid());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRenderLabel()
    {
        $factory = new Factory();

        $element = $factory->create(array(
            'type' => 'App\Form\Element',
            'options' => array(
                'view_path' => FIXTURES_PATH . '/Form/decorators/',
                'multiple' => true,
                'validators' => array(
                    'Zend\Validator\Digits'
                )
            ),
            'attributes' => array(
                'id'   => 'id_name',
                'name' => 'var_name',
                'class' => 'input p_input',
                'value' => '<some value "here">',
                'label' => 'Some label name',
                'label_class' => 'test'
            )
        ));

        $this->assertEquals(array('class' => 'test'), $element->getLabelAttributes());

        $element->setDecorator('input_values');
        $this->assertEquals('<label for="id_name">Some label name</label>' . "\n", $element->renderLabel());
        $this->assertEquals('<h1>Some label name</h1>' . "\n", $element->renderLabel(null, 'multiple_input_context/input_context/label_context'));
        $factory = new Factory();

        $element->setDecorator('label');
        $this->assertEquals('<label for="id_name">Some label name</label>' . "\n", $element->renderLabel());

        $element->setDecorator('label2');
        $this->assertEquals('<label for="id_name">Some label name</label>' . "\n", $element->renderLabel());

        $element->setDecorator('label3');
        $this->assertEquals('<label for="id_name">Some label name</label>' . "\n", $element->renderLabel());

        $element->setDecorator('label4');
        $this->assertEquals('<label for="id_name">Some label name</label>' . "\n", $element->renderLabel());

        $element->setDecorator(null);
        $this->assertEquals('', $element->renderLabel());

        $element->setDecorator('label4');
        $this->assertEquals('', $element->renderLabel(null, 'unknown'));
    }

    public function testType()
    {
        $element = new Element();
        $this->assertEquals('text', $element->getType());
        $this->assertEquals('password', $element->getType('password'));

        $this->assertEquals('password', $element->setType('password')->getType());
    }


    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetView()
    {
        $factory = new Factory();

        $element = $factory->create(array(
            'type' => 'App\Form\Element',
            'options' => array(
                'view_path' => FIXTURES_PATH . '/Form/decorators/')
        ));

        $getViewMethod = self::getMethod($element, 'getView');
        $view = $getViewMethod->invokeArgs($element, array('test'));
        $this->assertInstanceOf('Blitz', $view);

        // must throw an exception
        $getViewMethod->invokeArgs($element, array('test_unknown'));
    }

    public function testRender()
    {
        $factory = new Factory();

        $expected = <<<EOS
<label class="label" for="id_name">Some label name</label>
<input
id="id_name"
name="var_name[]"
class="input p_input"
value="&lt;some value &quot;here&quot;&gt;"
/>
<ul>
    <li>[notDigits]: The input must contain only digits</li>
</ul>

EOS;

        $element = $factory->create(array(
            'type' => 'App\Form\Element',
            'options' => array(
                'view_path' => FIXTURES_PATH . '/Form/decorators/',
                'multiple' => true,
                'validators' => array(
                    'Zend\Validator\Digits'
                )
            ),
            'attributes' => array(
                'id'   => 'id_name',
                'name' => 'var_name',
                'class' => 'input p_input',
                'value' => '<some value "here">',
                'label' => 'Some label name',
                'label_class' => 'label'
            )
        ));

        $this->assertEquals('', $element->render());
        $this->assertEquals('', $element->render('empty'));

        $this->assertEquals($expected, $element->render('test'));
    }

    /**
     * @group ttt
     */
    public function testInputValuesRender()
    {
        $factory = new Factory();

        $expected = <<<EOS
<label for="id_name">Some label name</label>
<ul>
    <li>[notDigits]: The input must contain only digits</li>
</ul>
<h1>Some label name</h1>
<input
id="id_name"
name="var_name[]"
class="input p_input"
value="&lt;some value &quot;here&quot;&gt;"
/>
<ul>
    <li>[notDigits]: The input must contain only digits</li>
</ul>

EOS;

        $element = $factory->create(array(
            'type' => 'App\Form\Element',
            'options' => array(
                'view_path' => FIXTURES_PATH . '/Form/decorators/',
                'multiple' => true,
                'validators' => array(
                    'Zend\Validator\Digits'
                )
            ),
            'attributes' => array(
                'id'   => 'id_name',
                'name' => 'var_name',
                'class' => 'input p_input',
                'value' => '<some value "here">',
                'label' => 'Some label name',
            )
        ));

        $this->assertEquals($expected, $element->render('input_values'));

    }

    public function testSetValueOptions()
    {
        $element = new Element('test');

        $options = array('0' => 'test');
        $element->setValueOptions($options);

        $this->assertEquals($options, $element->getValueOptions());

    }

    public function testEmptyOption()
    {
        $element = new Element('test');
        $element->setEmptyOption('test');
        $this->assertEquals('test', $element->getEmptyOption());
    }

    public function testMultiple()
    {
        $element = new Element('test');
        $this->assertFalse($element->getMultiple());
        $element->setMultiple(true);
        $this->assertTrue($element->getMultiple());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testOptions()
    {
        $element = new Element('test',array(), array('opt1' => 'value'));
        $this->assertInternalType('null', $element->getOption('opt_unknown'));
        $this->assertEquals('value', $element->getOption('opt1'));
        $this->assertEquals(array('opt1' => 'value'), $element->getOptions());

        $element->setOptions(array('model_link' => array('Model\TestModel')));
        $this->assertEquals(2, count($element->getModelLink()));

        $element = new Element('test', array(), array('opt1' => 'value'));
        $element->setOption('model_link', array('Model\TestModel'));
        $this->assertEquals(2, count($element->getModelLink()));

        $element = new Element('test', array(), array('opt' => 'value'));
        $this->assertEquals('value', $element->getOption('opt'));
        $this->assertEquals(array('value', 'value2'), $element->addOption('opt', 'value2')->getOption('opt'));

        // Exception
        $element->setOptions(true);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetOptionsFiltersException()
    {
        new Element('test',array(), array('filters' => 'value'));

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetOptionsValidatorsException()
    {
        new Element('test',array(), array('validators' => 'value'));

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetAttributes()
    {
        $element = new Element('test', new \ArrayObject(array('attr' => 'attr_value')), new \ArrayObject(array('opt1' => 'value')));
        $this->assertInternalType('string', $element->getAttribute('attr'));
        $this->assertEquals('attr_value', $element->getAttribute('attr'));
        $this->assertEquals(array('name' => 'test', 'attr' => 'attr_value'), $element->getAttributes());
        $this->assertEquals(array('attr' => 'attr_value'), $element->removeAttribute('name')->getAttributes());
        $this->assertEquals(array(), $element->clearAttributes()->getAttributes());

        $this->assertEquals(array('decorator' => 'test'), $element->setDecorator('test')->getAttributes());
        $this->assertEquals(array('decorator' => 'test'), $element->removeAttributes(array('test'))->getAttributes());
        $this->assertEquals('test', $element->getDecorator());
        $this->assertEquals('', $element->removeAttribute('decorator')->getDecorator());

        $element->setAttributes(null);
    }

    public function testSetValue()
    {
        $element = new Element('test');
        $this->assertInternalType('array', $element->getValue());

        $element = new Element('test', array('value' => 'test value'));
        $this->assertInternalType('array', $element->getValue());

        $element->setAttribute('value', 'test');
        $this->assertInternalType('array', $element->getValue());
        $this->assertEquals(array('test'), $element->getAttribute('value'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testValidators()
    {
        $element = new Element('test');
        $element->addValidator('\Zend\Validator\NotEmpty');

        $validator = $element->getValidator(Validator::getValidatorInstance('Zend\Validator\NotEmpty'));
        $this->assertInstanceOf('\Zend\Validator\NotEmpty', $validator);

        $this->assertTrue($element->hasValidator($validator));

        $this->assertEquals(array(Validator::getValidatorInstance('Zend\Validator\NotEmpty')), $element->getValidators());
        $this->assertEquals(array(Validator::getValidatorInstance('Zend\Validator\NotEmpty')), $element->getValidator());
        $this->assertNull($element->getValidator('unknown'));

        $validator = Validator::getValidatorInstance('Zend\Validator\StringLength', array('max' => 128));
        $this->assertInstanceOf('\Zend\Validator\StringLength', $validator);
        $element->addValidator($validator);
        $this->assertTrue($element->hasValidator($validator));

        $element = new Element('test');
        $validatorInstance = Validator::getValidatorInstance('Zend\Validator\StringLength', array('max' => 128));
        $validator = array('Zend\Validator\StringLength', array('max' => 128));
        $element->addValidator($validator);
        $this->assertTrue($element->hasValidator($validatorInstance));

        $element = new Element('test');
        $validatorInstance = Validator::getValidatorInstance('Zend\Validator\StringLength', array('max' => 128));
        $validator = array('type' => 'Zend\Validator\StringLength', 'params' => array('max' => 128));
        $element->addValidator($validator);
        $this->assertTrue($element->hasValidator($validatorInstance));

        $element = new Element('test');
        $validator = array('type' => 'Zend\Validator\StringLength', 'params' => array('max' => 128));
        $element->addValidator($validator);
        $this->assertFalse($element->hasValidator('Unknown'));
        $element->addValidator($validator);
        $element->addValidator(new \stdClass());
    }

    /**
     * @expectedException App\Form\Exception\InvalidArgumentException
     */
    public function testViewPath()
    {
        $element = new Element('test');
        $this->assertEquals(array(), $element->getViewPath());


        $element = new Element('test', array(), array('view_path' => array(__DIR__, __DIR__)));
        $this->assertEquals(array(__DIR__), $element->getOption('view_path'));

        $element = new Element('test', array(), array('view_path' => __DIR__ . '/../'));
        $this->assertEquals(array(realpath(__DIR__ . '/../')), $element->getOption('view_path'));

        $form = new Form('test', array(), array('view_path' => array(__DIR__)));
        $this->assertEquals(array(__DIR__), $form->getViewPath());
        $form->add($element);

        $this->assertEquals(array(realpath(__DIR__ . '/../'), __DIR__), $element->getViewPath());
        $this->assertEquals(array(__DIR__), $form->getViewPath());

        $element = new Element('test', array(), array('view_path' => './unknown'));
        $this->assertEquals('', $element->getOption('view_path'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetName()
    {
        $element = new Element('test[]');
        $this->assertEquals('test', $element->getInputName());

        $this->assertEquals('test[]', $element->setMultiple(true)->getInputName());

        $element = new Element('test', array(), new \ArrayObject(array('opt1' => 'value')));
        $this->assertEquals('test', $element->getName());

        $this->assertEquals('test', $element->getNameFromMixed(array('name' => 'test')));
        $this->assertEquals('test', $element->getNameFromMixed('test'));
        $this->assertEquals('test', $element->getNameFromMixed($element));

        $element = new Element();
        $this->assertEquals(null, $element->getName());
        $element->setName('name');
        $this->assertEquals('name', $element->getName());

        $element = new Element();
        $this->assertEquals(null, $element->getInputName());
        $this->assertEquals(null, $element->setMultiple(true)->getInputName());
        $element->setName('test_name');
        $this->assertEquals('test_name[]', $element->setMultiple(true)->getInputName());
        $this->assertEquals('test_name', $element->setMultiple(false)->getInputName());

        $fieldset = new Fieldset('fieldset');
        $fieldset->add($element);

        $form = new Fieldset('form[]');
        $form->add($fieldset);

        $this->assertEquals('fieldset[test_name]',   $element->setMultiple(false)->getInputName());
        $this->assertEquals('fieldset[test_name][]', $element->setMultiple(true)->getInputName());

        $form->setMultiple(true);
        $this->assertEquals('form[][fieldset][test_name]',   $element->setMultiple(false)->getInputName());
        $this->assertEquals('form[][fieldset][test_name][]', $element->setMultiple(true)->getInputName());
        $form->setMultiple(false);

        $fieldset->setMultiple(true);
        $this->assertEquals('fieldset[][test_name]',   $element->setMultiple(false)->getInputName());
        $this->assertEquals('fieldset[][test_name][]', $element->setMultiple(true)->getInputName());

        $form->setMultiple(true);
        $this->assertEquals('form[][fieldset][][test_name]',   $element->setMultiple(false)->getInputName());
        $this->assertEquals('form[][fieldset][][test_name][]', $element->setMultiple(true)->getInputName());

        // exception
        $this->assertEquals('test', $element->getNameFromMixed(new \stdClass()));
    }

    public function testGetters()
    {
        $element = new Element('test');
        $this->assertEquals('new', $element->setPlaceholder('new')->getPlaceholder());
        $this->assertEquals(array('new'), $element->setValue('new')->getValue());
        $this->assertEquals(array('new', 'new2'), $element->addValue('new2')->getValue());
        $this->assertEquals(array('new', 'new2', 'key' => 'test'), $element->addValue('test', 'key')->getValue());

        $this->assertEquals('new', $element->setTitle('new')->getTitle());
        $this->assertEquals('new', $element->setLabel('new')->getLabel());
        $this->assertEquals('new', $element->setId('new')->getId());
        $this->assertEquals(true, $element->setIsMultiple('1')->getIsMultiple());
        $this->assertEquals(true, $element->setIsMultiple(true)->isMultiple());


        $this->assertEquals(null, $element->getForm());

        $form = new Form();
        $fieldset = new Fieldset();

        // @fix it
        $this->assertInstanceOf('App\Form\Fieldset', $form->setForm($form)->getForm());
        $this->assertInstanceOf('App\Form\Fieldset', $form->setForm($fieldset)->getForm());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFilters()
    {
        $elementArray = array(
            'type' => 'App\Form\Element',
            'options' => array(
                'model_link' => 'Model\TestModel',
                'filters' => array(
                    'App\Filter\Name',
                )
            )
        );

        $factory = new Factory();
        $element = $factory->create($elementArray);

        $element->setValue(' test');
        $this->assertEquals(array('test'), $element->getValue());

        $element->setValue('    ');
        $this->assertEquals(array(''), $element->getValue());

        $element->setFilters(array('type' => 'Zend\Filter\Digits'));
        $element->setValue(0);
        $this->assertEquals(array('0'), $element->getValue());

        $element->setValue('Тест69');
        $this->assertEquals(array('69'), $element->getValue());

        $elementArray = array(
            'type' => 'App\Form\Element',
            'options' => array(
                'model_link' => 'Model\TestModel',
                'filters' => array(
                    array('type' => 'Zend\Filter\Digits'),
                    array('Zend\Filter\Int'),
                )
            )
        );

        $factory = new Factory();
        $element = $factory->create($elementArray);

        $this->assertEquals(array('69 тест'), $element->setValue('69 тест')->getRawValue());
        $this->assertEquals(array('69'), $element->setValue('69 тест')->getValue());
        $this->assertEquals(array('69 тест'), $element->getRawValue());
        $this->assertEquals(array('69'), $element->setValue('тест 69')->getValue());
        $this->assertEquals(array(''), $element->setValue('тест с очень длинным префиксом')->getValue());

        $filter = Filter::getFilterInstance('Zend\Filter\Int');

        $this->assertTrue($element->hasFilter($filter));
        $this->assertFalse($element->hasFilter('Zend\Filter\Float'));
        $this->assertEquals($filter, $element->getFilter($filter));

        $this->assertNull($element->getFilter('Zend\Filter\Float'));
        $this->assertInternalType('array', $element->getFilter());

        // exception
        $elementArray = array(
            'type' => 'App\Form\Element',
            'options' => array(
                'filters' => array(
                    array(),
                )
            )
        );
        $element = $factory->create($elementArray);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testFilterException()
    {
        $element = new Element('test');
        $element->addFilter(new \stdClass());
    }

    public function testAddClass()
    {
        $element = new Element('test');
        $element->setClass('yo');

        $this->assertEquals('yo', $element->getClass());

        $element->addClass('yo');
        $this->assertEquals('yo', $element->getClass());

        $element->addClass('yo2');
        $this->assertEquals('yo yo2', $element->getClass());

        $element->removeClass('yo');
        $this->assertEquals('yo2', $element->getClass());

    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMessages()
    {
        $element = new Element('some_field');
        $element->setModelLink('Model\TestModel');
        $element->setValue('Значение которое не пройдет валидацию');

        $this->assertFalse($element->isValid());
        $this->assertEquals(array(array('stringLengthTooLong' => 'The input is more than 10 characters long')), $element->getMessages());

        $element->removeModelLink()
                ->clearMessages()
                ->addValidator('Zend\Validator\StringLength', array('min' => 1, 'max' => 5))
                ->isValid();
        $this->assertEquals(array(array('stringLengthTooLong' => 'The input is more than 5 characters long')), $element->getMessages());

        $this->assertEquals(array('stringLengthTooLong' => 'The input is more than 5 characters long'), $element->getMessages(0));
        $this->assertEquals(array(), $element->getMessages(1));

        $element->setMessages(new \stdClass());
    }

}

