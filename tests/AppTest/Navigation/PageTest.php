<?php

namespace AppTest\Navigation;

use AppTest\Navigation\TestCase as ParentTestCase;
use App\Navigation\Page;
use App\Mvc\UrlBuilder\UrlBuilder;


class PageTest extends ParentTestCase
{
    protected $routes = array(
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

    );

    public function testInstance()
    {
        $page = new Page(array('href' => '/test/'));
        $this->assertInstanceOf('App\Navigation\Page', $page);
    }

    public function testGettersAndSetters()
    {
        $page = new Page(array('href' => '/test/'));
        $this->assertEquals('/test/', $page->get('href'));
        $this->assertEquals('/test/', $page->href);
        $this->assertEquals('/test/', $page->getHref());
    }

    public function testParent()
    {
        $page = new Page(array('href' => '/test/'));
        $pageParent = new Page(array('href' => '/test_parent/'));

        $page->setParent($pageParent);

        $this->assertEquals('/test_parent/', $page->getParent()->getHref());
    }

    public function testUrlBuilder()
    {
        $page = new Page(array('route' => 'index'));

        $urlBuilder = new UrlBuilder($this->routes);
        $page->setUrlBuilder($urlBuilder);

        $this->assertEquals('/', $page->getHref());

        $page = new Page();
        $page2 = new Page(array('route' => 'page', 'route_params' => array('slug' => 'about')));
        $page2->setParent($page);

        $urlBuilder = new UrlBuilder($this->routes);
        $page->setUrlBuilder($urlBuilder);

        $this->assertEquals('/page/about/', $page2->getHref());
    }

    public function testOrder()
    {
        $page = new Page();
        $page1 = new Page(array('href' => '/test/', 'order' => 99));
        $page2 = new Page(array('href' => '/test_parent/', 'order' => 1));

        $page->addPages(array($page1, $page2));

        foreach ($page as $p) {
            $this->assertEquals(1, $p->getOrder());
            break;
        }
    }
}