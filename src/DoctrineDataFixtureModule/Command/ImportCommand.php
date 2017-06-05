<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace DoctrineDataFixtureModule\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use DoctrineDataFixtureModule\Loader\ServiceLocatorAwareLoader;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Command for generate migration classes by comparing your current database schema
 * to your mapping information.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class ImportCommand extends Command
{
    protected $paths;

    protected $em;
    
    /**
     * Service Locator instance
     * @var Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceLocator;

    const PURGE_MODE_TRUNCATE = 2;
    
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();

        $this->setName('data-fixture:import')
            ->setDescription('Import Data Fixtures')
            ->setHelp('The import command Imports data-fixtures' . PHP_EOL)
            ->addOption('append', null, InputOption::VALUE_NONE, 'Append data to existing data.')
            ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Truncate tables before inserting data');
    }

    public function execute(InputInterface $input, OutputInterface $output)
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

        $executor = new ORMExecutor($this->em, $purger);
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

    public function setPath($paths)
    {
        $this->paths = $paths;
    }

    public function setEntityManager($em)
    {
        $this->em = $em;
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
