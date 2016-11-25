<?php

namespace Dorgflow;

// Wrapper around fetchers and parsers.
// Is injected into Waypoints
class Situation {

  protected $fetchers = [];

  /**
   * Magic method: get a data fetcher.
   */
  public function __get($name) {
    return $this->getFetcher($name);
  }

  /**
   * Get the issue number.
   */
  public function getIssueNumber() {
    return 123456;


    // Try git current branch first

    // Then user input - d.org node
  }

  public function setMasterBranch($master_branch) {
    $this->masterBranch = $master_branch;
  }

  public function getFeatureBranchLog() {
    // return array
    // SHA => commit message
    // Oldest first.


    // ARGH need FeatureBranch!!

    $fetcher = $this->getFetcher('GitFeatureBranchLog');
    return $fetcher->getFeatureBranchLog($this->masterBranch);
  }

  /**
   *
   */
  protected function getFetcher($name) {
    // statically cache fetchers, as they statically cache their own data.
    if (!isset($fetchers[$name])) {

      $fetcher_full_class_name = 'Dorgflow\\Fetcher\\' . $name;
      $fetchers[$name] = new $fetcher_full_class_name();
    }

    return $fetchers[$name];
  }

}
