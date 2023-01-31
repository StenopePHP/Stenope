<?php

$header = <<<'EOF'
This file is part of the "StenopePHP/Stenope" bundle.

@author Thomas Jarrand <thomas.jarrand@gmail.com>
EOF;

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__])
    ->exclude('doc/app')
    ->exclude('tests/fixtures/app/var')
    ->exclude('tests/fixtures/app/build')
;

return (new PhpCsFixer\Config)
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setFinder($finder)
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'blank_line_between_import_groups' => false,
        'concat_space' => ['spacing' => 'one'],
        'header_comment' => ['header' => $header],
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
        'ordered_imports' => true,
        'php_unit_namespaced' => true,
        'php_unit_method_casing' => false,
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_summary' => false,
        'phpdoc_order' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'psr_autoloading' => true,
        'single_line_throw' => false,
        'simplified_null_return' => false,
        'void_return' => true,
        'yoda_style' => [],

        // @see https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/5495
        'binary_operator_spaces' => ['operators' => ['|' => null]]
    ])
;
