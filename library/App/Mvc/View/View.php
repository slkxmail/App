<?php

namespace App\Mvc\View;

use App\ServiceManager\ServiceManager;
use App\Http\Response;
use App\Http\Request;


/**
 * Управление блоками в системе
 *
 * @package App\Mvc\Block
 */
class View extends \ArrayIterator
{
    static $urlBuilder;

    protected $viewPath = array();

    protected $helperPath = array();
    protected $layoutPath = array();

    /**
     * Stack of Zend_View_Filter names to apply as filters.
     * @var array
     */
    private $_filter = array();

    /**
     * Stack of Zend\View\Helper
     * @var array
     */
    private $_helper = array();

    /**
     * @var \App\ServiceManager\ServiceManager
     */
    protected $serviceManager;



    /**
     * @var /App/Http/Response
     */
    protected $response = null;

    /**
     * @var /App/Http/Request
     */
    private $request = null;


    private $param = array();

    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * Установить путь где искать view
     *
     * @param $path
     * @return $this
     */
    public function setViewPath($path)
    {
        if (!is_array($path)) {
            $path = array(realpath($path));
        }

        //$this->viewPath = array();
        foreach ($path as $_path) {
            $this->addViewPath($_path);
        }

        return $this;
    }

    public function setHelperPath($path)
    {
        if (!is_array($path)) {
            $path = array(realpath($path));
        }

        //$this->helperPath = array();
        foreach ($path as $_path) {
            $this->helperPath[] = realpath($_path);
        }

        return $this;
    }

    /**
     * Добавить путь где искать блоки
     *
     * @param $path
     * @return $this
     */
    public function addViewPath($path)
    {
        $this->viewPath[] = realpath($path);
    }

    /**
     * Добавить путь где искать блоки
     *
     * @param $path
     * @return $this
     */
    public function addLayoutPath($path)
    {
        $this->layoutPath[] = realpath($path);
        return $this;
    }


    /**
     * Установить путь где искать layout
     *
     * @param $path
     * @return $this
     */
    public function setLayoutPath($path)
    {
        if (!is_array($path)) {
            $path = array(realpath($path));
        }

        $this->layoutPath = array();
        foreach ($path as $_path) {
            $this->addLayoutPath($_path);
        }

        return $this;
    }

    public function render($viewScript = '')
    {
        $isInclude = false;
        ob_start();
        foreach ($this->viewPath as $path) {
            $_viewScript = $path . '/' . $viewScript;
            if (is_file($_viewScript)) {
                $isInclude = true;
                include $_viewScript;
            }
        }

        $result = $this->_filter(ob_get_clean()); // filter output

        if (!$isInclude) {
            throw new \Exception('Missing view template ' . $viewScript);
        }

        return $result;
    }

    public function renderLayout($layout = null)
    {
        $request = $this->getRequest();
        //$response = $this->getResponse();

        if ($layout === null) {
            $layout = $request->getParam('layout', 'default');
        }
        $isInclude = false;
        ob_start();
        foreach ($this->layoutPath as $path) {
            $_viewScript = $path . '/' . $layout . '.phtml';
            if (is_file($_viewScript)) {
                $isInclude = true;
                include_once $_viewScript;
            }
        }

        $result = $this->_filter(ob_get_clean()); // filter output

        if (!$isInclude) {
            throw new \Exception('Missing layout ' . $layout);
        }
        return $result;
        /*
        $response = $this->getResponse();
        $response->setBody($result);
        return $response;
        */
    }




    /**
     * @return Response
     */
    public function getResponse()
    {
        if (!$this->response) {
            $this->response = $this->getServiceManager()->get('response');
        }

        return new $this->response;
    }


    /**
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }


    /**
     * Get MVC request object
     *
     * @return null|Request
     */
    public function getRequest()
    {
        if (!$this->request) {
            $this->request = $this->getServiceManager()->get('request');
        }

        return $this->request;
    }

    /**
     * @return \App\Mvc\UrlBuilder\UrlBuilder
     */
    public function getUrlBuilder()
    {
        if (!self::$urlBuilder) {
            self::$urlBuilder = $this->getServiceManager()->get('url_builder');
        }

        return self::$urlBuilder;
    }

    /**
     * @param       $route
     * @param array $params
     * @return string
     */
    public function url($route, $params = array())
    {
        return $this->getUrlBuilder()->url($route, $params);
    }

    /**
     * Applies the filter callback to a buffer.
     *
     * @param string $buffer The buffer contents.
     * @return string The filtered buffer.
     */
    private function _filter($buffer)
    {
        // loop through each filter class
        foreach ($this->_filter as $name) {
            // load and apply the filter class
            $filter = $this->getFilter($name);
            $buffer = call_user_func(array($filter, 'filter'), $buffer);
        }

        // done!
        return $buffer;
    }

    /**
     * Get a filter object by name
     *
     * @param  string $name
     * @return object
     */
    public function getFilter($name)
    {
        return $this->_getPlugin('filter', $name);
    }


    public function __get($name)
    {
        if (array_key_exists($name, $this->param)) {
            return $this->param[$name];
        }

        return null;
    }

    public function __set($name, $value)
    {
        $this->param[$name] = $value;
    }


    /**
     * Accesses a helper object from within a script.
     *
     * If the helper class has a 'view' property, sets it with the current view
     * object.
     *
     * @param string $name The helper name.
     * @param array $args The parameters for the helper.
     * @return string The result of the helper output.
     */
    public function __call($name, $args)
    {
        // is the helper already loaded?
        $helper = $this->getHelper($name);
        // call the helper method
        return call_user_func_array(
            array($helper, '__invoke'),
            $args
        );
    }

    /**
     * Получить хелпер
     *
     * @param $name
     * @return \Zend\View\Helper\AbstractHelper
     */
    private function getHelper($name)
    {
        //$helper = $this->loadHelper($name);
        /*
        if(array_key_exists('name', $this->_helper)) {
            return $this->_helper[name];
        }*/
        $helperName = null;
        $name = ucfirst($name);
        //$isInclude = false;
        foreach($this->helperPath as $path) {
            $_helperScript = $path . '/' . $name . '.php';

            if (is_file($_helperScript)) {
                include_once $_helperScript;
            }
            $helperName = $name;
        }

        if (!$helperName || !class_exists($helperName)) {
            $prefix = "\\Zend\\View\\Helper\\";
            $helperName = $prefix . $name;
        }
        /** @var \Zend\View\Helper\AbstractHelper $_helper */
        $_helper = new $helperName;
        $_helper->setView($this);
        $this->_helper[$name] = $_helper ;
        return $this->_helper[$name];
    }
/*
    private function loadHelper()
    {
    }
*/
}
