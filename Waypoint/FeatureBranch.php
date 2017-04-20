<?php

namespace Dorgflow\Waypoint;

class FeatureBranch {

  protected $exists;

  protected $branchName;

  protected $isCurrentBranch;

  /**
   * The SHA for the tip commit of this branch.
   */
  protected $sha;

  function __construct($git_info, $analyser, $drupal_org, $git_exec) {
    $this->git_info = $git_info;
    $this->analyser = $analyser;
    $this->drupal_org = $drupal_org;
    $this->git_exec = $git_exec;

    $issue_number = $this->analyser->deduceIssueNumber();

    // Is there a branch for this issue number, that is not a tests branch?
    $branch_list = $this->git_info->getBranchList();
    //dump($branch_list);

    // Work over branch list.
    foreach ($branch_list as $branch => $sha) {
      if (substr($branch, 0, strlen($issue_number)) == $issue_number &&
        substr($branch, -6) != '-tests') {
        $this->exists = TRUE;
        // Set the current branch as WHAT WE ARE.
        $this->sha = $sha;
        $this->branchName = $branch;
        //dump('found!');
        //dump($this->branchName);

        break;
      }
    }

    if (empty($this->exists)) {
      // If we didn't find a branch for the issue number, then set ourselves
      // as not existing yet.
      $this->exists = FALSE;
      // Note we don't set the branch name at this point, as it incurs a call to
      // drupal.org, and it might be that we don't need it (such as if the
      // command fails for some reason).
    }

    // if current branch NOT feature branch, problem?
    // no, leave that to the command to determine.
  }

  /**
   * Returns the SHA for the commit for this patch, or NULL if not committed.
   *
   * @return string|null
   *  The SHA, or NULL if this patch has no corresponding commit.
   */
  public function getSHA() {
    return $this->sha;
  }

  public function createForkBranchName() {
    return $this->getBranchName() . '-forked-' . time();
  }

  public function getBranchName() {
    // If the branch name hasn't been set yet, it's because the branch doesn't
    // exist, and we need to invent the name.
    if (empty($this->branchName)) {
      // Invent a branch name.
      $this->branchName = $this->createBranchName();
      //dump($this->branchName);
    }

    return $this->branchName;
  }

  /**
   * Invents a name to give the branch if it does not actually exist yet.
   *
   * @return string
   *  The proposed branch name.
   */
  protected function createBranchName() {
    $issue_number = $this->analyser->deduceIssueNumber();
    $issue_title = $this->drupal_org->getIssueNodeTitle();

    $pieces = preg_split('/\s+/', $issue_title);
    $pieces = preg_replace('/[[:^alnum:]]/', '', $pieces);
    array_unshift($pieces, $issue_number);

    $branch_name = implode('-', $pieces);

    return $branch_name;
  }

  /**
   * Returns whether the branch exists in git.
   *
   * @return bool
   *  TRUE if the branch exists, FALSE if not.
   */
  public function exists() {
    return $this->exists;
  }

  /**
   * Returns whether the branch is currently checked out in git.
   *
   * @return bool
   *  TRUE if the branch is current, FALSE if not.
   */
  public function isCurrentBranch() {
    // If the branch doesn't exist, it can't be current.
    if (!$this->exists) {
      return FALSE;
    }

    // If the property hasn't been set yet, determine its value from git.
    if (!isset($this->isCurrentBranch)) {
      $this->isCurrentBranch = ($this->git_info->getCurrentBranch() == $this->branchName);
    }

    return $this->isCurrentBranch;
  }

  public function getBranchDescription() {
    $matches = [];
    preg_match("@\d+-?(?P<description>.+)@", $this->branchName, $matches);

    if (!empty($matches['description'])) {
      $branch_description = $matches['description'];
      return $branch_description;
    }
  }

  public function gitCreate() {
    // Get the branch name from the method, so this lazy-loads.
    $branch_name = $this->getBranchName();

    // Create a new branch and check it out.
    $this->git_exec->createNewBranch($branch_name, TRUE);

    // This now exists.
    $this->exists = TRUE;
  }

  /**
   * Checks out the branch.
   */
  public function gitCheckout() {
    // No need to do anything if the branch is current.
    if ($this->isCurrentBranch()) {
      return;
    }

    $branch_name = $this->getBranchName();
    $this->git_exec->checkOutBranch($branch_name);
  }

}
