<?php

namespace DoctrineDataFixtureModule\Service;

use Interop\Container\ContainerInterface;
use Zend\Stdlib\AbstractOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FixtureFactory implements FactoryInterface
{
    
    /**
     * {@inheritDoc}
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = [])
    {
        return $this->getOptions($container);
    }

    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this->getOptions($serviceLocator);
    }

    /**
     * Gets options from configuration based on name.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return AbstractOptions
     */
    public function getOptions(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');
        if (!isset($config['doctrine']['fixtures'])) {
            return [];
        }

        return $config['doctrine']['fixtures'];
    }
}
