#!/usr/bin/env php
<?php

/*
 * This file is part of the "StenopePHP/Stenope" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

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
