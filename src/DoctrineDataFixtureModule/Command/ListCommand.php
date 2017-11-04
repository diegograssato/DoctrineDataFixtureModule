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
use Doctrine\Common\DataFixtures\Loader;

/**
 * Command for generate migration classes by comparing your current database schema
 * to your mapping information.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class ListCommand extends Command
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

        $this->setName('orm:fixtures:list')
            ->setDescription('List Data Fixtures')
            ->setHelp(
                <<<EOT
The <info>orm:fixtures:list</info> command loads data fixtures from your bundles:
  <info>vendor/bin/doctrine-module orm:fixtures:list</info>
You can also optionally specify the path to fixtures with the <info>--fixture</info> option:
  <info>vendor/bin/doctrine-module orm:fixtures:list --fixture=/path/to/fixtures1 --fixture=/path/to/fixtures2</info>
  or
  <info>vendor/bin/doctrine-module orm:fixtures:list --fixture /path/to/fixtures1 --fixture /path/to/fixtures2</info>
 
EOT
            )
            ->addOption('fixture', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The directory to load data fixtures from.')
            ->addOption('group', null, InputOption::VALUE_OPTIONAL, 'Set group.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<comment>%s</comment>', "Listing ORM fixtures."));
        $output->writeln(sprintf('<comment>%s</comment>', "----------------------\n"));

        $loader = new Loader();

        $dirOrFile = $input->getOption('fixture');
        if ($dirOrFile) {
            $paths = is_array($dirOrFile) ? $dirOrFile : array($dirOrFile);
            $this->setPath(array_unique($paths));
        } else {

            $this->getPathFromConfig($input, $output);
        }


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
                sprintf('Could not find any fixtures to load in: %s', "\n\n- " . implode("\n- ", $this->paths))
            );
        }
        foreach ($fixtures as $fixture) {
            $output->writeln(sprintf('  <comment>✔</comment> <info>%s</info>', get_class($fixture)));
        }
        $output->writeln("");
    }

    protected function getPathFromConfig(InputInterface $input, OutputInterface $output)
    {
        if ($this->isGroupSupport()) {

            $group = $input->getOption('group');
            if (isset($this->paths['groups']['default']) && empty($group)) {

                $this->paths = $this->paths['groups']['default'];
                $output->writeln(sprintf('<comment>%s</comment>', "Loading [ default ] group."));

            } elseif (isset($this->paths['groups'][$group])) {

                $this->paths = $this->paths['groups'][$group];
                $output->writeln(sprintf('<comment>%s</comment>', "Loading [ $group ] group."));

            }

        } elseif (count($this->paths) > 0) {

            $this->paths = $this->paths;
            $output->writeln(sprintf('<comment>%s</comment>', "Loading path from configuration file."));

        }

    }

    protected function isGroupSupport()
    {

        if (count($this->paths) === 0) {
            return false;
        }

        return array_key_exists('groups', $this->paths);
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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $question
     * @param bool $default
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
