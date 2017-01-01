<?php

namespace Dorgflow\Waypoint;

use \Dorgflow\Situation;
use \Dorgflow\Executor\Git;

class MasterBranch {

  protected $branchName;

  protected $isCurrentBranch;

  function __construct(Situation $situation, Git $git) {
    $this->situation = $situation;
    $this->git = $git;

    // TODO: check order of the branch -- should be higher version numbers first!
    $branch_list = $situation->GitBranchList()->getBranchList();

    foreach ($branch_list as $branch) {
      // Identify the main development branch, of one of the following forms:
      //  - '7.x-1.x'
      //  - '7.x'
      //  - '8.0.x'
      if (preg_match("@(\d.x-\d+-x|\d.x|\d.\d+.x)@", $branch)) {
        $this->branchName = trim($branch);

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
    $this->git->checkOutFiles($this->branchName);
  }

}
