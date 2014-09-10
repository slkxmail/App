<?php

namespace App\Filter;

class Ulower extends \Zend\Filter\AbstractFilter
{
	public function filter($value)
	{
        $value = (string)$value;

		return mb_strtolower($value, 'UTF-8');
	}
}