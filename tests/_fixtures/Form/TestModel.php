<?php

namespace Model;

use App\Filter\Filter;
use App\Validator\Validator;

class TestModel
{
    public function filterValue($value, $field = null)
    {
        return Filter::filterStatic($value, 'App\Filter\Name');
    }

    public function validateValue($value, $field = null)
    {
        $validator = Validator::getValidatorInstance('Zend\Validator\StringLength', array('min' => 1, 'max' => 10));
        if (!$validator->isValid($value)) {
            return $validator;
        }

        return true;
    }

    public static function getInstance()
    {
        return new self();
    }
}