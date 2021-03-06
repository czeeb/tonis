<?php
namespace Tonis\Tonis\Factory;

use League\Plates\Engine;
use Tonis\Di\Container;
use Tonis\Tonis\TestAsset\TestPackage\TestPackage;
use Tonis\Package\PackageManager;
use Tonis\View\Strategy\PlatesStrategy;

/**
 * @coversDefaultClass \Tonis\Tonis\Factory\PlatesStrategyFactory
 */
class PlatesStrategyFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__invoke
     */
    public function testInvoke()
    {
        $pm = new PackageManager;
        $pm->add(TestPackage::class);
        $pm->load();

        $di = new Container;
        $di['config'] = [
            'plates' => [
                'folders' => [
                    'foo' => __DIR__ . '/../TestAsset/TestPackage'
                ]
            ]
        ];
        $di->set(PackageManager::class, $pm);

        $factory = new PlatesStrategyFactory();

        $plates = $factory->__invoke($di);

        $this->assertInstanceOf(PlatesStrategy::class, $plates);
        $this->assertInstanceOf(Engine::class, $plates->getEngine());

        $folders = $plates->getEngine()->getFolders();
        $this->assertTrue($folders->exists('foo'));
        $this->assertTrue($folders->exists('test-package'));
    }
}
