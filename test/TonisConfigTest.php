<?php
namespace Tonis\Tonis;

use Tonis\Tonis\TestAsset\TestPackage\TestPackage;
use Tonis\Tonis\TestAsset\TestSubscriber;

/**
 * @coversDefaultClass \Tonis\Tonis\TonisConfig
 */
class TonisConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::__construct
     * @covers ::isDebugEnabled
     * @covers ::getCacheDir
     * @covers ::getEnvironment
     * @covers ::getRequiredEnvironment
     * @covers ::getPackages
     * @covers ::getSubscribers
     * @dataProvider configProvider
     */
    public function testGetters($key, $method, $value)
    {
        $config = new TonisConfig([$key => $value]);
        $this->assertSame($value, $config->{$method}());
    }

    public function configProvider()
    {
        return [
            ['environment', 'getEnvironment', ['FOO' => 'bar']],
            ['required_environment', 'getRequiredEnvironment', ['FOO']],
            ['debug', 'isDebugEnabled', true],
            ['cache_dir', 'getCacheDir', 'cache'],
            ['packages', 'getPackages', [TestPackage::class]],
            ['subscribers', 'getSubscribers', [TestSubscriber::class]],
        ];
    }
}
