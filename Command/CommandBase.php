<?php

namespace Dorgflow\Command;

use \Dorgflow\Situation;
use \Dorgflow\Executor\Git;

/**
 * Common base class for commands.
 */
class CommandBase {

  protected $situation;

  protected $git;

  function __construct(Situation $situation, Git $git, $container) {
    $this->situation = $situation;
    $this->git = $git;

    // Temporary, until the commands get specific services injected.
    $this->container = $container;
  }

}
