<?php

namespace Dorgflow\Waypoint;

class FeatureBranch {

  protected $exists;

  protected $branchName;

  function __construct(\Dorgflow\Situation $situation) {
    $this->situation = $situation;

    $issue_number = $situation->getIssueNumber();
    //dump($issue_number);
    if (empty($issue_number)) {
      // If we can't figure out an issue numbner, FAIL.
      throw new \Exception("Can't find an issue number.");
    }

    // Is there a branch for this issue number, that is not a tests branch?
    $branch_list = $situation->GitBranchList()->getBranchList();
    //dump($branch_list);

    // Work over branch list.
    foreach ($branch_list as $branch) {
      if (substr($branch, 0, strlen($issue_number)) == $issue_number &&
        substr($branch, -6) != '-tests') {
        $this->exists = TRUE;
        // Set the current branch as WHAT WE ARE.
        $this->branchName = $branch;
        //dump('found!');
        //dump($this->branchName);

        break;
      }
    }

    if (empty($this->exists)) {
      $this->exists = FALSE;

      // Invent a branch name.
      $issue_title = $situation->DrupalOrgIssueNode()->getIssueNodeTitle();

      $issue_title = str_replace([',', "'", '"', '.', '\\', '/'], '', $issue_title);
      $issue_title = str_replace(['-', '_'], ' ', $issue_title);
      $pieces = preg_split('/\s+/', $issue_title);
      array_unshift($pieces, $issue_number);

      $this->branchName = implode('-', $pieces);
      //dump($this->branchName);
    }

    $this->isCurrentBranch = ($situation->GitCurrentBranch()->getCurrentBranch() == $this->branchName);

    // if current branch NOT feature branch, problem?
    // no, leave that to the command to determine.
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
    $this->situation->git()->createNewBranch($this->branchName, TRUE);
  }

}
