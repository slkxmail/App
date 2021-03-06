<?php

namespace App\Filter;

class StringTrim extends \Zend\Filter\StringTrim
{
    /**
     * Unicode aware trim method
     * Fixes a PHP problem
     *
     * @param string $value
     * @param string $charlist
     * @return string
     */
    protected function unicodeTrim($value, $charlist = null)
    {
        if ($charlist === null) {
            return trim($value);
        } else {
            return trim($value, $charlist);
        }
    }
}
