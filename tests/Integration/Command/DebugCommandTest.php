<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Stenope\Bundle\Tests\Integration\Command;

use App\Model\Author;
use Stenope\Bundle\Command\DebugCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class DebugCommandTest extends KernelTestCase
{
    /**
     * @dataProvider provide testList data
     */
    public function testList(string $class, string $expected, array $filters = [], array $orders = []): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find(DebugCommand::getDefaultName());
        $tester = new CommandTester($command);
        $tester->execute([
            'class' => $class,
            '--filter' => $filters,
            '--order' => $orders,
        ]);

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
             * tom32i
             * john.doe
             * ogi
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

        yield 'filter' => [Author::class,
            <<<TXT
             * tom32i
             * ogi
            TXT
        , ['core'], ];

        yield 'filter not:' => [Author::class,
            <<<TXT
             * john.doe
            TXT
        , ['not:core'], ];

        yield 'filter not: (! notation)' => [Author::class,
            <<<TXT
             * john.doe
            TXT
        , ['!core'], ];

        yield 'contains:' => [Author::class,
            <<<TXT
             * tom32i
             * ogi
            TXT
            , ['slug contains:i'], ];

        yield 'filter and order' => [Author::class,
            <<<TXT
             * ogi
             * tom32i
            TXT
        , ['core'], ['slug'], ];

        yield 'multiple filters' => [Author::class,
            <<<TXT
             * ogi
            TXT
        , ['core', 'slug contains:gi'], ];
    }

    public function testShow(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $command = $application->find(DebugCommand::getDefaultName());
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
}
