<?php

namespace App\Filter;

/**
 * Фильтрует поле как флаг в базе данных
 *
 * @category   category
 * @package    package
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class IsFlag extends \Zend\Filter\AbstractFilter
{
    public function filter($value)
    {
        $value = preg_replace('#[^yn]+#usi', '', $value);

        if (empty($value)) {
            return '';
        } else {
            return strtolower($value[0]);
        }
    }
}
