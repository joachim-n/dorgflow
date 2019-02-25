<?php

namespace Dorgflow\Service;

/**
 * Provides general information from git such as branch list and status.
 */
class GitInfo {

  protected $is_clean;

  protected $current_branch;

  protected $branch_list;

  protected $branch_list_reachable;

  /**
   * Returns whether the current git repository is clean.
   *
   * @return bool
   *  TRUE if clean, FALSE if local files have changes.
   */
  public function gitIsClean() {
    if (!isset($this->is_clean)) {
      // Unconfuse 'git diff-files', which sees a moved file as having a diff
      // even if the contents are the same.
      // See https://stackoverflow.com/questions/36367190/git-diff-files-output-changes-after-git-status
      shell_exec("git update-index --refresh");

      $diff_files = shell_exec("git diff-files");

      $this->is_clean = (empty($diff_files));
    }

    return $this->is_clean;
  }

  /**
   * Returns the diff to the given branch.
   *
   * TODO: consider whether to move this to the branch object.
   *
   * @param string $branch
   *  The branch name to diff against.
   *
   * @return string
   *  The diff output, with colour.
   */
  public function diffMasterBranch($branch) {
    $diff = shell_exec("git diff-index --color -p {$branch}");

    return $diff;
  }

  public function getCurrentBranch() {
    if (!isset($this->current_branch)) {
      $this->current_branch = trim(shell_exec("git symbolic-ref --short -q HEAD"));
    }

    return $this->current_branch;
  }

  /**
   * Clears the cached value for the current branch.
   *
   * The Git Executor service must call this whenever it changes the branch.
   */
  public function invalidateCurrentBranchCache() {
    unset($this->current_branch);
  }

  /**
   * Returns a list of all the git branches.
   *
   * @return
   *  An array whose keys are branch names, and values are the SHA of the tip.
   */
  public function getBranchList() {
    if (!isset($this->branch_list)) {
      // TODO: check in right dir!

      $branch_list = [];

      // Get the list of local branches as 'SHA BRANCHNAME'.
      $refs = shell_exec('git for-each-ref refs/heads/ "--format=%(objectname) %(refname:short)"');
      foreach (explode("\n", trim($refs)) as $line) {
        list($sha, $branch_name) = explode(' ', $line);

        $branch_list[$branch_name] = $sha;
      }

      $this->branch_list = $branch_list;
    }

    return $this->branch_list;
  }

  /**
   * Returns a list of all the git branches which are currently reachable.
   *
   * @return
   *  An array whose keys are branch names, and values are the SHA of the tip.
   */
  public function getBranchListReachable() {
    if (!isset($this->branch_list_reachable)) {
      $branch_list_reachable = [];

      foreach ($this->getBranchList() as $branch_name => $sha) {
        // TODO: use isAncestor().
        $output = '';
        // Exit value is 0 if true, 1 if false.
        $return_var = '';
        exec("git merge-base --is-ancestor $branch_name HEAD", $output, $return_var);

        if ($return_var === 0) {
          $branch_list_reachable[$branch_name] = $sha;
        }
      }

      $this->branch_list_reachable = $branch_list_reachable;
    }

    return $this->branch_list_reachable;
  }

  /**
   * Determines whether one commit is the ancestor of another.
   *
   * @param string $ancestor
   *  The potential ancestor commit.
   * @param string $child
   *  The potential child commit.
   *
   * @return bool
   *  TRUE if $ancestor is reachable from $child, FALSE if not.
   */
  public function isAncestor($ancestor, $child) {
    // Exit value is 0 if true, 1 if false.
    $return_var = '';
    exec("git merge-base --is-ancestor $ancestor $child", $output, $return_var);

    if ($return_var === 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
