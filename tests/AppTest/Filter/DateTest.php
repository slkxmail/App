<?php

namespace AppTest\Filter;

use AppTest\Filter\TestCase as ParentTestCase;
use App\Filter\Date;

class DateTest extends ParentTestCase
{
    public function testFilter()
    {
        $filter = new Date();

        $time = time();
        $this->assertEquals(date('Y-m-d H:i:s', $time),
            $filter->filter($time));

        $this->assertEquals('2012-03-31 00:00:00',
            $filter->filter('2012-03-31 00:00:00'));

        $this->assertEquals(date('Y-m-d 00:00:00', $time),
            $filter->filter('today'));

        $this->assertEquals(date('Y-m-d H:i:s', time()),
            $filter->filter(null));
    }
}

