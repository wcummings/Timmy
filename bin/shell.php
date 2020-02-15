#!/usr/bin/env php
<?php
namespace Timmy;

// Require psysh and all our code
$files = glob('lib/*.php');
$files[] = 'bin/psysh';
foreach ($files as $file) {
    require($file);   
}

echo __NAMESPACE__ . " shell\n";

$sh = new \Psy\Shell();

if (defined('__NAMESPACE__') && __NAMESPACE__ !== '') {
    $sh->addCode(sprintf("namespace %s;", __NAMESPACE__));
}

$sh->run();

echo "Bye.\n";
