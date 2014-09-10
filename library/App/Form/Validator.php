<?php


namespace App\Form;

use App\Filter\Filter;
use App\Form\Exception\InvalidArgumentException;
//use App\Validator\Validator;
use Model\Mysql\AbstractModel;
use Traversable;
use Zend\Filter\AbstractFilter;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\AbstractValidator;

class Validator
{
    /**
     * Валидатор email
     */
    const VALIDATOR_EMAIL = 'email';

    /**
     * Валидатор телефона
     */
    const VALIDATOR_PHONE = 'phone';

    /**
     * Обязательное поле
     */
    const VALIDATOR_QERUIRED = 'required';

    /**
     * Целое число
     */
    const VALIDATOR_DIGITS = 'digits';

    /**
     * Обязательное поле
     */
    const VALIDATOR_URL = 'url';

    /**
     * Минимальная длина
     */
    const VALIDATOR_MINLENGHT = 'minlength';

    /**
     * Максимальная длина
     */
    const VALIDATOR_MAXLENGHT = 'maxlength';

    /**
     * Обязательное поле
     */
    const VALIDATOR_CUSTOM = 'custom';


    public function validatorToFormPart($validator, $value)
    {
        $result = '';
        switch($validator) {
            case self::VALIDATOR_QERUIRED:
                $result = 'required';
                break;
            case self::VALIDATOR_EMAIL:
                $result = 'email';
                break;
        }

        return $result;
    }

}