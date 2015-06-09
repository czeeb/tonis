<?php
namespace Tonis\Mvc\Subscriber;

use Tonis\Event\EventManager;
use Tonis\Mvc\Factory\TonisFactory;
use Tonis\Mvc\LifecycleEvent;
use Tonis\Mvc\TestAsset\NewRequestTrait;
use Tonis\Mvc\TestAsset\TestSubscriber;
use Tonis\Mvc\TestAsset\TestViewModelStrategy;
use Tonis\Mvc\Tonis;
use Tonis\Router\Route;
use Tonis\Router\RouteMatch;
use Tonis\View\Model\StringModel;
use Tonis\View\Strategy\StringStrategy;
use Tonis\View\ViewManager;
use Zend\Diactoros\Response;

/**
 * @coversDefaultClass \Tonis\Mvc\Subscriber\BaseSubscriber
 */
class BaseSubscriberTest extends \PHPUnit_Framework_TestCase
{
    use NewRequestTrait;

    /** @var Tonis */
    private $tonis;
    /** @var BaseSubscriber */
    private $s;

    /**
     * @covers ::__construct
     * @covers ::subscribe
     */
    public function testSubscribe()
    {
        $events = new EventManager();
        $this->s->subscribe($events);

        $this->assertCount(4, $events->getListeners());
        $this->assertCount(1, $events->getListeners(Tonis::EVENT_ROUTE));
        $this->assertCount(2, $events->getListeners(Tonis::EVENT_DISPATCH));
        $this->assertCount(1, $events->getListeners(Tonis::EVENT_RENDER));
        $this->assertCount(1, $events->getListeners(Tonis::EVENT_RESPOND));
    }

    /**
     * @covers ::bootstrapPackageSubscribers
     */
    public function testBootstrapPackageSubscribers()
    {
        $di = $this->tonis->di();
        $di['config'] = ['mvc' => ['subscribers' => [new TestSubscriber()]]];

        $this->s->bootstrapPackageSubscribers();
        $this->assertNotEmpty($this->tonis->events()->getListeners());
    }

    /**
     * @covers ::onRender
     */
    public function testOnRenderWithModel()
    {
        $event = new LifecycleEvent($this->newRequest('/'));
        $event->setDispatchResult(new StringModel('testing'));

        $this->s->onRender($event);
        $this->assertSame('testing', $event->getRenderResult());
    }

    /**
     * @covers ::onRender
     */
    public function testOnRenderReturnsEarlyIfRenderResultIsNotNull()
    {
        $event = new LifecycleEvent($this->newRequest('/'));
        $event->setRenderResult('foo');

        $this->s->onRender($event);
        $this->assertSame('foo', $event->getRenderResult());
    }

    /**
     * @covers ::onRoute
     */
    public function testOnRoute()
    {
        $this->tonis->routes()->get('/', 'foo');

        $event = new LifecycleEvent($this->newRequest('/asdf'));
        $this->s->onRoute($event);
        $this->assertNull($event->getRouteMatch());

        $event = new LifecycleEvent($this->newRequest('/'));
        $this->s->onRoute($event);
        $this->assertInstanceOf(RouteMatch::class, $event->getRouteMatch());
    }

    /**
     * @covers ::onDispatch
     */
    public function testOnDispatchReturnsEarlyWithResult()
    {
        $event = new LifecycleEvent($this->newRequest('/'));
        $event->setDispatchResult('foo');
        $this->s->onDispatch($event);
        $this->assertSame('foo', $event->getDispatchResult());
    }

    /**
     * @covers ::onDispatch
     */
    public function testOnDispatchReturnsEarlyWithNoRouteMatch()
    {
        $event = new LifecycleEvent($this->newRequest('/'));
        $this->s->onDispatch($event);

        $this->assertNull($event->getDispatchResult());
    }

    /**
     * @covers ::onDispatch
     */
    public function testOnDispatch()
    {
        $handler = function () {
            return 'dispatched';
        };

        $event = new LifecycleEvent($this->newRequest('/'));
        $event->setRouteMatch(new RouteMatch(new Route('/', $handler)));

        $this->s->onDispatch($event);
        $this->assertSame('dispatched', $event->getDispatchResult());
    }

    /**
     * @covers ::onDispatch
     */
    public function testOnDispatchHandlesServiceDispatchables()
    {
        $handler = function () {
            return 'dispatched';
        };
        $this->tonis->di()->set('handler', $handler);

        $event = new LifecycleEvent($this->newRequest('/'));
        $event->setRouteMatch(new RouteMatch(new Route('/', 'handler')));

        $this->s->onDispatch($event);
        $this->assertSame('dispatched', $event->getDispatchResult());
    }

    /**
     * @covers ::onDispatchValidateResult
     * @expectedException \Tonis\Mvc\Exception\InvalidDispatchResultException
     */
    public function testOnDispatchValidateResult()
    {
        $event = new LifecycleEvent($this->newRequest('/'));
        $event->setDispatchResult(false);

        $this->s->onDispatchValidateResult($event);
    }

    /**
     * @covers ::onDispatchValidateResult
     */
    public function testOnDispatchValidateResultWithValidResult()
    {
        $event = new LifecycleEvent($this->newRequest('/'));
        $event->setDispatchResult(new StringModel('foo'));
        $this->s->onDispatchValidateResult($event);
    }

    /**
     * @covers ::onRespond
     */
    public function testOnRespond()
    {
        $response = new Response;

        $event = new LifecycleEvent($this->newRequest('/'));
        $event->setResponse($response);
        $event->setRenderResult('response');

        $this->s->onRespond($event);
        $this->assertSame($response, $event->getResponse());
        $this->assertSame('response', (string) $response->getBody());

        $event = new LifecycleEvent($this->newRequest('/'));
        $this->s->onRespond($event);
        $this->assertNotSame($response, $event->getResponse());
        $this->assertInstanceOf(Response::class, $event->getResponse());
        $this->assertSame('response', (string) $response->getBody());
    }

    protected function setUp()
    {
        $this->tonis = (new TonisFactory)->createWeb();
        /** @var \Tonis\Di\Container $di */
        $di = $this->tonis->di();
        $di->wrap(ViewManager::class, function () {
            $vm = new ViewManager();
            $vm->addStrategy(new StringStrategy());
            $vm->addStrategy(new TestViewModelStrategy());

            return $vm;
        });

        $this->tonis->bootstrap();

        $this->s = new BaseSubscriber($this->tonis->di());
    }
}