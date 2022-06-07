<?php

namespace Boodmo\Sales;

use Zend\ModuleManager\Feature\ConfigProviderInterface;

class Module implements ConfigProviderInterface
{
    public function getConfig()
    {
        return array_merge(
            include __DIR__.'/../config/module.config.php',
            include __DIR__.'/../config/service.config.php'
        );
    }
}
