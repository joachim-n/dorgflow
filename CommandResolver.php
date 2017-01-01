<?php

namespace Dorgflow;

/**
 * Determines which command to run and creates it.
 */
class CommandResolver {

  /**
   * Determines which class should handle the command.
   *
   * @param $parameters
   *  Input parameters.
   *
   * @return
   *  The full command class name.
   */
  public function getCommandClass($parameters) {
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

    $full_class_name = "\\Dorgflow\\Command\\$command_class_name";
    return $full_class_name;
  }

}
