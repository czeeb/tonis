<?php
namespace Tonis\Mvc\Factory;

use Tonis\Di\Container;
use Tonis\Mvc\TestAsset\TestPackage\TestPackage;
use Tonis\Package\PackageManager;

/**
 * @coversDefaultClass \Tonis\Mvc\Factory\PackageManagerFactory
 */
class PackageManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::createService
     */
    public function testCreateService()
    {
        $di = new Container;
        $factory = new PackageManagerFactory(true, [TestPackage::class]);
        $pm = $factory->createService($di);

        $this->assertInstanceOf(PackageManager::class, $pm);
        $this->assertCount(2, $pm->getPackages());
    }

    /**
     * @covers ::createService
     */
    public function testCreateServiceSkipsDebugPackages()
    {
        $di = new Container;
        $factory = new PackageManagerFactory(false, ['?doNotLoad']);
        $pm = $factory->createService($di);

        $this->assertInstanceOf(PackageManager::class, $pm);
        $this->assertCount(1, $pm->getPackages());

        $di = new Container();
        $factory = new PackageManagerFactory(true, ['?' . TestPackage::class]);
        $pm = $factory->createService($di);

        $this->assertInstanceOf(PackageManager::class, $pm);
        $this->assertCount(2, $pm->getPackages());
    }
}