<?php

namespace App\Form;

use App\Form\Exception\InvalidArgumentException;

class Form extends Fieldset
{
    const METHOD_POST = 'POST';

    const METHOD_GET = 'GET';

    /**
     * Суффикс для декоратора элементов формы
     * @var string
     */
    public $elementDecoratorSuffix;

    /**
     * @var array
     */
    protected $attributes = array('method' => self::METHOD_POST);

    /**
     * Декаратор формы
     * @var null
     */
    private $_decorator = 'default';

    /**
     * URL Builder
     * @var null
     */
    private static $_urlBuilder = null;

    public function setAutocomplete($autocompleteFlag = false)
    {
        return $this->setAttribute('autocomplete', ($autocompleteFlag ? 'on' : 'off'));
    }

    /**
     * @return Element
     */
    public function getAutocomplete()
    {
        return $this->getAttribute('autocomplete');
    }

    /**
     * @param $enctype
     * @return Form
     */
    public function setEnctype($enctype)
    {
        $this->setAttribute('enctype', $enctype);
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getEnctype()
    {
        return $this->getAttribute('enctype');
    }

    /**
     * @param $method
     * @return Form
     */
    public function setMethod($method)
    {
        return $this->setAttribute('method', strtoupper((string)$method));
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->getAttribute('method');
    }
    
    /**
     * @param $name
     * @return Form
     */
    public function setFormName($name)
    {
        $this->setAttribute('formName', (string)$name);
        return $this;
    }

    /**
     * @return string
     */
    public function getFormName()
    {
        return $this->getAttribute('formName');
    }

    /**
     * @param $id
     * @return Form
     */
    public function setId($id)
    {
        $this->setAttribute('id', (string)$id);
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->getAttribute('id');
    }

    /**
     * @param $action
     * @return Form
     */
    public function setAction($action)
    {
        return $this->setAttribute('action', (string)$action);
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->getAttribute('action');
    }

    /**
     * Получить декоратор формы
     * @return String
     */
    public function getDecorator()
    {
        return $this->_decorator;
    }
    /**
     * Получить декоратор формы
     * @return String
     */
    public function setDecorator($decorator)
    {
        $this->_decorator = $decorator;
        return $this;
    }

    public function render($decorator = null)
    {
        if ($decorator) {
            $this->setDecorator($decorator);
        }

        $view = $this->renderTemplate();
        return $view;
    }

    public function toArray()
    {
        $elementArray = array();
        foreach ($this->getIterator() as $element) {
            $elementArray[] = $element->toArray();
        }

        return array_merge(array(
                                'name' => $this->getName(),
                                'action' => $this->getAction(),
                                'elements' => $elementArray,
                                'method' => $this->getMethod()), $this->getAttributes());
    }

    public function iterateElements()
    {
        $result = '';
        $renderElements = array();

        foreach ($this->getIterator() as $element){
            //** @var $element Element * /
            if ($render = $element->render()) {
                $elementName = $element->getName();
                $renderElements[$elementName] = $render;
            }
        }

        foreach ($renderElements as $element) {
            $result .= $element;
        }

        return $result;
    }

    /**
     * @param $decorator
     * @throws InvalidArgumentException
     * @return string
     */
    protected function renderTemplate($decorator = null)
    {
        $isFileExists = false;
        $viewPathArray = $this->getViewPath();
        if (!$decorator) {
            $decorator = $this->getDecorator();
        }
        $filename = $decorator . '.phtml';

        ob_start();
        foreach ($viewPathArray as $viewPath) {
            if (is_file($viewPath . DIRECTORY_SEPARATOR . $filename)) {
                include $viewPath . DIRECTORY_SEPARATOR . $filename;
                $isFileExists = true;
            }
        }

        $result = ob_get_clean(); // filter output

        if (!$isFileExists) {
            throw new InvalidArgumentException('Decorator ' . $decorator . ' not found in path');
        }

        return $result;

    }

    /**
     * Задать суффикс для элементов формы
     * @param $suffix String
     */
    public function setElementDecoratorSuffix($suffix)
    {
        $this->elementDecoratorSuffix = $suffix;
    }

    /**
     * Получить суффикс для элементов формы
     * @param $suffix String
     */
    public function getElementDecoratorSuffix()
    {
        return $this->elementDecoratorSuffix;
    }

    /**
     * Retrieve all a elements filtered value
     *
     * @return array
     */
    public function isValid()
    {
        $isValid = true;

        foreach ($this->getIterator() as $element) {
            if (!$element->isValid()) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * @return null
     */
    public function getUrlBuilder()
    {
        return self::$_urlBuilder;
    }

    /**
     * @param null $urlBuilder
     */
    public static function setUrlBuilder($urlBuilder)
    {
        self::$_urlBuilder = $urlBuilder;
    }


    public function getScriptValidator()
    {
        $validateStr = '';
        $formRules = '';
        $formMess = '';
        $formRulesEnd = '';
        $formMessEnd = '';
        foreach ($this->getIterator() as $element){
            //** @var $element \App\Form\Element * /
            $ruleLine = "";
            $messLine = "";
            $validatorArray = $element->getValidator();
            if (!empty($validatorArray)) {
                $formRules .= "{$formRulesEnd}\n\t{$element->getName()}: {";
                if (array_key_exists('rules', $validatorArray) && !empty($validatorArray['rules'])) {
                    $endLine = '';
                    foreach ($validatorArray['rules'] as $key => $val) {
                        $ruleLine .= "{$endLine}\n\t\t{$key}:{$val}";
                        $endLine = ',';
                    }
                }
                $formRules .= "{$ruleLine}\n\t}";

                $formMess .= "{$formMessEnd}\n\t{$element->getName()}: {";
                if (array_key_exists('messages', $validatorArray) && !empty($validatorArray['messages'])) {
                    $endLine = '';
                    foreach ($validatorArray['messages'] as $key => $val) {
                        $messLine .= "{$endLine}\n\t\t{$key}:'{$val}'";
                        $endLine = ',';
                    }
                }
                $formMess .= "{$messLine}\n\t}";
            $formRulesEnd = ',';
            $formMessEnd = ',';
            }
        }

        $fullRule = '';
        if(!empty($formRules)) {
    $fullRule = <<<A
        rules: {
            {$formRules}
        }
A;
        }

        $fullMess = '';
        if(!empty($formMess)) {
    $fullMess = <<<A
,
        messages: {
            {$formMess}
        }
A;
    }

        $allStr = '' . $fullRule;
        if (!empty($fullMess)) {
            $allStr .= $fullMess;
        }
        $result = <<<EOF
<script>
    $(document).ready(function() {

        $("#{$this->getId()}").validate({
            {$allStr}
        });
    });
</script>
EOF;
        return $result;
    }

}
