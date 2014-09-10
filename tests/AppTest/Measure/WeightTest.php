<?php

namespace AppTest\Measure;

use AppTest\Measure\TestCase as ParentTestCase;
use App\Measure\Weight;

class DateTest extends ParentTestCase
{
    public function testFilter()
    {
        $weight = new Weight(1, Weight::KILOGRAM);
        $this->assertEquals(1, $weight->getValue());
        $this->assertEquals(2.204622621848775807229738, $weight->setType(Weight::LB)->getValue());
    }
}

