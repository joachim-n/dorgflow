<?php

namespace Dorgflow\Fetcher;

// todo!!!
class UserInput {

  public function getIssueNumber() {
    global $argv;
    if (!empty($argv[1])) {
      if (is_numeric($argv[1])) {
        return $argv[1];
      }

      // If the param is a URL, get the node ID from the end of it.
      $matches = [];
      if (preg_match("@www\.drupal\.org/node/(?P<number>\d+)$@", $argv[1], $matches)) {
        return $matches['number'];
      }
    }
  }

}
