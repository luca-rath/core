<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Plugin\PluginRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('Checks configuration issues')
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> command looks for configuration issues

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Configuration Check Report');

        $io->writeln('Local directory: ' . $this->getContainer()->getParameter('nanbando.storage.local_directory'));

        if (!$this->getContainer()->has('filesystem.remote')) {
            $io->warning(
                'No remote storage configuration found. This leads into disabled "fetch" and "push" commands.' .
                'Please follow the documentation for global configuration.' . PHP_EOL . PHP_EOL .
                'http://nanbando.readthedocs.io/en/latest/configuration.html#global-configuration'
            );
        } else {
            $io->writeln('Remote Storage: YES');
        }

        $backups = $this->getContainer()->getParameter('nanbando.backup');
        if (0 === count($backups)) {
            $io->warning(
                'No backup configuration found. Please follow the documentation for local configuration.' . PHP_EOL . PHP_EOL .
                'http://nanbando.readthedocs.io/en/latest/configuration.html#local-configuration'
            );
        }

        $this->checkBackups($io, $backups);

        $io->writeln('');
    }

    /**
     * Check backup-configuration.
     *
     * @param SymfonyStyle $io
     * @param array $backups
     */
    private function checkBackups(SymfonyStyle $io, array $backups)
    {
        /** @var PluginRegistry $plugins */
        $plugins = $this->getContainer()->get('plugins');
        foreach ($backups as $name => $backup) {
            $this->checkBackup($plugins, $io, $name, $backup);
        }
    }

    /**
     * Check single backup-configuration.
     *
     * @param PluginRegistry $plugins
     * @param SymfonyStyle $io
     * @param string $name
     * @param array $backup
     *
     * @return bool
     */
    private function checkBackup(PluginRegistry $plugins, SymfonyStyle $io, $name, array $backup)
    {
        $io->section('Backup: ' . $name);
        if (!$plugins->has($backup['plugin'])) {
            $io->warning(sprintf('Plugin "%s" not found', $backup['plugin']));

            return false;
        }

        $optionsResolver = new OptionsResolver();
        $plugins->getPlugin($backup['plugin'])->configureOptionsResolver($optionsResolver);

        try {
            $optionsResolver->resolve($backup['parameter']);
        } catch (InvalidArgumentException $e) {
            $io->warning(sprintf('Parameter not valid' . PHP_EOL . PHP_EOL . 'Message: "%s"', $e->getMessage()));

            return false;
        }

        $io->writeln('OK');

        return true;
    }
}
