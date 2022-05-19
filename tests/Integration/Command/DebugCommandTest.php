<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Integration\Command;

use App\Model\Author;
use App\Model\Recipe;
use Stenope\Bundle\Command\DebugCommand;
use Symfony\Bridge\PhpUnit\ClassExistsMock;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ExpressionLanguage;

class DebugCommandTest extends KernelTestCase
{
    /**
     * @dataProvider provide testList data
     */
    public function testList(string $class, string $expected, array $filters = [], array $orders = []): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('debug:stenope:content');
        $tester = new CommandTester($command);
        $tester->execute([
            'class' => $class,
            '--filter' => $filters,
            '--order' => $orders,
        ], ['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE, 'interactive' => false]);

        self::assertStringMatchesFormat(<<<TXT

            Debug Stenope Contents
            ======================

            "$class" items
            --------------%A

            $expected

             ! [NOTE] Found %d items %w

            %A
            TXT
            , $tester->getDisplay(true));
    }

    public function provide testList data(): iterable
    {
        yield 'list' => [Author::class,
            <<<TXT
             * john.doe
             * ogi
             * tom32i
            TXT
        ];

        yield 'order' => [Author::class,
            <<<TXT
             * john.doe
             * ogi
             * tom32i
            TXT
        , [], ['slug'], ];

        yield 'order desc:' => [Author::class,
            <<<TXT
             * tom32i
             * ogi
             * john.doe
            TXT
        , [], ['desc:slug'], ];

        yield 'order desc (- notation)' => [Author::class,
            <<<TXT
             * tom32i
             * ogi
             * john.doe
            TXT
        , [], ['-slug'], ];

        yield 'filter property (data prefix)' => [Author::class,
            <<<TXT
             * ogi
             * tom32i
            TXT
        , ['_.core'], ];

        yield 'filter property (d prefix)' => [Author::class,
            <<<TXT
             * ogi
             * tom32i
            TXT
        , ['d.core'], ];

        yield 'filter property (_ prefix)' => [Author::class,
            <<<TXT
             * ogi
             * tom32i
            TXT
        , ['_.core'], ];

        yield 'filter not' => [Author::class,
            <<<TXT
             * john.doe
            TXT
        , ['not _.core'], ];

        yield 'filter not (! notation)' => [Author::class,
            <<<TXT
             * john.doe
            TXT
        , ['!_.core'], ];

        yield 'filter contains' => [Author::class,
            <<<TXT
             * ogi
             * tom32i
            TXT
            , ['contains(_.slug, "i")'], ];

        yield 'filter dates' => [Recipe::class,
            <<<TXT
             * ogito
             * tomiritsu
            TXT
            , ['_.date > date("2019-01-01") and _.date < date("2020-01-01")'], ];

        yield 'filter and order' => [Author::class,
            <<<TXT
             * tom32i
             * ogi
            TXT
        , ['_.core'], ['desc:slug'], ];

        yield 'multiple filters' => [Author::class,
            <<<TXT
             * ogi
            TXT
        , ['_.core', 'contains(_.slug, "gi")'], ];
    }

    public function testShow(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('debug:stenope:content');
        $tester = new CommandTester($command);
        $tester->execute([
            'class' => Author::class,
            'id' => 'ogi',
        ]);

        self::assertStringMatchesFormat(
            <<<TXT

            Debug Stenope Contents
            ======================

            "App\Model\Author" item with id "ogi"
            -------------------------------------

            App\Model\Author {
              +slug: "ogi"
              +firstname: "Maxime"
              +lastname: "Steinhausser"
              +nickname: "ogi"
              %A
            }

            %A
            TXT,
            $tester->getDisplay(true)
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testUnavailableExpressionLanguageHint(): void
    {
        ClassExistsMock::register(DebugCommand::class);
        ClassExistsMock::withMockedClasses([ExpressionLanguage::class => false]);

        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find('debug:stenope:content');
        $tester = new CommandTester($command);

        try {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('You must install the Symfony ExpressionLanguage component ("symfony/expression-language")');

            $tester->execute([
                'class' => Author::class,
                '--filter' => ['_.core'],
            ], ['verbosity' => ConsoleOutput::VERBOSITY_VERY_VERBOSE]);
        } finally {
            ClassExistsMock::withMockedClasses([ExpressionLanguage::class => true]);
        }
    }
}
