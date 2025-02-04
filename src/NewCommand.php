<?php

namespace Brackets\CraftableInstaller\Console;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new Craftable application using latest Laravel (currently 5.8).')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest DEV release ready for Craftable development')
            ->addOption('lts', null, InputOption::VALUE_NONE, 'Installs Craftable using LTS release of Laravel (currently 5.5)')
            ->addOption('no-install', null, InputOption::VALUE_NONE, 'Do not run craftable:install')
            ;
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $this->findComposer();

        $commands = [];

        $directory = "\"".$input->getArgument('name')."\"";

        array_push($commands, $composer.' create-project --prefer-dist laravel/laravel '.$directory.($input->getOption('lts') ? ' "5.5.*" ' : ' "5.8.*" '));

        array_push($commands, 'cd '.$directory);

        $output->writeln('<info>Crafting Craftable :) ...</info>');

        $packages = [
            "craftable/admin-ui",
            "craftable/admin-listing",
            "craftable/admin-auth",
            "craftable/admin-translations",
            "craftable/media",
            "craftable/translatable",
            "craftable/craftable",
        ];

        if ($input->getOption('dev')) {
            $packages = array_map(function($package) {
                return '"'.$package.':dev-master"';
            }, $packages);
            array_push($commands, $composer.' require '.implode(' ', $packages));
            array_push($commands, $composer.' require --dev "craftable/admin-generator:dev-master"');
            array_push($commands, 'rm -rf vendor/craftable');
            array_push($commands, $composer.' update --prefer-source');
        } else {
            array_push($commands, $composer.' require "craftable/craftable"');
            array_push($commands, $composer.' require --dev "craftable/admin-generator"');
        }

        if (!$input->getOption('no-install')) {
            // FIXME these commands seem to not work on some environments (probably on some Windows platforms) when run this way (but they work when run manually) - this needs further investigation
            array_push($commands, '"'.PHP_BINARY.'" artisan craftable:init-env');
            array_push($commands, '"'.PHP_BINARY.'" artisan craftable:install');
            array_push($commands, 'npm install');
            array_push($commands, 'npm run dev');

        }

        // TODO it would be better to run not all commands in once, because some of them may fail
        $process = new Process(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            $process->setTty(true);
        }

        $process->run(function ($type, $line) use ($output) {
            $output->write($line);
        });

        $output->writeln('<comment>Craftable crafted! Craft something crafty ;)</comment>');
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }

        return 'composer';
    }
}
