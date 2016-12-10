<?php

namespace Dorgflow\DataSource;

class GitFeatureBranchLog extends DataSourceBase {

  public function getFeatureBranchLog() {
    $feature_branch_log = [];

    $git_log_lines = explode("\n", rtrim($this->data));
    foreach ($git_log_lines as $line) {
      list($sha, $message) = explode(' ', $line, 2);
      //dump("$sha ::: $message");

      $feature_branch_log[$sha] = $message;
    }

    return $feature_branch_log;
  }

}
