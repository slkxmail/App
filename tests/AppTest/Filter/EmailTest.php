<?php

namespace AppTest\Filter;

use AppTest\Filter\TestCase as ParentTestCase;
use App\Filter\Email;

class EmailTest extends ParentTestCase
{
    public function setUp()
    {
        $this->filter = new Email();
    }

    public function testFilter()
    {
        $filter = new Email();

        $time = time();
        $this->assertEquals('meniam@gmail.com',
            $filter->filter('mailto: meniam@gmail.com'));

        $this->assertEquals('meniam@gmail.com',
            $filter->filter('meniam @ gmail.com'));

        $this->assertEquals('meniam@gmail.com',
            $filter->filter('meniam [at] gmail.com'));
    }
}

