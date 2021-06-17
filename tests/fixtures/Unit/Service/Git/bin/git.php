#!/usr/bin/env php
<?php

/**
 * @see \Stenope\Bundle\Tests\Unit\Service\Git\LastModifiedFetcherTest
 */
$path = end($argv);

switch ($path) {
    case 'fail':
        exit(1);

    case 'empty':
        echo '';
        exit(0);

    default:
        echo '2021-06-14 10:25:47 +0200' . PHP_EOL;
        exit(0);
}
