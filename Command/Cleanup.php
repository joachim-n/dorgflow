<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

class Cleanup extends CommandBase {

  public function execute() {
    $situation = $this->situation;

    // Check git is clean.
    $clean = $situation->GitStatus()->gitIsClean();
    if (!$clean) {
      throw new \Exception("Git repository is not clean. Aborting.");
    }

    $master_branch = $situation->getMasterBranch();
    $feature_branch = $situation->getFeatureBranch();

    $master_branch_name = $master_branch->getBranchName();
    shell_exec("git checkout $master_branch_name");

    // TODO: confirmation!!!!!!!!!!!!!!

    $feature_branch_name = $feature_branch->getBranchName();
    shell_exec("git branch -D $feature_branch_name");
  }

}
