<?php
namespace Tonis\Tonis\Subscriber;

use Interop\Container\ContainerInterface;
use Tonis\Di\ContainerUtil;
use Tonis\Di\ServiceFactoryInterface;
use Tonis\Dispatcher\Dispatcher;
use Tonis\Event\EventManager;
use Tonis\Event\SubscriberInterface;
use Tonis\Tonis\Exception\InvalidDispatchResultException;
use Tonis\Tonis\LifecycleEvent;
use Tonis\Tonis\Tonis;
use Tonis\Router\RouteCollection;
use Tonis\Router\RouteMatch;
use Tonis\View\ModelInterface;
use Tonis\View\ViewManager;
use Zend\Diactoros\Response;

final class BaseSubscriber implements SubscriberInterface
{
    /** @var ContainerInterface */
    private $di;

    /**
     * @param ContainerInterface $di
     */
    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
    }

    /**
     * @param EventManager $events
     * @return void
     */
    public function subscribe(EventManager $events)
    {
        $events->on(Tonis::EVENT_ROUTE, [$this, 'onRoute']);
        $events->on(Tonis::EVENT_ROUTE_ERROR, [$this, 'onRouteError']);
        $events->on(Tonis::EVENT_DISPATCH, [$this, 'onDispatch'], 1000);

        // This needs to run as the last Dispatch event which detects if the dispatch result is valid
        $events->on(Tonis::EVENT_DISPATCH, [$this, 'onDispatchValidateResult'], -1000);

        $events->on(Tonis::EVENT_RENDER, [$this, 'onRender']);
        $events->on(Tonis::EVENT_RESPOND, [$this, 'onRespond']);

        $events->on(Tonis::EVENT_DISPATCH_EXCEPTION, [$this, 'onDispatchException']);
    }

    public function bootstrapPackageSubscribers()
    {
        /** @var Tonis $tonis */
        $tonis = $this->di->get(Tonis::class);
        $subscribers = $this->di['config']['mvc']['subscribers'];

        foreach ($subscribers as $subscriber) {
            $tonis->events()->subscribe(ContainerUtil::get($this->di, $subscriber));
        }
    }

    /**
     * @param LifecycleEvent $event
     */
    public function onRoute(LifecycleEvent $event)
    {
        $match = $this->di->get(RouteCollection::class)->match($event->getRequest());
        if ($match instanceof RouteMatch) {
            $event->setRouteMatch($match);
        }
    }

    /**
     * @param LifecycleEvent $event
     */
    public function onRouteError(LifecycleEvent $event)
    {
        $event->setResponse($event->getResponse()->withStatus(404));
    }

    /**
     * @param LifecycleEvent $event
     */
    public function onDispatch(LifecycleEvent $event)
    {
        if (null !== $event->getDispatchResult()) {
            return;
        }

        $routeMatch = $event->getRouteMatch();
        if (!$routeMatch instanceof RouteMatch) {
            return;
        }

        $dispatcher = $this->di->get(Dispatcher::class);
        $handler = $routeMatch->getRoute()->getHandler();

        if (is_string($handler) && $this->di->has($handler)) {
            $handler = $this->di->get($handler);
        }

        $result = $dispatcher->dispatch($handler, $routeMatch->getParams());

        $event->setDispatchResult($result);
    }

    /**
     * @param LifecycleEvent $event
     */
    public function onDispatchValidateResult(LifecycleEvent $event)
    {
        $result = $event->getDispatchResult();
        if (!$result instanceof ModelInterface) {
            throw new InvalidDispatchResultException();
        }
    }

    /**
     * @param LifecycleEvent $event
     */
    public function onDispatchException(LifecycleEvent $event)
    {
        $event->setResponse($event->getResponse()->withStatus(500));
    }

    /**
     * @param LifecycleEvent $event
     */
    public function onRender(LifecycleEvent $event)
    {
        if (null !== $event->getRenderResult()) {
            return;
        }

        $vm = $this->di->get(ViewManager::class);
        $event->setRenderResult($vm->render($event->getDispatchResult()));
    }

    public function onRespond(LifecycleEvent $event)
    {
        $response = $event->getResponse() ? $event->getResponse() : new Response;
        $response->getBody()->write($event->getRenderResult());
    }
}
