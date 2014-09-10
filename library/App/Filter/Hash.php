<?php

namespace App\Filter;

class Hash extends \Zend\Filter\AbstractFilter
{
    /*
	public function filter($value)
	{
		$value = mb_strtoupper(parent::filter($value) ,'UTF-8');
		$value = preg_replace('#[^A-F\d]+#si', '', $value);
		return mb_substr($value, 0, 40, 'UTF-8');
	}
    */
    public function filter($value)
    {
        return sha1($value);
    }
}
