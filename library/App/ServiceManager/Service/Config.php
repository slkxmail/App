<?php

namespace App\ServiceManager\Service;

use \Zend\ServiceManager\FactoryInterface as FactoryInterface;
use \Zend\ServiceManager\ServiceLocatorInterface as ServiceLocatorInterface;

class Config implements FactoryInterface
{
    /**
     * @var array
     */
    private static $config = array();

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return self::$config;
    }

    public static function setConfig(array $config = array())
    {
        self::$config = $config;
    }
}