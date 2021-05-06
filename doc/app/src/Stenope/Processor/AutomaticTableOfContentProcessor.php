<?php

declare(strict_types=1);

namespace App\Stenope\Processor;

use App\Model\Page;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;

/**
 * Build a table of content from the content titles
 */
class AutomaticTableOfContentProcessor implements ProcessorInterface
{
    private string $tableOfContentProperty;

    public function __construct(string $tableOfContentProperty = 'tableOfContent')
    {
        $this->tableOfContentProperty = $tableOfContentProperty;
    }

    public function __invoke(array &$data, string $type, Content $content): void
    {
        if (!is_a($type, Page::class, true)) {
            return;
        }

        $data[$this->tableOfContentProperty] = 3;
    }
}
