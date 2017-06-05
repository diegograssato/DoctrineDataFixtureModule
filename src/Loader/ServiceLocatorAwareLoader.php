<?php

namespace DoctrineDataFixtureModule\Loader;

use Doctrine\Common\DataFixtures\Loader as BaseLoader;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;

class ServiceLocatorAwareLoader extends BaseLoader
{

    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Constructor
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Add a fixture object instance to the loader.
     *
     * @param FixtureInterface $fixture
     */
    public function addFixture(FixtureInterface $fixture)
    {
        if ($fixture instanceof ServiceLocatorAwareInterface) {
            $fixture->setServiceLocator($this->serviceLocator);
        }
        parent::addFixture($fixture);
    }
}
