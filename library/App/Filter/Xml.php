<?php
/**
 * Фильтр запрещенных символов, которые рушат xml
 */
namespace App\Filter;

class Xml extends \Zend\Filter\AbstractFilter
{
	public function filter($value)
	{
        return preg_replace("#[\x01-\x08\x0B-\x0C\x0E-\x1F]#","", $value);
	}
}
