<?php

namespace AppTest\Mvc\Block;

use App\Mvc\Block\Manager;
use AppTest\TestCase as ParentTestCase;


class ManagerTest extends ParentTestCase
{

    public function testCreateBlockFromXml()
    {
        $blockFilesPath = $testBlockFile = FIXTURES_PATH . '/Mvc/Block/';

        $manager = new Manager();
        $manager->setBlockPath($blockFilesPath);
        $manager->setCachePath($blockFilesPath);

        $block = $manager->getBlock('test_block');

        $this->assertInstanceOf('App\Mvc\Block\Block', $block);
        $this->assertEquals('sidebar',  $block->getName());
        $this->assertEquals(null,       $block->getPos());
        $this->assertEquals('Side bar', $block->getLabel());
        $this->assertInternalType('boolean', $block->getShow());
        $this->assertEquals(true, $block->getShow());

        $manager->setAllowCache(true);
        $manager->clearCache();
        $block = $manager->getBlock('test_block');
        $this->assertInstanceOf('App\Mvc\Block\Block', $block);
        $this->assertEquals('sidebar',  $block->getName());

        $block = $manager->getBlock('test_block');
        $this->assertInstanceOf('App\Mvc\Block\Block', $block);
        $this->assertEquals('sidebar',  $block->getName());
        $this->assertEquals(null,       $block->getPos());
        $this->assertEquals('Side bar', $block->getLabel());
        $this->assertInternalType('boolean', $block->getShow());
        $this->assertEquals(true, $block->getShow());
    }


    /**
     * @expectedException \App\Exception\InvalidArgumentException
     */
    public function testCacheOptions()
    {
        $blockFilesPath = $testBlockFile = FIXTURES_PATH . '/Mvc/Block/';

        $manager = new Manager();
        $manager->setBlockPath($blockFilesPath);
        $manager->setCachePath($blockFilesPath);

        $manager->setCachePath('unexists');
    }


    public function testGetParam()
    {

    }
}
