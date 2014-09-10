<?php

namespace App\Measure;

class Weight
{
    const STANDART = 'KILOGRAM';

    const LB = 'LB';
    const KILOGRAM = 'KILOGRAM';
    const POUND    = 'POUND';

    /**
     * @var float
     */
    private $value = 0;

    /**
     * @var string
     */
    private $type;

    private static $bcmathDisabled;

    /**
     * Calculations for all weight units
     *
     * @var array
     */
    protected $_units = array(
        'FUNT'                  => array('0.4095',         'funt'),
        'GRAM'                  => array('0.001',          'g'),
        'KILOGRAM'              => array('1',              'kg'),
        'LB'                    => array('0.45359237',     'lb'),
        'LBS'                   => array('0.45359237',     'lbs'),
        'METRIC_TON'            => array('1000',           't'),
        'OUNCE'                 => array(array('' => '0.45359237', '/' => '16'),    'oz'),
        'POUND'                 => array('0.45359237',     'lb'),
        'PUD'                   => array('16.3',           'pud'),
        'POOD'                  => array('16.3',           'pood'),
        'TON'                   => array('1000',           't'),
        'UNCIA'                 => array('0.0272875',      'uncia'),
        'STANDARD'              => 'KILOGRAM'
    );

    public function __construct($value, $type = null, $locale = null)
    {
        if ($type === null) {
            $type = $this->_units['STANDARD'];
        }

        if (isset($this->_units[$type]) === false) {
            throw new \App\Exception\ErrorException("Type ($type) is unknown");
        }

        if (self::$bcmathDisabled == null) {
            self::$bcmathDisabled = !extension_loaded('bcmath');
        }

        $this->setValue($value, $type);
    }

    /**
     * Set a new value
     *
     * @param  integer|string      $value   Value as string, integer, real or float
     * @param  string              $type    OPTIONAL A measure type f.e. Zend_Measure_Length::METER
     * @throws \App\Exception\ErrorException
     */
    public function setValue($value, $type = null)
    {
        if ($type === null) {
            $type = $this->_units['STANDARD'];
        }

        if (empty($this->_units[$type])) {
            throw new \App\Exception\ErrorException("Type ($type) is unknown");
        }

        $this->value = floatval($value);
        $this->setType($type);
        return $this;
    }

    /**
     *
     * @return float|int
     */
    public function getValue()
    {
        return $this->value;
    }


    /**
     * Set a new type, and convert the value
     *
     * @param  string $type New type to set
     * @throws \App\Exception\ErrorException
     * @return \App\Measure\Weight
     */
    public function setType($type)
    {
        if (empty($this->_units[$type])) {
            throw new \App\Exception\ErrorException("Type ($type) is unknown");
        }

        if (empty($this->type)) {
            $this->type = $type;
        } else {
            // Convert to standard value
            $value = $this->value;
            if (is_array($this->_units[$this->getType()][0])) {
                foreach ($this->_units[$this->getType()][0] as $key => $found) {
                    switch ($key) {
                        case "/":
                            if ($found != 0) {
                                $value = call_user_func(array($this, 'Div'), $value, $found, 25);
                            }
                            break;
                        case "+":
                            $value = call_user_func(array($this, 'Add'), $value, $found, 25);
                            break;
                        case "-":
                            $value = call_user_func(array($this, 'Sub'), $value, $found, 25);
                            break;
                        default:
                            $value = call_user_func(array($this, 'Mul'), $value, $found, 25);
                            break;
                    }
                }
            } else {
                $value = call_user_func(array($this, 'Mul'), $value, $this->_units[$this->getType()][0], 25);
            }

            // Convert to expected value
            if (is_array($this->_units[$type][0])) {
                foreach (array_reverse($this->_units[$type][0]) as $key => $found) {
                    switch ($key) {
                        case "/":
                            $value = call_user_func(array($this, 'Mul'), $value, $found, 25);
                            break;
                        case "+":
                            $value = call_user_func(array($this, 'Sub'), $value, $found, 25);
                            break;
                        case "-":
                            $value = call_user_func(array($this, 'Add'), $value, $found, 25);
                            break;
                        default:
                            if ($found != 0) {
                                $value = call_user_func(array($this, 'Div'), $value, $found, 25);
                            }
                            break;
                    }
                }
            } else {
                $value = call_user_func(array($this, 'Div'), $value, $this->_units[$type][0], 25);
            }

            $slength = strlen($value);
            $length  = 0;
            for($i = 1; $i <= $slength; ++$i) {
                if ($value[$slength - $i] != '0') {
                    $length = 26 - $i;
                    break;
                }
            }

            $this->value = $this->round($value, $length);
            $this->type  = $type;
        }
        return $this;
    }

    /**
     * Changes exponential numbers to plain string numbers
     * Fixes a problem of BCMath with numbers containing exponents
     *
     * @param integer $value Value to erase the exponent
     * @param integer $scale (Optional) Scale to use
     * @return string
     */
    protected function exponent($value, $scale = null)
    {
        if (!extension_loaded('bcmath')) {
            return $value;
        }

        $split = explode('e', $value);
        if (count($split) == 1) {
            $split = explode('E', $value);
        }

        if (count($split) > 1) {
            $value = bcmul($split[0], bcpow(10, $split[1], $scale), $scale);
        }

        return $value;
    }

    /**
     * BCAdd - fixes a problem of BCMath and exponential numbers
     *
     * @param  string  $op1
     * @param  string  $op2
     * @param  integer $scale
     * @return string
     */
    protected function Add($op1, $op2, $scale = null)
    {
        $op1 = $this->exponent($op1, $scale);
        $op2 = $this->exponent($op2, $scale);

        return bcadd($op1, $op2, $scale);
    }

