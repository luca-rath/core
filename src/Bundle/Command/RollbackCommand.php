<?php

namespace Nanbando\Bundle\Command;

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RollbackCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('rollback')
            ->setDescription('Rollback last self-update command.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updater = new Updater();
        $updater->rollback();

        $new = $updater->getNewVersion();
        $old = $updater->getOldVersion();

        $output->writeln(sprintf('Rolled back from %s to %s', $old, $new));
    }
}