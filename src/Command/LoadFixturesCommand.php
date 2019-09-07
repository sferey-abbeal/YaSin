<?php

namespace App\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixturesCommand extends ContainerAwareCommand
{
    /** @var OutputInterface */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:fixtures:load')
            ->setDescription('Recreate database and load fixtures');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->dropDatabase();
        $this->createDatabase();
        $this->loadFixtures();
        $output->writeln('Fixtures were loaded');
    }

    /**
     * @return int
     * @throws Exception
     */
    private function dropDatabase(): int
    {
        $command = $this->getApplication()->find('doctrine:schema:drop');

        $arguments = [
            'command' => 'doctrine:schema:drop',
            '--full-database' => true,
            '--force' => true,
        ];

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $command->run($input, $this->output);
    }

    /**
     * @return int
     * @throws Exception
     */
    private function createDatabase(): int
    {
        $command = $this->getApplication()->find('doctrine:migrations:migrate');

        $arguments = [
            'command' => 'doctrine:migrations:migrate',
            '-n' => true,
        ];

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $command->run($input, $this->output);
    }

    /**
     * @return int
     * @throws Exception
     */
    private function loadFixtures(): int
    {
        $command = $this->getApplication()->find('doctrine:fixtures:load');

        $arguments = [
            'command' => 'doctrine:fixtures:load',
            '-n' => true,
        ];

        $input = new ArrayInput($arguments);
        $input->setInteractive(false);

        return $command->run($input, $this->output);
    }
}