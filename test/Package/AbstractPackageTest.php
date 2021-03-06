<?php
namespace Tonis\Tonis\Package;

use Tonis\Di\Container;
use Tonis\Tonis\Factory\TonisFactory;
use Tonis\Tonis\TestAsset\PlainPackage;
use Tonis\Tonis\TestAsset\TestPackage\TestPackage;
use Tonis\Tonis\TestAsset\TestPackageWithNoConfigs;
use Tonis\Router\RouteCollection;

/**
 * @coversDefaultClass \Tonis\Tonis\Package\AbstractPackage
 */
class AbstractPackageTest extends \PHPUnit_Framework_TestCase
{
    /** @var TestPackage */
    private $package;

    /**
     * @covers ::getConfig
     */
    public function testGetConfig()
    {
        $package = new TestPackageWithNoConfigs();
        $this->assertSame([], $package->getConfig());

        $package = new TestPackage();
        $this->assertSame(include __DIR__ . '/../TestAsset/TestPackage/config/package.php', $package->getConfig());
    }

    /**
     * @covers ::getPath
     */
    public function testGetPath()
    {
        $package = new PlainPackage();
        $this->assertSame(realpath(__DIR__ . '/../'), $package->getPath());
        $this->assertSame(realpath(__DIR__ . '/../'), $package->getPath());
    }

    /**
     * @covers ::getNamespace
     */
    public function testGetNamespace()
    {
        $this->assertSame('Tonis\Tonis\TestAsset\TestPackage', $this->package->getNamespace());
        $this->assertSame('Tonis\Tonis\TestAsset\TestPackage', $this->package->getNamespace());
    }

    /**
     * @covers ::getName
     */
    public function testGetName()
    {
        $this->assertSame('test-package', $this->package->getName());
        $this->assertSame('test-package', $this->package->getName());
    }

    /**
     * @covers ::bootstrap
     */
    public function testBootstrap()
    {
        $factory = new TonisFactory;
        $this->assertNull($this->package->bootstrap($factory->createWeb()));
    }

    /**
     * @covers ::bootstrapConsole
     */
    public function testBootstrapConsole()
    {
        $factory = new TonisFactory;
        $this->assertNull($this->package->bootstrapConsole($factory->createConsole([])));
    }

    /**
     * @covers ::configureRoutes
     */
    public function testConfigureRoutes()
    {
        $package = new PlainPackage;
        $routes = new RouteCollection;
        $package->configureRoutes($routes);

        $this->assertEmpty($routes->getRoutes());
    }

    /**
     * @covers ::configureServices
     */
    public function testConfigureServices()
    {
        $package = new PlainPackage;
        $di = new Container;
        $package->configureServices($di);
    }

    protected function setUp()
    {
        $this->package = new TestPackage();
    }
}
