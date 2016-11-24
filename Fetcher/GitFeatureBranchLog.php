<?php

namespace Dorgflow\Fetcher;

class GitFeatureBranchLog {
  
  public function getFeatureBranchLog($master_branch) {
    $master_branch_name = $master_branch->getBranchName();

    $git_log = shell_exec("git log $master_branch_name..HEAD --pretty=oneline --reverse");
    // todo: caching
    
    $feature_branch_log = [];
    
    // TODO: the rest belongs in parser.
    $git_log_lines = explode("\n", rtrim($git_log));
    foreach ($git_log_lines as $line) {      
      list($sha, $message) = explode(' ', $line, 2);
      //dump("$sha ::: $message");
      
      $feature_branch_log[$sha] = $message;
    }

    return $feature_branch_log;
  }
  
  
}
