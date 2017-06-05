<?php

namespace DoctrineDataFixtureModule\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use DoctrineDataFixtureModule\Loader\ServiceLocatorAwareLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Zend\ServiceManager\ServiceLocatorInterface;

class ImportCommand extends Command
{

    const PURGE_MODE_TRUNCATE = 2;

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Service Locator instance
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * {@inheritDoc}
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        parent::__construct();
    }

    /**
     * Set the paths
     *
     * @param array $paths
     */
    public function setPaths($paths)
    {
        $this->paths = $paths;
    }

    /**
     * Set the entity manager
     *
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('data-fixture:import')
            ->setDescription('Import Data Fixtures')
            ->setHelp('The import command Imports data-fixtures' . PHP_EOL)
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append data to existing data.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Truncate tables before inserting data');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<comment>%s</comment>', "Loading fixtures"));
        $output->writeln(sprintf('<comment>%s</comment>', "----------------"));
        if ($input->isInteractive() && !$input->getOption('append')) {
            if (!$this->askConfirmation($input, $output, '<question>Careful, database will be purged. Do you want to continue y/N ?</question>', false)) {
                return;
            }
            $output->writeln("");
        }

        $loader = new ServiceLocatorAwareLoader($this->serviceLocator);
        $purger = new ORMPurger();

        if ($input->getOption('purge-with-truncate')) {
            $purger->setPurgeMode(self::PURGE_MODE_TRUNCATE);
        }

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>✔</comment> <info>%s</info>', $message));
            }
        );

        foreach ($this->paths as $value) {
            if (is_dir($value)) {
                $loader->loadFromDirectory($value);
            } elseif (is_file($value)) {
                $loader->loadFromFile($value);
            }
        }
        $fixtures = $loader->getFixtures();
        if (!$fixtures) {
            throw new \RuntimeException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $this->paths))
            );
        }
        $executor->execute($fixtures, $input->getOption('append'));
        $output->writeln("");
    }


    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string          $question
     * @param bool            $default
     *
     * @return bool
     */
    private function askConfirmation(InputInterface $input, OutputInterface $output, $question, $default)
    {
        if (!class_exists('Symfony\Component\Console\Question\ConfirmationQuestion')) {
            $dialog = $this->getHelperSet()->get('dialog');
            return $dialog->askConfirmation($output, $question, $default);
        }
        $questionHelper = $this->getHelperSet()->get('question');
        $question = new ConfirmationQuestion($question, $default);
        return $questionHelper->ask($input, $output, $question);
    }
}
