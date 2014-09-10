<?php

namespace App\Filter;

class UrlTranslit extends \Zend\Filter\AbstractFilter
{
	public function filter($value)
	{
		return \App\Translit\Translit::url($value);
	}
}
