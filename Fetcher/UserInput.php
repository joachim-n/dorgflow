<?php

namespace Dorgflow\Fetcher;

// todo!!!
class UserInput {

  public function getIssueNumber() {
    // TODO: allow for a URL as input.
    if (empty($argv[1]) && is_numeric($argv[1])) {
      return $argv[1];
    }
  }

}
