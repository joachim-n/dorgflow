<?php

namespace Dorgflow\Command;

/**
 * Deletes the current feature branch.
 */
class Cleanup extends CommandBase {

  public function execute() {
    // TEMPORARY: get services from the container.
    // @todo inject these.
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');

    // Check git is clean.
    $clean = $this->git_info->gitIsClean();
    if (!$clean) {
      throw new \Exception("Git repository is not clean. Aborting.");
    }

    $master_branch = $this->waypoint_manager_branches->getMasterBranch();
    $feature_branch = $this->waypoint_manager_branches->getFeatureBranch();

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
