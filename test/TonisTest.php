<?php
namespace Tonis\Tonis;

use Psr\Http\Message\ResponseInterface;
use Tonis\Di\Container;
use Tonis\Event\EventManager;
use Tonis\Tonis\Factory\TonisFactory;
use Tonis\Tonis\TestAsset\NewRequestTrait;
use Tonis\Tonis\TestAsset\TestPackage\TestPackage;
use Tonis\Package\PackageManager;
use Tonis\Router\Route;
use Tonis\Router\RouteCollection;
use Tonis\Router\RouteMatch;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * @coversDefaultClass \Tonis\Tonis\Tonis
 */
class TonisTest extends \PHPUnit_Framework_TestCase
{
    use NewRequestTrait;

    /** @var Tonis */
    private $tonis;

    /**
     * @covers ::__invoke
     */
    public function testInvokeProxiesToRun()
    {
        $request = ServerRequestFactory::fromGlobals();
        $response = new Response;
        $this->assertSame($this->tonis->run($request, $response), $this->tonis->__invoke($request, $response));
    }

    /**
     * @covers ::run
     */
    public function testRun()
    {
        $request = ServerRequestFactory::fromGlobals();

        $tonis = (new TonisFactory)->createTonisInstance();
        $tonis->run($request);

        $event = $tonis->getLifecycleEvent();
        $this->assertSame($request, $event->getRequest());

        $response = new Response;
        $tonis->run($request, $response);

        $event = $tonis->getLifecycleEvent();
        $this->assertSame($request, $event->getRequest());
        $this->assertSame($response, $event->getResponse());
    }

    /**
     * @covers ::respond
     */
    public function testRespond()
    {
        $response = new Response;

        $this->tonis->events()->on(Tonis::EVENT_RESPOND, function (LifecycleEvent $event) use ($response) {
            $response->getBody()->write('foobar');
            $event->setResponse($response);
        });

        $this->tonis->route();
        $this->tonis->respond();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('foobar', (string) $response->getBody());
    }

    /**
     * @covers ::events
     */
    public function testEvents()
    {
        $this->assertInstanceOf(EventManager::class, $this->tonis->events());
    }

    /**
     * @covers ::__construct
     * @covers ::bootstrap
     * @covers ::bootstrapPackages
     */
    public function testBootstrap()
    {
        $tonis = (new TonisFactory)->createTonisInstance();
        $count = 0;
        $tonis->events()->on(Tonis::EVENT_BOOTSTRAP, function () use (&$count) {
            $count++;
        });

        $tonis->bootstrap();
        $this->assertSame(1, $count);

        $tonis->bootstrap();
        $this->assertSame(1, $count);
    }

    /**
     * @covers ::dispatch
     * @covers ::tryFire
     */
    public function testDispatch()
    {
        $dispatch = false;
        $this->tonis->events()->on(Tonis::EVENT_DISPATCH, function () use (&$dispatch) {
            $dispatch = true;
        });

        $this->tonis->dispatch();
        $this->assertTrue($dispatch);
    }

    /**
     * @covers ::dispatch
     * @covers ::tryFire
     */
    public function testDispatchCatchesExceptions()
    {
        $this->tonis->events()->on(Tonis::EVENT_DISPATCH, function () {
            throw new \RuntimeException();
        });

        $this->tonis->route();
        $this->tonis->dispatch();

        $this->assertNotNull($this->tonis->getLifecycleEvent()->getException());
    }

    /**
     * @covers ::render
     * @covers ::tryFire
     */
    public function testRender()
    {
        $render = false;
        $this->tonis->events()->on(Tonis::EVENT_RENDER, function () use (&$render) {
            $render = true;
        });

        $this->tonis->render();

        $this->assertTrue($render);
    }

    /**
     * @covers ::render
     * @covers ::tryFire
     */
    public function testRenderCatchesExceptions()
    {
        $this->tonis->events()->on(Tonis::EVENT_RENDER, function () {
            throw new \RuntimeException();
        });

        $this->tonis->route();
        $this->tonis->render();

        $this->assertNotNull($this->tonis->getLifecycleEvent()->getException());
    }

    /**
     * @covers ::__construct
     * @covers ::route
     */
    public function testRoute()
    {
        $error = false;
        $this->tonis->events()->on(Tonis::EVENT_ROUTE, function (LifecycleEvent $event) use (&$route) {
            $event->setRouteMatch(new RouteMatch(new Route('/', 'handler')));
        });
        $this->tonis->events()->on(Tonis::EVENT_ROUTE_ERROR, function () use (&$error) {
            $error = true;
        });

        $this->tonis->route($this->newRequest('/'));

        $this->assertFalse($error);
        $this->assertInstanceOf(RouteMatch::class, $this->tonis->getLifecycleEvent()->getRouteMatch());
    }

    /**
     * @covers ::route
     */
    public function testRouteError()
    {
        $error = false;
        $this->tonis->events()->on(Tonis::EVENT_ROUTE_ERROR, function () use (&$error) {
            $error = true;
        });

        $this->tonis->route($this->newRequest('/'));

        $this->assertTrue($error);
    }

    /**
     * @covers ::isDebugEnabled
     */
    public function testIsDebugEnabled()
    {
        $this->assertFalse($this->tonis->isDebugEnabled());

        $tonis = (new TonisFactory)->createWeb(['debug' => true]);
        $this->assertTrue($tonis->isDebugEnabled());
    }

    /**
     * @covers ::getConfig
     */
    public function testGetConfig()
    {
        $this->assertInstanceOf(TonisConfig::class, $this->tonis->getConfig());
    }

    /**
     * @covers ::getPackageManager
     */
    public function testGetPackageManager()
    {
        $this->assertInstanceOf(PackageManager::class, $this->tonis->getPackageManager());
    }

    /**
     * @covers ::di
     */
    public function testDi()
    {
        $this->assertInstanceOf(Container::class, $this->tonis->di());
    }

    /**
     * @covers ::routes
     */
    public function testRoutes()
    {
        $this->assertInstanceOf(RouteCollection::class, $this->tonis->routes());
    }

    /**
     * @covers ::getLifecycleEvent
     */
    public function testGetLifecycleEvent()
    {
        $this->assertNull($this->tonis->getLifecycleEvent());
        $this->tonis->route();
        $this->assertInstanceOf(LifecycleEvent::class, $this->tonis->getLifecycleEvent());
    }

    /**
     * @covers ::bootstrapEnvironment
     * @runInSeparateProcess
     */
    public function testBootstrapEnvironment()
    {
        $tonis = (new TonisFactory)->createTonisInstance(['environment' => ['TONIS_ENV_TEST' => 'bar']]);
        $tonis->bootstrap();

        $this->assertSame('bar', getenv('TONIS_ENV_TEST'));
    }

    /**
     * @covers ::bootstrapEnvironment
     * @expectedException \Tonis\Tonis\Exception\MissingRequiredEnvironmentException
     * @expectedExceptionMessage The environment variable "TONIS_ENV_TEST" is missing but is set as required
     */
    public function testMissingEnvironmentThrowsException()
    {
        $tonis = (new TonisFactory)->createTonisInstance(['required_environment' => ['TONIS_ENV_TEST']]);
        $tonis->bootstrap();
    }

    protected function setUp()
    {
        $this->tonis = (new TonisFactory)->createTonisInstance(['packages' => [TestPackage::class]]);
        $this->tonis->bootstrap();
    }
}
