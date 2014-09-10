<?php

namespace App\Filter;

class Slug extends Name
{
	public function filter($value)
	{
        $value = parent::filter($value);
        $value = str_replace('&', 'and', $value);

		$value = \App\Translit\Translit::url($value);
        return trim(preg_replace('#[\-\_\s]+#u', '-', $value), ' -');
	}
}
