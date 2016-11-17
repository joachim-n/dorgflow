<?php

namespace Dorgflow\Waypoint;

class MasterBranch {

  function __construct(\Dorgflow\Situation $situation) {
    return;
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
