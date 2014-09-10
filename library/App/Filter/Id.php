<?php

namespace App\Filter;

class Id extends \Zend\Filter\AbstractFilter
{
    /**
     * Defined by Zend_Filter_Interface
     *
     * Returns (int) $value
     *
     * @param  mixed $value
     * @return integer
     */
    public function filter($value)
    {
        return abs((int)$value);
    }
}
