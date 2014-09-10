<?php

namespace AppTest\Filter;


use App\Filter\StringTrim;

class StringTrimTest extends \PHPUnit_Framework_TestCase
{

    public function testFilter()
    {
        $stringTrim = new StringTrim();

        $this->assertEquals('test', $stringTrim->filter("\r\n\t     test\r\n\t     "));

        $stringTrim = new StringTrim(array('charlist' => "\r\n"));
        $this->assertEquals(' test', $stringTrim->filter("\r\n test\r\n"));
    }

}
