<?php

namespace AppTest\Filter;

use App\Filter\Filter;
use AppTest\Filter\TestCase as ParentTestCase;

class FilterTest extends ParentTestCase
{
    /**
     * @expectedException \App\Exception\InvalidElementException
     * @group mm1
     */
    public function testInstance()
    {
        $filter = Filter::getFilterInstance('StringTrim', array(), array('\App\Filter'));
        $this->assertInstanceOf('App\Filter\StringTrim', $filter);
        $this->assertEquals('', $filter->filter("\r\n "));

        $filter = Filter::getFilterInstance('StringTrim', array('charlist' => "\r\n"), array('\App\Filter'));
        $this->assertInstanceOf('App\Filter\StringTrim', $filter);
        $this->assertEquals(' ', $filter->filter("\r\n "));


        Filter::getFilterInstance('Element', array(), array('\App\Form'));
        Filter::getFilterInstance('Unknown', array(), array('\App\Filter'));
    }
}
