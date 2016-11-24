<?php

namespace Dorgflow;

// Wrapper around fetchers and parsers.
// Is injected into Waypoints
class Situation {

  protected $fetchers = [];

  public function getIssueNumber() {
    return 123456;


    // Try git current branch first

    // Then user input - d.org node
  }

  public function getBranchList() {
    $fetcher = $this->getFetcher('GitBranchList');
    return $fetcher->getBranchList();
  }

  public function getCurrentBranch() {
    $fetcher = $this->getFetcher('GitBranchList');
    // ARGH!
    return $fetcher->getCurrentBranch();
  }
  
  public function getFeatureBranchLog($master_branch) {
    // return array
    // SHA => commit message
    // Oldest first.
    
    
    // ARGH need FeatureBranch!!
    
    $fetcher = $this->getFetcher('GitFeatureBranchLog');
    return $fetcher->getFeatureBranchLog($master_branch);
  }

  public function getIssueNodeTitle() {
    return $this->getFetcher('DrupalOrgIssueNode')->getIssueNodeTitle();
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
