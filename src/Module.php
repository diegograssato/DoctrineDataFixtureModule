<?php

namespace DoctrineDataFixtureModule;

use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\EventManager\EventInterface;
use Zend\ModuleManager\ModuleManagerInterface;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use DoctrineDataFixtureModule\Command\ImportCommand;

class Module implements ConfigProviderInterface, InitProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function init(ModuleManagerInterface $manager)
    {
        $events = $manager->getEventManager()->getSharedManager();
        $events->attach(
            'doctrine',
            'loadCli.post',
            [$this, 'initCli']
        );
    }

    /**
     * Initialise the command line interface
     *
     * @param EventInterface $event
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function initCli(EventInterface $event)
    {
        $application    = $event->getTarget();
        $serviceManager = $event->getParam('ServiceManager');
        $entityManager  = $serviceManager->get('Doctrine\ORM\EntityManager');
        $paths          = $serviceManager->get('Doctrine\ORM\Configuration\Fixture');

        $importCommand = new ImportCommand($serviceManager);
        $importCommand->setEntityManager($entityManager);
        $importCommand->setPaths($paths);
        
        ConsoleRunner::addCommands($application);
        $application->addCommands([$importCommand]);
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
