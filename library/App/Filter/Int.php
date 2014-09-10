<?php

namespace App\Filter;

class Int extends \Zend\Filter\AbstractFilter
{
    /**
     * Defined by Zend\Filter\FilterInterface
     *
     * Returns (int) $value
     *
     * @param  string $value
     * @return integer
     */
    public function filter($value)
    {
        return (int) ((string) $value);
    }
}
