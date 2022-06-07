<?php

namespace Boodmo\Sales\Factory;

use Boodmo\Sales\Model\Workflow\StatusWorkflow;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\EventManager\LazyListenerAggregate;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class StatusWorkflowFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     *
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config')['order_workflow_listeners'] ?? [];
        $definitions = [];
        foreach ($config as $listener) {
            $definitions = array_merge($definitions, $listener::getDefinitions());
        }
        $aggregate = new LazyListenerAggregate(
            $definitions,
            $container
        );
        return new StatusWorkflow($aggregate);
    }
}
