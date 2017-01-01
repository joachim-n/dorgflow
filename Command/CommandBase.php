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

  function __construct(Situation $situation, Git $git) {
    $this->situation = $situation;
    $this->git = $git;
  }

}
