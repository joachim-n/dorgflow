<?php

namespace Dorgflow;

/**
 * Determines which command to run and creates it.
 */
class CommandBuilder {

  /**
   * Creates a command handler.
   *
   * @param $parameters
   *  Input parameters.
   *
   * @return
   *  The command object.
   */
  public function getCommand($parameters) {
    if (empty($parameters[1])) {
      // If we're run with no parameter, we're creating a patch.
      $command_class_name = 'CreatePatch';
    }
    else {
      if ($parameters[1] == 'cleanup') {
        $command_class_name = 'Cleanup';
      }
      elseif ($parameters[1] == 'update') {
        $command_class_name = 'LocalUpdate';
      }
      elseif ($parameters[1] == 'test') {
        $command_class_name = 'Test';
      }
      else {
        // If the parameter is something else, assume initial setup: the command
        // checks for a URL or issue number.
        $command_class_name = 'LocalSetup';
      }
    }

    $command = $this->createCommand($command_class_name);
    return $command;
  }

  protected function createCommand($command_class_name) {
    // Helper objects to inject.
    // TODO: use a dedicated container class and inject that instead?
    $git = new \Dorgflow\Executor\Git();
    $situation = new \Dorgflow\Situation($git);
    // $analyser = new Analyser... ?

    $full_class_name = "\\Dorgflow\\Command\\$command_class_name";
    $command = new $full_class_name($situation, $git);

    return $command;
  }

}
