<?php

namespace Dorgflow\Service;

/**
 * Handles user input.
 */
class UserInput {

  public function getIssueNumber() {
    global $argv;

    if (!empty($argv[1])) {
      if (is_numeric($argv[1])) {
        $this->issueNumber = $argv[1];
      }
      else {
        // If the param is a URL, get the node ID from the end of it.
        // Allow an #anchor at the end of the URL so users can copy and paste it
        // when it has a #new or #ID link.
        $matches = [];
        // Built-in node URL.
        if (preg_match("@www\.drupal\.org/node/(?P<number>\d+)(#.*)?$@", $argv[1], $matches)) {
          $this->issueNumber = $matches['number'];
        }
        // Issue node path alias with the project name in the path.
        if (preg_match("@www\.drupal\.org/project/(?:\w+)/issues/(?P<number>\d+)(#.*)?$@", $argv[1], $matches)) {
          $this->issueNumber = $matches['number'];
        }
      }
    }

    // If nothing worked, set it to FALSE so we don't repeat the work here
    // another time.
    if (!isset($this->issueNumber)) {
      $this->issueNumber = FALSE;
    }
      
    return $this->issueNumber;
  }

}
