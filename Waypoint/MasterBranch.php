<?php

namespace Dorgflow\Waypoint;

class MasterBranch {

  protected $branchName;

  protected $isCurrentBranch;

  function __construct(\Dorgflow\Situation $situation) {
    // TODO: check order of the branch -- should be higher version numbers first!
    $branch_list = $situation->GitBranchList->getBranchList();

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

    $this->isCurrentBranch = ($situation->GitBranchList->getCurrentBranch() == $this->branchName);

    // Set this on the situation object, as other things depend on it.
    $situation->setMasterBranch($this);
  }

  public function getBranchName() {
    return $this->branchName;
  }

  public function isCurrentBranch() {
    return $this->isCurrentBranch;
  }

}
