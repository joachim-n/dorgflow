<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

class Cleanup {
  
  public function __construct(Situation $situation) {
    $this->situation = $situation;
  }

  public function execute() {
    $situation = $this->situation;
    
    // TODO: stop if git not clean

    $master_branch = $situation->getMasterBranch();
    $feature_branch = $situation->getFeatureBranch();

    $master_branch_name = $master_branch->getBranchName();
    shell_exec("git checkout $master_branch_name");

    // TODO: confirmation!!!!!!!!!!!!!!

    $feature_branch_name = $feature_branch->getBranchName();
    shell_exec("git branch -D $feature_branch_name");
  }
  
}
