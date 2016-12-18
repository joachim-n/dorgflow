#!/usr/bin/env php
<?php

// Just test stuff for now.
namespace Dorgflow;

print "Hello, this is Dorgflow!\n";

require_once __DIR__ . '/vendor/autoload.php';

// Helper objects to inject.
$git = new \Dorgflow\Executor\Git();
$situation = new \Dorgflow\Situation($git);
// $analyser = new Analyser... ?

// Figure out which command to run.
if (empty($argv[1])) {
  // If we're run with no parameter, we're creating a patch.
  $command = new Command\CreatePatch($situation, $git);
}
else {
  if ($argv[1] == 'cleanup') {
    $command = new Command\Cleanup($situation, $git);
  }
  elseif ($argv[1] == 'update') {
    $command = new Command\LocalUpdate($situation, $git);
  }
  elseif ($argv[1] == 'test') {
    $command = new Command\Test($situation, $git);
  }
  else {
    // If the parameter is something else, assume initial setup: the command
    // checks for a URL or issue number.
    $command = new Command\LocalSetup($situation, $git);
  }
}

$command->execute();
