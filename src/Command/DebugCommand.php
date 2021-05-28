<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Command;

use Stenope\Bundle\ContentManager;
use Stenope\Bundle\TableOfContent\Headline;
use Stenope\Bundle\TableOfContent\TableOfContent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Dumper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class DebugCommand extends Command
{
    use StopwatchHelperTrait;

    protected static $defaultName = 'debug:stenope:content';

    private ContentManager $manager;
    private Stopwatch $stopwatch;

    public function __construct(ContentManager $manager, Stopwatch $stopwatch)
    {
        $this->manager = $manager;
        $this->stopwatch = $stopwatch;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Debug Stenope managed contents')
            ->addArgument('class', InputArgument::REQUIRED, 'Content FQCN')
            ->addArgument('id', InputArgument::OPTIONAL, 'Content identifier')
            ->addOption('order', 'o', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Order by field(s)')
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Filter by field(s)')
            ->setHelp(<<<HELP
            The <info>%command.name%</info> allows to list and display content managed by Stenope:

                <info>php %command.full_name% "App\Model\Author"</info>

            will list all authors.

                <info>php %command.full_name% "App\Model\Author ogi"</info>

            will fetch and display the processed Author with id "ogi".

            Change <info>verbosity</info> in order to get more details <comment>(-v, -vv, -vvv)</comment>.

            --- Sort

            The command allows to order the list:

                <info>php %command.full_name% "App\Model\Author" --order="slug"</info>
                <info>php %command.full_name% "App\Model\Author" --order="integrationDate"</info>

            In <info>desc</info> order:

                <info>php %command.full_name% "App\Model\Author" --order="desc:integrationDate"</info>

            You can order by multiple fields:

                <info>php %command.full_name% "App\Model\Author" --order="desc:active" --order="integrationDate"</info>

            --- Filter

            The command allows to filter out the list in various ways:

                <info>php %command.full_name% "App\Model\Author" --filter=active</info>

            Negation:

                <info>php %command.full_name% "App\Model\Author" --filter="not:active"</info>

                same as:

                <info>php %command.full_name% "App\Model\Author" --filter="-active"</info>

            Contains:

                <info>php %command.full_name% "App\Model\Article" --filter="slug contains:symfony"</info>

            You can also use multiple filters at once:

                <info>php %command.full_name% "App\Model\Article" --filter="not:outdated" --filter="slug contains:symfony"</info>
            HELP
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Debug Stenope Contents');

        $class = $input->getArgument('class');
        $id = $input->getArgument('id');

        if (!$this->stopwatch->isStarted('fetch')) {
            $this->stopwatch->start('fetch', 'stenope');
        }

        if (null === $id) {
            $this->list($io, $class, $this->geOrders($input), $this->getFilters($input));
        } else {
            $this->describe($io, $class, $id);
        }

        if ($this->stopwatch->isStarted('fetch')) {
            $this->stopwatch->stop('fetch');
        }

        $io->comment("Fetched info:\n" . self::formatEvent($this->stopwatch->getEvent('fetch')));

        return Command::SUCCESS;
    }

    private function geOrders(InputInterface $input): array
    {
        $orders = [];
        foreach ($input->getOption('order') ?? [] as $field) {
            if (\str_starts_with($field, 'desc:')) {
                $orders[substr($field, 5)] = false;
                continue;
            }

            $orders[$field] = true;
        }

        return $orders;
    }

    private function getFilters(InputInterface $input): array
    {
        $filters = [];
        foreach ($input->getOption('filter') ?? [] as $field) {
            $matches = [];
            if (preg_match('#^(\w+) contains:(.*)$#', $field, $matches)) {
                $searched = $matches[2];
                $filters[$matches[1]] = static function ($value) use ($searched) {
                    if (!\is_string($value)) {
                        return false;
                    }

                    return str_contains($value, $searched);
                };
                continue;
            }
            if (\str_starts_with($field, 'not:')) {
                $filters[substr($field, 4)] = false;
                continue;
            }
            if (\str_starts_with($field, '-')) {
                $filters[substr($field, 1)] = false;
                continue;
            }

            $filters[$field] = true;
        }

        return $filters;
    }

    private function list(SymfonyStyle $io, string $class, array $sort, array $filters): void
    {
        $io->section("\"$class\" items");

        $list = $this->manager->getContents($class, $sort, $filters);

        $io->listing(array_keys($list));

        $io->note(sprintf('Found %d items', \count($list)));
    }

    private function describe(SymfonyStyle $io, string $class, string $id): void
    {
        $io->section("\"$class\" item with id \"$id\"");

        $item = $this->manager->getContent($class, $id);

        $cloner = new VarCloner();

        if (!$io->isVeryVerbose()) {
            // Except on very verbose mode, display a simplified TOC representation:
            $cloner->addCasters([
                TableOfContent::class => \Closure::fromCallable([self::class, 'castTableOfContent']),
            ]);
        }

        // Show full strings on very verbose mode:
        $cloner->setMaxString($io->isVeryVerbose() ? -1 : 250);
        // Show full data tree on verbose mode:
        $cloner->setMaxItems($io->isVerbose() ? -1 : 15);
        $dump = new Dumper($io, null, $cloner);

        $io->writeln($dump($item));
    }

    private static function castTableOfContent(TableOfContent $toc, array $a, Stub $s): array
    {
        $appendHeadline = static function (&$headlines, Headline $headline) use (&$appendHeadline): void {
            $childHeadlines = [];
            foreach ($headline->getChildren() as $child) {
                $appendHeadline($childHeadlines, $child);
            }

            $headlines["H{$headline->getLevel()} - {$headline->getContent()}"] = $childHeadlines;
        };

        $headlines = [];
        /** @var Headline $headline */
        foreach ($toc as $headline) {
            $appendHeadline($headlines, $headline);
        }

        $s->type = Stub::TYPE_ARRAY;
        $s->class = Stub::ARRAY_ASSOC;
        $s->value = 'headlines';

        return $headlines;
    }
}
