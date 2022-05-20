<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Command;

use Stenope\Bundle\Builder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Stopwatch\Stopwatch;

class BuildCommand extends Command
{
    use StopwatchHelperTrait;

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
            ->setName('stenope:build')
            ->setDescription('Build static website')
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
                'base-url',
                null,
                InputOption::VALUE_REQUIRED,
                'What should be used as base-url for absolute url generation?'
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
            ->addOption(
                'ignore-content-not-found',
                null,
                InputOption::VALUE_NONE,
                'Ignore content not found errors'
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

        if ($baseUrl = $input->getOption('base-url')) {
            $this->builder->setBaseUrl($baseUrl);
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
            ['scheme' => $this->builder->getScheme()],
            ['host' => $this->builder->getHost()],
            ['baseUrl' => $this->builder->getBaseUrl()],
        );

        if (!$this->stopwatch->isStarted('build')) {
            $this->stopwatch->start('build', 'stenope');
        }

        $sitemap = $input->getOption('no-sitemap');
        $expose = $input->getOption('no-expose');
        $ignoreContentNotFoundErrors = $input->getOption('ignore-content-not-found');

        if ($input->isInteractive() && $output->isDecorated() && $output->getVerbosity() === OutputInterface::VERBOSITY_NORMAL) {
            // In interactive, ansi compatible envs with normal verbosity, use a progress bar
            iterator_to_array($progressIterator = new BuildProgressIterator($output, $this->builder->iterate(
                !$sitemap,
                !$expose,
                $ignoreContentNotFoundErrors,
            )));
            $count = \count($progressIterator);
        } else {
            // Otherwise, let the user controls shown information in logs through verbosity
            $count = $this->builder->build(!$sitemap, !$expose, $ignoreContentNotFoundErrors);
            $io->newLine();
        }

        if ($this->stopwatch->isStarted('build')) {
            $this->stopwatch->stop('build');
        }

        $io->success("Built $count pages.\n" . self::formatEvent($this->stopwatch->getEvent('build')));

        return Command::SUCCESS;
    }
}

/**
 * A build iterator that shows progress using a Symfony CLI ProgressBar
 */
class BuildProgressIterator implements \IteratorAggregate, \Countable
{
    private ProgressBar $progressBar;
    private \Generator $buildIterator;
    private int $count = 1;

    public function __construct(OutputInterface $output, \Generator $buildIterator)
    {
        $this->progressBar = new ProgressBar($output);
        $this->progressBar->minSecondsBetweenRedraws(0.02);
        $this->progressBar->maxSecondsBetweenRedraws(0.05);
        $this->progressBar->setMessage('...', 'step');
        $this->progressBar->setBarCharacter('<fg=green>-</>');
        $this->progressBar->setEmptyBarCharacter(' ');
        $this->progressBar->setProgressCharacter('<fg=green>➤</>');
        $this->progressBar->setFormat(<<<TXT
              <bg=green;fg=black>[%step%]</bg=green;fg=black> %current%/%max% [%bar%] %percent:3s%% <info>%elapsed:6s%/%estimated:-6s%</info> <fg=white;bg=blue>%memory:6s%</fg=white;bg=blue>
               <comment>%message%</comment>

            TXT
        );

        $this->buildIterator = $buildIterator;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->progressBar->iterate((function (): iterable {
            foreach ($this->buildIterator as $step => $context) {
                $this->notifyProgress(
                    \is_string($step) ? $step : null,
                    $context['maxStep'] ?? null,
                    $context['message'] ?? null,
                );

                for ($x = 0; $x < $context['advance']; ++$x) {
                    // Show progress for each advancement in build steps
                    yield;
                }
            }

            $this->progressBar->finish();
            $this->count = $this->buildIterator->getReturn();
        })(), $this->count());

        $this->progressBar->clear();
    }

    public function count(): int
    {
        return $this->count ?? 1;
    }

    private function notifyProgress(?string $stepName = null, ?int $maxStep = null, ?string $message = null): void
    {
        if ($maxStep) {
            $this->progressBar->setMaxSteps($maxStep);
        }

        if ($message) {
            $this->progressBar->setMessage($this->padMessage($message));
        }

        if ($stepName && $this->progressBar->getMessage('step') !== $stepName) {
            $this->progressBar->setMessage($stepName, 'step');
            // Reset message on changed step
            $this->progressBar->setMessage($this->padMessage($message ?? ''));
            // Force display change:
            $this->progressBar->display();
        }
    }

    private function padMessage(string $message): string
    {
        static $messagePadding = null;
        if (!$messagePadding) {
            // Fixup the progress bar %message% placeholder clearing according to terminal width
            // (multi-line progress bars are a bit messed up)
            $messagePadding = (new Terminal())->getWidth() - 3;
        }

        return str_pad($message, $messagePadding, ' ');
    }
}
