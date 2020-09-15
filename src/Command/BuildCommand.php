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

    private Builder $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;

        parent::__construct();
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
                'buildDir',
                InputArgument::OPTIONAL,
                'Full path to build directory',
                $this->builder->getBuildDir(),
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
                'Don\'t expose the public directory'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ($destination = $input->getArgument('buildDir')) {
            $this->builder->setBuildDir($destination);
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
        $io->definitionList(
            ['buildDir' => $this->builder->getBuildDir()],
            ['host' => $this->builder->getHost()],
            ['scheme' => $this->builder->getScheme()],
        );

        $this->builder->setLogger(new BuildCommandLogger($io));

        $this->builder->build(!$input->getOption('no-sitemap'), !$input->getOption('no-expose'));

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
