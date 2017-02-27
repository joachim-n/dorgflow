<?php

namespace Dorgflow\Waypoint;

class MasterBranch {

  protected $branchName;

  protected $isCurrentBranch;

  // TODO: typehints
  function __construct($git_info, $git_executor) {
    $this->git_info = $git_info;
    $this->git_executor = $git_executor;

    $branch_list = $this->git_info->getBranchList();

    // Sort the branches by version number, with highest first.
    uksort($branch_list, 'version_compare');
    $branch_list = array_reverse($branch_list);

    foreach ($branch_list as $branch => $sha) {
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

    $this->isCurrentBranch = ($this->git_info->getCurrentBranch() == $this->branchName);
  }

  public function getBranchName() {
    return $this->branchName;
  }

  public function isCurrentBranch() {
    return $this->isCurrentBranch;
  }

  public function checkOutFiles() {
    $this->git_executor->checkOutFiles($this->branchName);
  }

}
