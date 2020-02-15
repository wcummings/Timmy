#!/usr/bin/env php
<?php
namespace Timmy;

if (php_sapi_name() !== 'cli') {
    echo "Go play outside";
    exit(0);
}

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