    /**
     * BCSub - fixes a problem of BCMath and exponential numbers
     *
     * @param  string  $op1
     * @param  string  $op2
     * @param  integer $scale
     * @return string
     */
    protected function Sub($op1, $op2, $scale = null)
    {
        $op1 = $this->exponent($op1, $scale);
        $op2 = $this->exponent($op2, $scale);
        return bcsub($op1, $op2, $scale);
    }

    /**
     * BCMul - fixes a problem of BCMath and exponential numbers
     *
     * @param  string  $op1
     * @param  string  $op2
     * @param  integer $scale
     * @return string
     */
    protected function Mul($op1, $op2, $scale = null)
    {
        $op1 = $this->exponent($op1, $scale);
        $op2 = $this->exponent($op2, $scale);
        return bcmul($op1, $op2, $scale);
    }

    /**
     * BCDiv - fixes a problem of BCMath and exponential numbers
     *
     * @param  string  $op1
     * @param  string  $op2
     * @param  integer $scale
     * @return string
     */
    protected function Div($op1, $op2, $scale = null)
    {
        $op1 = $this->exponent($op1, $scale);
        $op2 = $this->exponent($op2, $scale);
        return bcdiv($op1, $op2, $scale);
    }

    /**
     * Surprisingly, the results of this implementation of round()
     * prove better than the native PHP round(). For example, try:
     *   round(639.795, 2);
     *   round(267.835, 2);
     *   round(0.302515, 5);
     *   round(0.36665, 4);
     * then try:
     *   Zend_Locale_Math::round('639.795', 2);
     */
    protected function round($op1, $precision = 0)
    {
        if (self::$bcmathDisabled) {
            $op1 = round($op1, $precision);
            if (strpos((string) $op1, 'E') === false) {
                return $this->normalize(round($op1, $precision));
            }
        }

        if (strpos($op1, 'E') !== false) {
            $op1 = self::floatalize($op1);
        }

        $op1    = trim($this->normalize($op1));
        $length = strlen($op1);
        if (($decPos = strpos($op1, '.')) === false) {
            $op1 .= '.0';
            $decPos = $length;
            $length += 2;
        }
        if ($precision < 0 && abs($precision) > $decPos) {
            return '0';
        }

        $digitsBeforeDot = $length - ($decPos + 1);
        if ($precision >= ($length - ($decPos + 1))) {
            return $op1;
        }

        if ($precision === 0) {
            $triggerPos = 1;
            $roundPos   = -1;
        } elseif ($precision > 0) {
            $triggerPos = $precision + 1;
            $roundPos   = $precision;
        } else {
            $triggerPos = $precision;
            $roundPos   = $precision -1;
        }

        $triggerDigit = $op1[$triggerPos + $decPos];
        if ($precision < 0) {
            // zero fill digits to the left of the decimal place
            $op1 = substr($op1, 0, $decPos + $precision) . str_pad('', abs($precision), '0');
        }

        if ($triggerDigit >= '5') {
            if ($roundPos + $decPos == -1) {
                return str_pad('1', $decPos + 1, '0');
            }

            $roundUp = str_pad('', $length, '0');
            $roundUp[$decPos] = '.';
            $roundUp[$roundPos + $decPos] = '1';

            if ($op1 > 0) {
                if (self::$bcmathDisabled) {
                    return $this->Add($op1, $roundUp, $precision);
                }
                return self::Add($op1, $roundUp, $precision);
            } else {
                if (self::$bcmathDisabled) {
                    return $this->Sub($op1, $roundUp, $precision);
                }
                return self::Sub($op1, $roundUp, $precision);
            }
        } elseif ($precision >= 0) {
            return substr($op1, 0, $decPos + ($precision ? $precision + 1: 0));
        }

        return (string) $op1;
    }

    /**
     * Normalizes an input to standard english notation
     * Fixes a problem of BCMath with setLocale which is PHP related
     *
     * @param   integer  $value  Value to normalize
     * @return  string           Normalized string without BCMath problems
     */
    protected function normalize($value)
    {
        $convert = localeconv();
        $value = str_replace($convert['thousands_sep'], "", (string) $value);
        $value = str_replace($convert['positive_sign'], "", $value);
        $value = str_replace($convert['decimal_point'], ".",$value);
        if (!empty($convert['negative_sign']) and (strpos($value, $convert['negative_sign']))) {
            $value = str_replace($convert['negative_sign'], "", $value);
            $value = "-" . $value;
        }

        return $value;
    }

    /**
     * Convert a scientific notation to float
     * Additionally fixed a problem with PHP <= 5.2.x with big integers
     *
     * @param string $value
     * @return float
     */
    protected function floatalize($value)
    {
        $value = strtoupper($value);
        if (strpos($value, 'E') === false) {
            return $value;
        }

        $number = substr($value, 0, strpos($value, 'E'));
        if (strpos($number, '.') !== false) {
            $post   = strlen(substr($number, strpos($number, '.') + 1));
            $mantis = substr($value, strpos($value, 'E') + 1);
            if ($mantis < 0) {
                $post += abs((int) $mantis);
            }

            $value = number_format($value, $post, '.', '');
        } else {
            $value = number_format($value, 0, '.', '');
        }

        return (float)$value;
    }

    /**
     *
     * @return float|int
     */
    public function getType()
    {
        return $this->type;
    }

}

