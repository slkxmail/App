<?php

namespace App\Filter;

class Domain extends \Zend\Filter\AbstractFilter
{
	public function filter($value)
	{
        $fromLinkTemp = str_replace(array('http://', 'http://www', 'https://', 'https://www', 'www.'), '', trim($value));
        $fromLinkArr = explode('/', $fromLinkTemp);
        $fromDomain = $fromLinkArr[0];

		return $fromDomain;
	}
}
