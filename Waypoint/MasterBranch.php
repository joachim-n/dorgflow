<?php

namespace Dorgflow\Waypoint;

class MasterBranch {

  protected $branchName;

  function __construct(\Dorgflow\Situation $situation) {
    $branch_list = $situation->getBranchList();

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
  }

  public function getBranchName() {
    return $this->branchName;
  }

}
