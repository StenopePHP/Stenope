<?php

$header = <<<'EOF'
This file is part of the "Tom32i/Content" bundle.

@author Thomas Jarrand <thomas.jarrand@gmail.com>
EOF;

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__])
    ->exclude('tests/fixtures/app/var')
    ->exclude('tests/fixtures/app/build')
;

return PhpCsFixer\Config::create()
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@Symfony' => true,
        'php_unit_namespaced' => true,
        'psr0' => false,
        'concat_space' => ['spacing' => 'one'],
        'phpdoc_summary' => false,
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_order' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'simplified_null_return' => false,
        'header_comment' => ['header' => $header],
        'yoda_style' => null,
        'no_superfluous_phpdoc_tags' => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'void_return' => true,
        'single_line_throw' => false,
    ])
;
