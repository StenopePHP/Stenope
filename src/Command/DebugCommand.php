<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Command;

use Stenope\Bundle\Attribute\SuggestedDebugQuery;
use Stenope\Bundle\ContentManagerInterface;
use function Stenope\Bundle\ExpressionLanguage\expr;
use Stenope\Bundle\ExpressionLanguage\Expression;
use Stenope\Bundle\TableOfContent\Headline;
use Stenope\Bundle\TableOfContent\TableOfContent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Dumper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ExpressionLanguage;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class DebugCommand extends Command
{
    use StopwatchHelperTrait;

    private ContentManagerInterface $manager;
    private Stopwatch $stopwatch;
    private array $registeredTypes;

    public function __construct(ContentManagerInterface $manager, Stopwatch $stopwatch, array $registeredTypes = [])
    {
        $this->manager = $manager;
        $this->stopwatch = $stopwatch;
        $this->registeredTypes = $registeredTypes;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('debug:stenope:content')
            ->setDescription('Debug Stenope managed contents')
            ->addArgument('class', InputArgument::REQUIRED, 'Content FQCN')
            ->addArgument('id', InputArgument::OPTIONAL, 'Content identifier')
            ->addOption('order', 'o', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Order by field(s)')
            ->addOption('filter', 'f', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Filter by field(s)')
            ->addOption('suggest', 's', InputOption::VALUE_NONE, 'Suggest common queries')
            ->setHelp(<<<HELP
            The <info>%command.name%</info> allows to list and display content managed by Stenope:

                <info>php %command.full_name% "App\Model\Author"</info>

            will list all authors.

                <info>php %command.full_name% "App\Model\Author ogi"</info>

            will fetch and display the processed Author with id "ogi".

            Change <info>verbosity</info> in order to get more details <comment>(-v, -vv, -vvv)</comment>.

            --- Sort

            The command allows to order the list:

                <info>php %command.full_name% "App\Model\Author" --order=slug</info>
                <info>php %command.full_name% "App\Model\Author" --order=integrationDate</info>

            In <info>desc</info> order:

                <info>php %command.full_name% "App\Model\Author" --order='desc:integrationDate'</info>

                same as:

                <info>php %command.full_name% "App\Model\Author" --order='-integrationDate'</info>

            You can order by multiple fields:

                <info>php %command.full_name% "App\Model\Author" --order='desc:active' --order='integrationDate'</info>

            --- Filter

            The command allows to filter out the list in various ways, using an expression read by the ExpressionLanguage component.
            See https://symfony.com/doc/current/components/expression_language/syntax.html
            The current item is referred using "data", "d" or "_":

                <info>php %command.full_name% "App\Model\Author" --filter=data.active</info>
                <info>php %command.full_name% "App\Model\Author" --filter=d.active</info>
                <info>php %command.full_name% "App\Model\Author" --filter=_.active</info>

            Negation:

                <info>php %command.full_name% "App\Model\Author" --filter='not d.active'</info>
                <info>php %command.full_name% "App\Model\Author" --filter='!d.active'</info>

            Contains:

                <info>php %command.full_name% "App\Model\Article" --filter='contains(_.slug, "symfony")'</info>

            You can also use multiple filters at once:

                <info>php %command.full_name% "App\Model\Article" \
                    --filter='not _.outdated' \
                    --filter='contains(_.slug, "dev")' \
                    --filter='"symfony" in _.tags' \
                    --filter='_.date > date("2021-01-23")'</info>

            Built-in functions are:

            * date
            * datetime
            * upper
            * lower
            * contains
            * starts_with
            * ends_with
            * keys

            HELP
            )
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getArgument('class')) {
            if (0 === \count($this->registeredTypes)) {
                $io->error('It seems there is no type known by Stenope. Did you configure stenope.providers?');

                return;
            }

            $chosenType = $io->choice('Which content type would you like to inspect?', $this->registeredTypes);
            $input->setArgument('class', $chosenType);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Debug Stenope Contents');

        $class = $this->guessOrAskClass($input, $io);
        $id = $input->getArgument('id');

        [$suggestedFilter, $suggestedOrder] = $this->suggest($io, $input, $class, $id);

        if (!$this->stopwatch->isStarted('fetch')) {
            $this->stopwatch->start('fetch', 'stenope');
        }

        if (null === $id) {
            $this->list(
                $io,
                $class,
                $this->getOrders($suggestedOrder ?? $input->getOption('order')),
                $suggestedFilter ? expr($suggestedFilter) : $this->getFilters($input)
            );
        } else {
            $this->describe($io, $class, $id);
        }

        if ($this->stopwatch->isStarted('fetch')) {
            $this->stopwatch->stop('fetch');
        }

        $io->comment("Fetched info:\n" . self::formatEvent($this->stopwatch->getEvent('fetch')));

        return Command::SUCCESS;
    }

    private function getOrders(array $rawOrders): array
    {
        $orders = [];
        foreach ($rawOrders as $field) {
            if (str_starts_with($field, 'desc:')) {
                $orders[substr($field, 5)] = false;
                continue;
            }
            if (str_starts_with($field, '-')) {
                $orders[substr($field, 1)] = false;
                continue;
            }

            $orders[$field] = true;
        }

        return $orders;
    }

    private function getFilters(InputInterface $input): ?Expression
    {
        if ([] === $filterExpr = $input->getOption('filter')) {
            return null;
        }

        if (!class_exists(ExpressionLanguage::class)) {
            throw new \LogicException('You must install the Symfony ExpressionLanguage component ("symfony/expression-language") to use the "--filter" option.');
        }

        return expr(...$filterExpr);
    }

    private function list(SymfonyStyle $io, string $class, array $sort, ?Expression $filters): void
    {
        $io->section("\"$class\" items");

        if ($filters) {
            $io->writeln("Filtered with: <info>$filters</info>");
            $io->newLine();
        }

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

    private function suggest(
        SymfonyStyle $io,
        InputInterface $input,
        string $class,
        ?string $id
    ): array {
        if ($input->getOption('suggest')) {
            if (PHP_MAJOR_VERSION < 8) {
                throw new \LogicException('You need PHP 8.0 at least to use this option.');
            }

            if (!$input->isInteractive()) {
                throw new \LogicException('Cannot use the --suggest option in non-interactive mode.');
            }

            $suggestedQueries = $this->getSuggestedQueries($class);

            $choices = array_map(
                static fn (SuggestedDebugQuery $suggestion): string => sprintf(
                    '<info>%s</info> (filter: %s, order: %s)',
                    $suggestion->description,
                    $suggestion->filters ?? '-',
                    $suggestion->orders ? implode(', ', $suggestion->orders) : '-',
                ),
                $suggestedQueries,
            );

            /** @var SuggestedDebugQuery $choice */
            $choice = $suggestedQueries[array_search(
                $io->choice('Which query would you like to execute?', $choices),
                $choices,
                true
            )];

            $filter = $choice->filters;
            $orders = $choice->orders;

            $io->comment($this->getCommandLine($class, $choice));

            return [$filter, $orders];
        }

        if (PHP_MAJOR_VERSION >= 8 && null === $id && $input->isInteractive()) {
            $io->section('Suggested queries');

            if (!$suggestedQueries = $this->getSuggestedQueries($class)) {
                $io->writeln(sprintf(
                    ' 💡 <fg=cyan>Use the <comment>"%s"</comment> attribute on the <info>"%s"</info> class to register common queries.</>',
                    SuggestedDebugQuery::class,
                    $class,
                ));
            }

            $io->definitionList(...array_map(
                fn (SuggestedDebugQuery $suggestion): array => [
                    $suggestion->description => $this->getCommandLine($class, $suggestion),
                ],
                $suggestedQueries,
            ));

            $io->writeln(' 💡 <fg=cyan>Use <comment>--suggest</comment> to interactively run one of those queries.</>');
        }

        return [null, null];
    }

    private function getSuggestedQueries(string $class): array
    {
        return array_map(
            static fn (\ReflectionAttribute $attribute) => $attribute->newInstance(),
            (new \ReflectionClass($class))->getAttributes(SuggestedDebugQuery::class),
        );
    }

    private function getCommandLine(string $class, SuggestedDebugQuery $query): string
    {
        $commandLine = sprintf('bin/console %s "%s"', $this->getName(), $class);

        foreach ($query->orders as $order) {
            $commandLine .= sprintf(' --order="%s"', $order);
        }

        if ($query->filters) {
            $commandLine .= sprintf(' --filter="%s"', strtr($query->filters, ['"' => '\"']));
        }

        return $commandLine;
    }

    private function guessOrAskClass(InputInterface $input, SymfonyStyle $io): string
    {
        $class = $input->getArgument('class');

        if ($input->isInteractive() && !\in_array($class, $this->registeredTypes, true)) {
            $shorthands = array_combine(
                $this->registeredTypes,
                array_map(
                    static fn (string $fqcn) => strtolower(basename(str_replace('\\', '/', $fqcn))),
                    $this->registeredTypes
                )
            );

            $comparedClassInput = strtolower($class);
            $scores = array_combine(
                array_keys($shorthands),
                array_map(fn (string $shortname) => levenshtein($comparedClassInput, $shortname), $shorthands)
            );

            $bestScore = min($scores);
            $matches = array_keys($scores, $bestScore, true);

            if (\count($matches) > 1) {
                $class = $io->choice('Did you mean one of these content types?', $matches);
            } else {
                $match = current($matches);
                $class = $match;

                $io->comment("Assuming you meant \"$class\"");
            }
        }

        return $class;
    }
}
