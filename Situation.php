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
    // Try to deduce an issue number from the current branch.
    $current_branch = $this->GitBranchList->getCurrentBranch();

    // TODO: analysis should be in Fetcher/Parser classes!
    $matches = [];
    preg_match("@^(?P<number>\d+)-@", $current_branch, $matches);
    if (!empty($matches['number'])) {
      return $matches['number'];
    }

    $issue_number = $this->UserInput->getIssueNumber();

    if (!empty($issue_number)) {
      return $issue_number;
    }
    else {
      // TEMP! TESTING!
      return 2801423;

      throw new \Exception("Unable to find an issue number from command line parameter.");
    }
  }

  public function setUpMasterBranch() {
    $this->masterBranch = new \Dorgflow\Waypoint\MasterBranch($this);

    return $this->masterBranch;
  }

  public function setUpFeatureBranch() {
    $feature_branch = new Waypoint\FeatureBranch($this);

    return $feature_branch;
  }

  public function setUpPatches() {
    $patches = [];

    do {
      $patch = new Waypoint\Patch($this);

      if ($patch->cancel) {
        if ($patch->status == 'skip') {
          continue;
        }

        break;
      }

      $patches[] = $patch;

    } while (TRUE);

    return $patches;
  }

  public function getFeatureBranchLog() {
    // return array
    // SHA => commit message
    // Oldest first.


    // ARGH need MastBranch!!!
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
