<?php

namespace App\Filter;

class Ucfirst extends \Zend\Filter\AbstractFilter
{
	public function filter($value)
	{
		return mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8') . mb_strtolower(mb_substr($value, 1, mb_strlen($value, 'UTF-8'), 'UTF-8'), 'UTF-8');
	}
}