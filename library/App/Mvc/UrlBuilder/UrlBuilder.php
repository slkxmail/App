<?php

namespace App\Mvc\UrlBuilder;

use App\Exception\InvalidArgumentException;

class UrlBuilder implements UrlBuilderInterface
{
    private $routeList = array();

    public function __construct(array $routes)
    {
        $this->routeList = $routes;
    }

    public function url($route, $params = array())
    {
        $route = (string)$route;

        if (!isset($this->routeList[$route])) {
            throw new InvalidArgumentException('Route ' . $route . ' not found');
        }
        $defaults = $this->routeList[$route]['defaults'];

        if (!array_key_exists('spec', $this->routeList[$route]) || !$this->routeList[$route]['spec']) {
            $spec = '/' . $this->routeList[$route]['route'];
        } else {
            $spec = $this->routeList[$route]['spec'];
        }

        $url                   = $spec;
        $mergedParams          = array_merge($defaults, $params);

        foreach ($mergedParams as $key => $value) {
            $spec = '%' . $key . '%';

            if (strpos($url, $spec) !== false) {
                $url = str_replace($spec, rawurlencode($value), $url);
            }
        }
        // Пустой параемтер не выводим
        $url = preg_replace('#[\/]+\%[^%\/]+\%#is','',$url);
        // Удаляем двойные слеши
        $url = preg_replace('#[\/]+#is','/',$url);

        return $url;

    }
}