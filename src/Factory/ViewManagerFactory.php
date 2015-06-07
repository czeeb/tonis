<?php
namespace Tonis\Mvc\Factory;

use Tonis\Di\Container;
use Tonis\Di\ContainerUtil;
use Tonis\Package;
use Tonis\Package\PackageManager;
use Tonis\View\ViewManager;

final class ViewManagerFactory
{
    /**
     * @param Container $di
     * @return ViewManager
     */
    public function __invoke(Container $di)
    {
        /** @var PackageManager $pm */
        $pm = $di->get(PackageManager::class);
        $vm = new ViewManager();
        $config = $pm->getMergedConfig()['mvc']['view_manager'];

        foreach ($config['strategies'] as $strategy) {
            if (empty($strategy)) {
                continue;
            }
            $vm->addStrategy(ContainerUtil::get($di, $strategy));
        }

        $vm->setErrorTemplate($config['error_template']);
        $vm->setNotFoundTemplate($config['not_found_template']);
        return $vm;
    }
}