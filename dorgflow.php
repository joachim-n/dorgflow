#!/usr/bin/env php
<?php

// Can't run as Drush script, as __DIR__ doesn't work with that.

// Just test stuff for now.
namespace Dorgflow;

print "Hello, this is Dorgflow!\n";

require_once __DIR__ . '/vendor/autoload.php';

$a = new Analyser;
$a->doStuff();


