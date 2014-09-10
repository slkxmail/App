<?php

use \Zend\ServiceManager\FactoryInterface as FactoryInterface;
use \Zend\ServiceManager\ServiceLocatorInterface as ServiceLocatorInterface;

class App_ServiceManager_Service_Db implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');
        return Zend_Db::factory($config['db']['adapter'], $config['db']['params']);
    }
}