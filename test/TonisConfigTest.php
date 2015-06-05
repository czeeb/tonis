<?php
namespace Tonis\Mvc;

/**
 * @coversDefaultClass \Tonis\Mvc\TonisConfig
 */
class TonisConfigTest extends \PHPUnit_Framework_TestCase
{
    /** @var TonisConfig */
    private $config;

    /**
     * @covers ::__construct
     * @covers ::isDebugEnabled
     */
    public function testIsDebugEnabled()
    {
        $this->assertFalse($this->config->isDebugEnabled());
    }

    /**
     * @covers ::getEnvironment
     */
    public function testGetEnvironment()
    {
        $this->assertEmpty($this->config->getEnvironment());
    }

    /**
     * @covers ::getSubscribers
     */
    public function testGetSubscribers()
    {
        $this->assertCount(3, $this->config->getSubscribers());
    }

    /**
     * @covers ::getPackages
     */
    public function testGetPackages()
    {
        $this->assertEmpty($this->config->getPackages());
    }

    protected function setUp()
    {
        $this->config = new TonisConfig;
    }
}