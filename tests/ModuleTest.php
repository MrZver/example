<?php

namespace Boodmo\SalesTest;

use Boodmo\Sales\Module;

use Tests\Bootstrap;

class ModuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Scans service manager configuration, returning all services created by factories and aliases.
     *
     * @return array
     */
    public function provideServiceList()
    {
        $config = array_merge(
            include __DIR__.'/../config/service.config.php'
        );
        $serviceConfig = array_merge(
            $config['service_manager']['factories'] ?? [],
            $config['service_manager']['aliases'] ?? []
        );
        $services = [];
        foreach ($serviceConfig as $key => $val) {
            $services[] = [$key];
        }

        return $services;
    }

    public function testService()
    {
        //$this->markTestIncomplete('need refactor');
        $sm = Bootstrap::getServiceManager();
        // test if service is available in SM
        foreach ($this->provideServiceList() as [$service]) {
            $this->assertTrue($sm->has($service));
        }
    }

    /**
     * @covers \Boodmo\Media\Module::getConfig
     */
    public function testGetConfig()
    {
        $config = array_merge(
            include __DIR__.'/../config/module.config.php',
            include __DIR__.'/../config/service.config.php'
        );
        $module = new Module();
        $this->assertEquals($config, $module->getConfig());
    }
}
