<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Command;

use Content\Builder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * Build Command
 */
class BuildCommand extends Command
{
    protected static $defaultName = 'content:build';

    private Builder $builder;
    private Stopwatch $stopwatch;

    public function __construct(Builder $builder, Stopwatch $stopwatch)
    {
        $this->builder = $builder;
        $this->stopwatch = $stopwatch;

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

        if (!$this->stopwatch->isStarted('build')) {
            $this->stopwatch->start('build', 'content');
        }

        if ($input->isInteractive() && $output->isDecorated() && $output->getVerbosity() <= OutputInterface::VERBOSITY_NORMAL) {
            $progressBar = new ProgressBar($output);
            $notifier = new ProgressNotifier($progressBar);
        }

        $count = $this->builder->build(
            !$input->getOption('no-sitemap'),
            !$input->getOption('no-expose'),
            $notifier ?? null
        );

        if ($this->stopwatch->isStarted('build')) {
            $this->stopwatch->stop('build');
        }

        if (isset($progressBar)) {
            $progressBar->finish();
            $progressBar->clear();
        } else {
            $io->newLine();
        }

        $io->success("Built $count pages.\n" . self::formatEvent($this->stopwatch->getEvent('build')));

        return Command::SUCCESS;
    }

    public static function formatEvent(StopwatchEvent $event): string
    {
        return sprintf(
            'Start time: %s — End time: %s — Duration: %s — Memory used: %s',
            date('H:i:s', ($event->getOrigin() + $event->getStartTime()) / 1000),
            date('H:i:s', ($event->getOrigin() + $event->getEndTime()) / 1000),
            Helper::formatTime($event->getDuration() / 1000),
            Helper::formatMemory($event->getMemory())
        );
    }
}

class ProgressNotifier implements Builder\BuildNotifierInterface
{
    private ProgressBar $progressBar;

    public function __construct(ProgressBar $progressBar)
    {
        $this->progressBar = $progressBar;
        $this->progressBar->minSecondsBetweenRedraws(0.05);
        $this->progressBar->maxSecondsBetweenRedraws(0.1);
        $this->progressBar->setMessage('...', 'step');
        $this->progressBar->setFormat(<<<TXT
              <bg=green;fg=black>[%step%]</bg=green;fg=black> %current%/%max% [%bar%] %percent:3s%% <info>%elapsed:6s%/%estimated:-6s%</info> <fg=white;bg=blue>%memory:6s%</fg=white;bg=blue>
               <comment>%message%</comment>

            TXT
        );

        $this->progressBar->setBarCharacter('<fg=green>-</>');
        $this->progressBar->setEmptyBarCharacter(' ');
        $this->progressBar->setProgressCharacter('<fg=green>➤</>');
    }

    public function notify(
        ?string $stepName = null,
        ?int $advance = null,
        ?int $maxStep = null,
        ?string $message = null
    ): void {
        static $started = false;
        static $messagePadding = null;
        if (!$started) {
            $this->progressBar->start($maxStep ?? 1);
            $started = true;
            // Fixup the progress bar %message% placeholder clearing according to terminal width
            // (multi-line progress bars are a bit messed up)
            $messagePadding = (new Terminal())->getWidth() - 3;
        }

        if ($maxStep) {
            $this->progressBar->setMaxSteps($maxStep);
        }

        if ($advance) {
            $this->progressBar->advance($advance);
        }

        if ($message) {
            $this->progressBar->setMessage(str_pad($message, $messagePadding, ' '));
        }

        if ($stepName && $this->progressBar->getMessage('step') !== $stepName) {
            $this->progressBar->setMessage($stepName, 'step');
            // Reset message on changed step
            $this->progressBar->setMessage(str_pad($message ?? '', $messagePadding, ' '));
            $this->progressBar->display();
        }
    }
}
