<?php

namespace Tonis\Mvc;

use Tonis\Mvc\Subscriber\DispatchSubscriber;
use Tonis\Mvc\Subscriber\RenderSubscriber;
use Tonis\Mvc\Subscriber\RouteSubscriber;

final class TonisConfig
{
    /** @var array */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $defaults = [
            'debug' => false,
            'environment' => [],
            'packages' => [],
            'subscribers' => [
                RouteSubscriber::class,
                DispatchSubscriber::class,
                RenderSubscriber::class
            ]
        ];

        $this->config = array_replace_recursive($defaults, $config);
    }

    /**
     * @return bool
     */
    public function isDebugEnabled()
    {
        return $this->config['debug'];
    }

    /**
     * @return array
     */
    public function getEnvironment()
    {
        return $this->config['environment'];
    }

    /**
     * @return array
     */
    public function getPackages()
    {
        return $this->config['packages'];
    }

    /**
     * @return array
     */
    public function getSubscribers()
    {
        return $this->config['subscribers'];
    }
}