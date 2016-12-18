<?php

namespace Dorgflow\Waypoint;

class MasterBranch {

  protected $branchName;

  protected $isCurrentBranch;

  function __construct(\Dorgflow\Situation $situation) {
    // TODO: check order of the branch -- should be higher version numbers first!
    $branch_list = $situation->GitBranchList()->getBranchList();

    foreach ($branch_list as $branch) {
      // Identify the main development branch, of one of the following forms:
      //  - '7.x-1.x'
      //  - '7.x'
      //  - '8.0.x'
      if (preg_match("@(\d.x-\d+-x|\d.x|\d.\d+.x)@", $branch)) {
        $this->branchName = trim($branch);
        dump("master branch: $this->branchName");

        $found = TRUE;

        break;
      }
    }

    if (empty($found)) {
      // This should trigger a complete failure -- throw an exception!
      throw new \Exception("Can't find a master branch.");
    }

    $this->isCurrentBranch = ($situation->GitCurrentBranch()->getCurrentBranch() == $this->branchName);
  }

  public function getBranchName() {
    return $this->branchName;
  }

  public function isCurrentBranch() {
    return $this->isCurrentBranch;
  }

  public function checkOutFiles() {
    // In order to apply a patch, we change the files to look like the master
    // branch, while keeping the actual branch HEAD in the same place.
    // See http://stackoverflow.com/questions/13896246/reset-git-to-commit-without-changing-head-to-detached-state

    // Get the current SHA so that we can return to it.
    $current_sha = shell_exec("git rev-parse HEAD");

    // Reset the feature branch to the master branch tip commit. This puts the
    // files in the same state as the master branch.
    shell_exec("git reset --hard $this->branchName");

    // Move the branch reference back to where it was, but without changing the
    // files.
    shell_exec("git reset --soft $current_sha");
  }

}
