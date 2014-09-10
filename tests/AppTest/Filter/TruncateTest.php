<?php

namespace AppTest\Filter;

use App\Filter\Truncate;

class TruncateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @
     */
    public function testFilter()
    {
        $truncate = new Truncate(array('length' => 10));
        $this->assertEquals('привет мир', $truncate->filter('привет мир большой и беспощадный'));

        $truncate = new Truncate(array('length' => 10, 'etc' => '...'));
        $this->assertEquals('привет...', $truncate->filter('привет мир большой и беспощадный'));

        $truncate = new Truncate(array('length' => 10, 'etc' => '...', 'middle' => true));
        $this->assertEquals('при...ный', $truncate->filter('привет мир большой и беспощадный'));
    }
}
