#!/usr/bin/env php
<?php

// Just test stuff for now.
namespace Dorgflow;

print "Hello, this is Dorgflow!\n";

require_once __DIR__ . '/vendor/autoload.php';

// Figure out which command to run.
if (empty($argv[1])) {
  // If we're run with no parameter, we're creating a patch.
  $command = new Command\CreatePatch;
}
else {
  // For now, the only other thing we support is an update.
  $command = new Command\LocalUpdate;
}

$command->execute();
