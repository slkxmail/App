<?php

namespace AppTest\Filter;

use App\Filter\Ucwords;
use AppTest\Filter\TestCase as ParentTestCase;

class UcwordsTest extends ParentTestCase
{
    public function testFilter()
    {
        $ucwords = new Ucwords();

        $this->assertEquals('', $ucwords->filter(' '));
        $this->assertEquals('Привет-Мир', $ucwords->filter(' привет-мир'));
        $this->assertEquals('Мама Мыла Раму!', $ucwords->filter(' Мама   МЫЛА раму!'));
        $this->assertEquals('&laquo;мама Мыла Раму!', $ucwords->filter('&laquo;мама   МЫЛА раму!'));
    }
}
