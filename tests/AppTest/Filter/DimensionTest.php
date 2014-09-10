<?php

namespace AppTest\Filter;

use App\Filter\Dimension;
use AppTest\Filter\TestCase as ParentTestCase;

class DimensionTest extends ParentTestCase
{
    public function testFilter()
    {
        $dimension = new Dimension();
        $var = 10;
        $this->assertEquals(10, $dimension->filter($var));
        $this->assertInternalType('float', $dimension->filter($var));

        $var = 10.12;
        $this->assertInternalType('float', $dimension->filter($var));
        $this->assertEquals(10.12, $dimension->filter($var));

        $var = "10.12 inches";
        $this->assertInternalType('float', $dimension->filter($var));
        $this->assertEquals(10.12, $dimension->filter($var));

        $var = "";
        $this->assertInternalType('float', $dimension->filter($var));
        $this->assertEquals(0.0, $dimension->filter($var));

        $var = "$0";
        $this->assertInternalType('float', $dimension->filter($var));
        $this->assertEquals(0.0, $dimension->filter($var));
    }
}
