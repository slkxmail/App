<?php

namespace AppTest\Mvc\UrlBuilder;


use App\Mvc\UrlBuilder\UrlBuilder;

class UrlBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException App\Exception\InvalidArgumentException
     */
    public function testUrl()
    {
        $urlBuilder = new UrlBuilder(
            array(
                'index' => array(
                    'route' => '',
                    'map' => array(),
                    'defaults' => array(
                        'controller' => 'index',
                        'action' => 'index'
                    ),
                    'spec' => '/'
                ),

                'page' => array(
                    'route' => '/page/(.*?)/',
                    'map' => array(
                        1 => 'slug'
                    ),
                    'defaults' => array(
                        'layout' => 'page',
                        'controller' => 'page',
                        'action' => 'index'
                    ),
                    'spec' => '/page/%slug%/'
                ),

                'test' => array(
                    'route' => '(news|about)',
                    'map' => array(
                        1 => 'type'
                    ),
                    'defaults' => array(
                        'types' => 'about',
                        'controller' => 'index',
                        'action' => 'index'
                    )
                )

            )
        );

        $this->assertEquals('/', $urlBuilder->url('index'));
        $this->assertEquals('/page/about/', $urlBuilder->url('page', array('slug' => 'about')));

        // Это правильно!
        $this->assertEquals('/page/%slug%/', $urlBuilder->url('page', array('unknown' => 'about')));
        $urlBuilder->url('unknown');
    }

}
