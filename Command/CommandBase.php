<?php

namespace Dorgflow\Command;

/**
 * Common base class for commands.
 */
class CommandBase {

  function __construct($container) {
    // Temporary, until the commands get specific services injected.
    $this->container = $container;
  }

}
