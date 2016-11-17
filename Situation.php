<?php

namespace Dorgflow;

// Wrapper around fetchers and parsers.
// Is injected into Waypoints
class Situation {
  
  protected $fetchers = [];
  
  public function getIssueNumber() {
    return 12345;
    // Try git branch list first
    
    // Now try d.org
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
  
  protected function getFetcher($name) {
    // statically cache fetchers, as they statically cache their own data.
    if (!isset($fetchers[$name])) {
      
      $fetcher_full_class_name = 'Dorgflow\\Fetcher\\' . $name;
      $fetchers[$name] = new $fetcher_full_class_name();
    }
    
    return $fetchers[$name];
  }
  
}
