<?php

namespace App\Mvc\UrlBuilder;

interface UrlBuilderInterface
{
    public function url($route, $params = array());
}