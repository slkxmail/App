<?php

namespace App\Mvc;

class Viewold// extends \Blitz
{
    /**
     * @var array
     */
    private static $viewPathList = array();



    public function __construct($template = null, $viewPath = null)
    {
        if ($viewPath) {
            $this->setViewPath($viewPath);
        }

        parent::__construct($template);
    }

    public function _($string)
    {
        return $string;
    }

    public function setViewPath($path)
    {
        ini_set('blitz.path', realpath($path) . '/');
    }

    /**
     * @param       $filename
     * @param array $vars
     * @return string
     */
    public function includeTpl($filename, $vars = array())
    {
        if (!is_array($vars)) {
            $vars = (array)$vars;
        }

        if ($filename[0] != '/') {
            foreach ($this->getViewPath() as $viewPath) {
                $fullFilename = $viewPath . DIRECTORY_SEPARATOR . $filename;

                if (is_file($fullFilename)) {
                    return $this->include($fullFilename, $vars);
                }
            }
        } else {
            return $this->include($filename, $vars);
        }

        return '';
    }

    /**
     * @param       $string
     * @param array $vars
     * @return string
     */
    public function renderString($string, $vars = array())
    {
        $this->load($string);
        return $this->parse($vars);
    }

    /**
     * @param $viewPath
     */
    public function addViewPath($viewPath)
    {
        self::$viewPathList[] = realpath($viewPath);
    }

    /**
     * @return array
     */
    public function getViewPath()
    {
        return self::$viewPathList;
    }

    /**
     * @param      $var
     * @param null $value
     * @return View
     */
    public function assign($var, $value = null)
    {
        if (!is_array($var)) {
            $var = array($var => $value);
        }

        $this->set($var);
        return $this;
    }
}