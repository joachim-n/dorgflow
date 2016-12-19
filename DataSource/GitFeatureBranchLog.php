<?php

namespace Dorgflow\DataSource;

class GitFeatureBranchLog extends DataSourceBase {

  /**
   * Get the log data of the feature branch from the branch point with master.
   *
   * @return
   *  An array keyed by SHA, whose items are arrays with 'sha' and 'message'.
   */
  public function getFeatureBranchLog() {
    return $this->feature_branch_log;
  }

  /**
   * {@inheritdoc}
   */
  protected function parse() {
    $feature_branch_log = [];

    if (!empty($this->data)) {
      $git_log_lines = explode("\n", rtrim($this->data));
      foreach ($git_log_lines as $line) {
        list($sha, $message) = explode(' ', $line, 2);
        //dump("$sha ::: $message");

        // This gets used with array_shift(), so the key is mostly pointless.
        $feature_branch_log[$sha] = [
          'sha' => $sha,
          'message' => $message,
        ];
      }
    }

    $this->feature_branch_log = $feature_branch_log;
  }

}
