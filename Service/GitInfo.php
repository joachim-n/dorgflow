<?php

namespace Dorgflow\Service;

/**
 * Provides general information from git such as branch list and status.
 */
class GitInfo {

  protected $is_clean;

  /**
   * Returns whether the current git repository is clean.
   *
   * @return bool
   *  TRUE if clean, FALSE if local files have changes.
   */
  public function gitIsClean() {
    if (!isset($this->is_clean)) {
      $diff_files = shell_exec("git diff-files");

      $this->is_clean = (empty($diff_files));
    }

    return $this->is_clean;
  }

  public function getCurrentBranch() {
    // TODO cache
    $current_branch = trim(shell_exec("git symbolic-ref --short -q HEAD"));

    return $current_branch;
  }


  /**
   * Returns a list of all the git branches which are currently reachable.
   *
   * @return
   *  An array of branch names keyed by the SHA of the tip commit.
   */
  public function getBranchList() {
    // TODO: caching!

    // TODO: check in right dir!

    $branch_list = [];

    // Get the list of local branches as 'SHA BRANCHNAME'.
    $refs = shell_exec("git for-each-ref refs/heads/ --format='%(objectname) %(refname:short)'");
    foreach (explode("\n", trim($refs)) as $line) {
      list($sha, $branch_name) = explode(' ', $line);

      $output = '';
      // Exit value is 0 if true, 1 if false.
      $return_var = '';
      // @todo bug in the special case that the feature branch is at the same
      // place as the master branch!
      exec("git merge-base --is-ancestor $branch_name HEAD", $output, $return_var);

      if ($return_var === 0) {
        $branch_list[$sha] = $branch_name;
      }
    }

    return $branch_list;
  }

}
