<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

/**
 * Deletes the current feature branch.
 */
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
    $feature_branch_name = $feature_branch->getBranchName();

    print "You are about to checkout branch $master_branch_name and DELETE branch $feature_branch_name!\n";
    $confirmation = readline("Please enter 'delete' to confirm:");

    if ($confirmation != 'delete') {
      print "Clean up aborted.\n";
      return;
    }

    $master_branch_name = $master_branch->getBranchName();
    shell_exec("git checkout $master_branch_name");

    shell_exec("git branch -D $feature_branch_name");

    // TODO: delete any patch files for this issue.
  }

}
