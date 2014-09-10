<?php

namespace AppTest\Filter;
use AppTest\TestCase as ParentTestCase;

abstract class TestCase extends ParentTestCase
{
    protected $filter;

    public function testInstanceOf()
    {
        if ($this->filter) {
            $this->assertInstanceOf('Zend\Filter\FilterInterface', $this->filter);
        }
    }

}
