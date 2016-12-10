<?php

namespace Dorgflow\DataSource;

class GitFeatureBranchLog extends DataSourceBase {

  public function getFeatureBranchLog() {
    return $this->feature_branch_log;
  }

  /**
   * {@inheritdoc}
   */
  protected function parse() {
    $feature_branch_log = [];

    $git_log_lines = explode("\n", rtrim($this->data));
    foreach ($git_log_lines as $line) {
      list($sha, $message) = explode(' ', $line, 2);
      //dump("$sha ::: $message");

      $feature_branch_log[$sha] = $message;
    }

    $this->feature_branch_log = $feature_branch_log;
  }

}
