#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Bridge\Twig\Command\LintCommand;
use Symfony\Component\Console\Application;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

(new Application('twig/lint'))
    ->add(new LintCommand(new Environment(new FilesystemLoader())))
    ->getApplication()
    ->setDefaultCommand('lint:twig', true)
    ->run();
