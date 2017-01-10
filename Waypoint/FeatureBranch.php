<?php

namespace Dorgflow\Waypoint;

use \Dorgflow\Situation;
use \Dorgflow\Executor\Git;

class FeatureBranch {

  protected $exists;

  protected $branchName;

  /**
   * The SHA for the tip commit of this branch.
   */
  protected $sha;

  function __construct($git_info, $analyser, $drupal_org, $git_exec) {
    $this->git_info = $git_info;
    $this->analyser = $analyser;
    $this->drupal_org = $drupal_org;
    $this->git_exec = $git_exec;

    $issue_number = $this->analyser->getIssueNumber();
    //dump($issue_number);
    if (empty($issue_number)) {
      // If we can't figure out an issue numbner, FAIL.
      throw new \Exception("Can't find an issue number.");
    }

    // Is there a branch for this issue number, that is not a tests branch?
    $branch_list = $this->git_info->getBranchList();
    //dump($branch_list);

    // Work over branch list.
    foreach ($branch_list as $sha => $branch) {
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
      $this->exists = FALSE;

      // Invent a branch name.
      $this->branchName = $this->createBranchName();
      //dump($this->branchName);
    }

    $this->isCurrentBranch = ($this->git_info->getCurrentBranch() == $this->branchName);

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

  /**
   * Invents a name to give the branch if it does not actually exist yet.
   */
  public function createBranchName() {
    $issue_number = $this->analyser->getIssueNumber();
    $issue_title = $this->drupal_org->getIssueNodeTitle();

    $pieces = preg_split('/\s+/', $issue_title);
    $pieces = preg_replace('/[[:^alnum:]]/', '', $pieces);
    array_unshift($pieces, $issue_number);

    $branch_name = implode('-', $pieces);

    return $branch_name;
  }

  public function createForkBranchName() {
    return $this->branchName . '-forked-' . time();
  }

  public function getBranchName() {
    return $this->branchName;
  }

  public function exists() {
    return $this->exists;
  }

  public function isCurrentBranch() {
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
    // Create a new branch and check it out.
    $this->git_exec->createNewBranch($this->branchName, TRUE);
  }

}
