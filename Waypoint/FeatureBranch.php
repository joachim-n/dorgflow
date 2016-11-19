<?php

namespace Dorgflow\Waypoint;

class FeatureBranch {

  protected $exists;

  function __construct(\Dorgflow\Situation $situation) {
    $issue_number = $situation->getIssueNumber();
    dump($issue_number);
    if (empty($issue_number)) {
      // If we can't figure out an issue numbner, FAIL.
      throw new \Exception("Can't find an issue number.");
    }

    // Is there a branch for this issue number, that is not a tests branch?
    $branch_list = $situation->getBranchList();
    dump($branch_list);

    // Work over branch list.
    foreach ($branch_list as $branch) {
      if (substr($branch, 0, strlen($issue_number)) == $issue_number &&
        substr($branch, -6) != '-tests') {
        $this->exists = TRUE;
        // Set the current branch as WHAT WE ARE.
        $this->branchName = $branch;
        dump('found!');
        dump($this->branchName);
        
        break;
      }
    }

    if (empty($this->exists)) {
      $this->exists = FALSE;

      // Invent a branch name.
      $issue_title = $situation->getIssueNodeTitle();
      
      $issue_title = str_replace([',', "'", '"'], '', $issue_title);
      $issue_title = str_replace(['-', '_'], ' ', $issue_title);
      $pieces = preg_split('/\s+/', $issue_title);
      array_unshift($pieces, $issue_number);

      $this->branchName = implode('-', $pieces);
      dump($this->branchName);
    }


    return; 
    $current_branch = $situation->getCurrentBranch();
    dump($current_branch);

    // if current branch NOT feature branch, problem?
    // no, leave that to the command to determine.


    return;

    /////////////////////////////////////




    foreach ($branch_list as $branch) {
      // Identify the main development branch, of one of the following forms:
      //  - '7.x-1.x'
      //  - '7.x'
      //  - '8.0.x'
      if (preg_match("@(\d.x-\d+-x|\d.x|\d.\d+.x)@", $branch)) {
        $this->masterName = trim($branch);
        dump("master branch: $this->masterName");

        $this->exists = TRUE;

        return;
      }
    }

    // If we get here, then we didn't find a branch: fail.
    $this->exists = FALSE;
    // TODO: this should trigger a complete failure -- throw an exception!
    throw new \Exception("Can't find a master branch.");
  }

  public function getBranchName() {
    return $this->masterName;
  }

}
