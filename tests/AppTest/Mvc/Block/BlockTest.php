<?php

namespace AppTest\Mvc\Block;

use App\Mvc\Block\Block;
use AppTest\TestCase as ParentTestCase;

class BlockTest extends ParentTestCase
{
    public function testSettersAndGetterParam()
    {
        /*$object = new Block(array());
        $this->_testParamSettersAndGetters($object, 'param');
        $this->_testSettersAndGetters($object, 'name', 'string', false);
        $this->_testSettersAndGetters($object, 'show', 'bool', true);*/
    }

    public function testGetParam()
    {
        $expectedValue = array(
            'name'  => 'test',
            'param' => array('test'  =>
                             array(
                                 'name'  => 'test',
                                 'value' => 'val',
                                 'test2' =>
                                 array(
                                     'value' => 'val2',
                                     'test3' => array('value' => 'yo'))),
                             'test2' =>
                             array(
                                 'name'  => 'test',
                                 'value' => 'val',
                                 'test2' =>
                                 array(
                                     'value' => 'val2',
                                     'test3' => array('value' => 'yo')))));

        $object = new Block($expectedValue);

        $this->assertEquals('val', $object->getParam('test'));
        $this->assertEquals('val2', $object->getParam('test/test2'));

        $object->removeParam('test/test2');
        $this->assertEquals(null, $object->getParam('test/test2'));

        $this->assertEquals('val', $object->getParam('test2'));
        $object->removeParam('test2');
        $this->assertEquals(null, $object->getParam('test2'));

        $object->removeParam(null);
        $this->assertEquals(null, $object->getParam('test'));

    }
}

