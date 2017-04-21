<?php

namespace Dorgflow\Service;

/**
 * Provides log data from git.
 */
class GitLog {

  protected $feature_branch_log;

  function __construct($waypoint_manager_branches) {
    $this->waypoint_manager_branches = $waypoint_manager_branches;
  }

  /**
   * Get the log data of the feature branch from the branch point with master.
   *
   * @return
   *  An array keyed by SHA, whose items are arrays with 'sha' and 'message'.
   *  The items are arranged in progressing order, that is, older commits first.
   */
  public function getFeatureBranchLog() {
    if (!isset($this->feature_branch_log)) {
      $master_branch_name = $this->waypoint_manager_branches->getMasterBranch()->getBranchName();
      // TODO! Complain if $feature_branch_name doesn't exist yet!
      $feature_branch_name = $this->waypoint_manager_branches->getFeatureBranch()->getBranchName();

      $log = $this->getLog($master_branch_name, $feature_branch_name);
      $this->feature_branch_log = $this->parseLog($log);
    }

    return $this->feature_branch_log;
  }

  /**
   * Get the log data of the feature branch from a given point.
   *
   * @param $commit
   *  The older commit to start the log after. This is not included.
   *  TODO: change this to be a Waypoint object.
   *
   * @return
   *  An array keyed by SHA, whose items are arrays with 'sha' and 'message'.
   *  The items are arranged in progressing order, that is, older commits first.
   */
  public function getPartialFeatureBranchLog($commit) {
    // This only gets called once, no need to cache.

    // TODO! Complain if $feature_branch_name doesn't exist yet!
    $feature_branch_name = $this->waypoint_manager_branches->getFeatureBranch()->getBranchName();

    $log = $this->getLog($commit, $feature_branch_name);

    return $this->parseLog($log);
  }

  /**
   * Gets the raw git log from one commit to another.
   *
   * @param $old
   *  The older commit. This is not included in the log.
   * @param $new
   *  The recent commit. This is included in the log.
   *
   * @return
   *  The raw output from git rev-list.
   */
  protected function getLog($old, $new) {
    $git_log = shell_exec("git rev-list {$new} ^{$old} --pretty=oneline --reverse");

    return $git_log;
  }

  /**
   * Parse raw git log output into structured data.
   *
   * @param string $log
   *  The log output, as given by 'git rev-list --pretty=oneline'.
   *
   * @return
   *  An array keyed by SHA, where each item is an array with:
   *    - 'sha': The SHA.
   *    - 'message': The commit message.
   */
  protected function parseLog($log) {
    $feature_branch_log = [];

    if (!empty($log)) {
      $git_log_lines = explode("\n", rtrim($log));
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

    return $feature_branch_log;
  }

}
