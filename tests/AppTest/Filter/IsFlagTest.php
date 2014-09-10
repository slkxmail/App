<?php

namespace AppTest\Filter;

use AppTest\Filter\TestCase as ParentTestCase;
use App\Filter\IsFlag;

class IsFlagTest extends ParentTestCase
{
    public function testFilter()
    {
        $filter = new IsFlag();

        $this->assertEquals('y',
            $filter->filter(' Yes of course'));
        $this->assertEquals('n',
            $filter->filter(' No of course'));
    }

    public function testForEmpty()
    {
        $filter = new IsFlag();

        $this->assertEquals('', $filter->filter('fuck'));
    }
}

