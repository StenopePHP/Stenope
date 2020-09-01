<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Command;

use Content\Builder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Build Command
 */
class BuildCommand extends Command
{
    protected static $defaultName = 'content:build';

    /**
     * Static site builder
     *
     * @var Builder
     */
    private $builder;

    public function __construct(Builder $builder)
    {
        parent::__construct();

        $this->builder = $builder;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Build static website')
            ->setHelp('...')
            ->addArgument(
                'destination',
                InputArgument::OPTIONAL,
                'Full path to destination directory'
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'What should be used as domain name for absolute url generation?'
            )
            ->addOption(
                'scheme',
                null,
                InputOption::VALUE_REQUIRED,
                'What should be used as scheme for absolute url generation?'
            )
            ->addOption(
                'no-sitemap',
                null,
                InputOption::VALUE_NONE,
                'Don\'t build the sitemap'
            )
            ->addOption(
                'no-expose',
                null,
                InputOption::VALUE_NONE,
                'Don\'t expose the public directory after build'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ($destination = $input->getArgument('destination')) {
            $this->builder->setDestination($destination);
        }

        if ($host = $input->getOption('host')) {
            $this->builder->setHost($host);
        }

        if ($scheme = $input->getOption('scheme')) {
            $this->builder->setScheme($scheme);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Building static site');

        try {
            $this->builder->build(!$input->getOption('no-sitemap'), !$input->getOption('no-expose'));
        } catch (\Throwable $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
