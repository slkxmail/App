<?php

namespace AppTest\Filter;

use AppTest\Filter\TestCase as ParentTestCase;
use App\Filter\Name;

class NameTest extends ParentTestCase
{
    public function testFilter()
    {
        $filter = new Name();

        $this->assertEquals('ну просто очень большое название котороне полюбому должно быть обрезано, так как не можен являться именем чего угодно и где угодно, посмотрим как с этим справится наш супер фильтрация различных имён. Это достаточно важно, а сама большая ошибка этой',
        $filter->filter(' ну просто очень большое название котороне полюбому должно быть обрезано, так как не можен являться именем чего угодно и где угодно, посмотрим как с этим справится наш супер фильтрация различных имён. Это достаточно важно, а сама большая ошибка этой &#151; системы в том, что обрезать UTF-8 нужно по другому, но этот косяк разрулим как-нить потом'));
        $this->assertEquals('Super бренд® - was here™™', $filter->filter(' Super&nbsp;бренд&reg; &#151; was here™&trade;'));
        $this->assertEquals('"Super star"...', $filter->filter(' &laquo;Super star&raquo;...'));
        $this->assertEquals('Super', $filter->filter('<strong>Super</strong>'));

        // @todo
        //$this->assertEquals('', $filter->filter('<style>Super</style>'));
    }
}

