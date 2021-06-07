<?php

namespace App\Processor;

use App\Model\Page;
use Stenope\Bundle\Behaviour\ProcessorInterface;
use Stenope\Bundle\Content;

class DefaultTocProcessor implements ProcessorInterface
{
    private string $tableOfContentProperty;

    public function __construct(string $tableOfContentProperty = 'tableOfContent')
    {
        $this->tableOfContentProperty = $tableOfContentProperty;
    }

    public function __invoke(array &$data, Content $content): void
    {
        if (!is_a($content->getType(), Page::class, true)) {
            return;
        }

        if (!isset($data[$this->tableOfContentProperty])) {
            // By default, always generate a TOC for pages, with max depth of 3:
            $data[$this->tableOfContentProperty] = 3;
        }
    }
}
