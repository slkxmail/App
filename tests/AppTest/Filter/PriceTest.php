<?php

namespace AppTest\Filter;

use AppTest\Filter\TestCase as ParentTestCase;
use App\Filter\Price;

class PriceTest extends ParentTestCase
{
    public function setUp()
    {
        $this->filter = new Price();
    }

	public function testFilter()
	{
        $price = new Price();

        $this->assertEquals(floatval(1000000), $price->filter('1.000.000.00'));
        $this->assertEquals(floatval(1000000000), $price->filter('1.000.000.000'));

        $this->assertEquals(floatval(1000000000), $price->filter('1,000,000.000.00'));

        $this->assertEquals(floatval(0), $price->filter(0));
        $this->assertEquals(floatval(1.0), $price->filter('$1'));


        $this->assertEquals(floatval(7800), $price->filter('$7,800.00'));
        $this->assertEquals(floatval(10000), $price->filter('10.000'));
        $this->assertEquals(floatval(10000), $price->filter('10,000'));
        $this->assertEquals(floatval(10.00), $price->filter('10,00'));

		$this->assertEquals(floatval(10.00), $price->filter('10.00'));
		$this->assertEquals(floatval(10000), $price->filter('10 000'));

		$this->assertEquals(floatval(-10.00), $price->filter('-10,00'));
		$this->assertEquals(floatval(-10000), $price->filter('-10,000'));

		$this->assertEquals(floatval(-10.00), $price->filter('-10.00'));
		$this->assertEquals(floatval(-10000), $price->filter('-10.000'));
		$this->assertEquals(floatval(-10000), $price->filter('-10 000'));

		$this->assertEquals(floatval(-10.00), $price->filter('-10 . 00'));

		$this->assertEquals(floatval(1000000.00), $price->filter('1,000,000.00'));
		$this->assertEquals(floatval(1000000.00), $price->filter('1.000.000,00'));
		$this->assertEquals(floatval(10.02), $price->filter('10,02'));
		$this->assertEquals(floatval(10020), $price->filter('10,020'));
		$this->assertEquals(floatval(10.02), $price->filter('10.02'));
		$this->assertEquals(floatval(1000000), $price->filter('1000.000'));
		$this->assertEquals(floatval(10.02), $price->filter('10.02.'));
		$this->assertEquals(floatval(10.02), $price->filter('.10.02.'));
		$this->assertEquals(floatval(10.02), $price->filter('.10.02,'));
	}
}