<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

class LocalUpdate {

  public function execute() {
    /*
      run the analyer to get stuff
      run over the waypoint objects:
        - report whether it exists locally
        - create itself if not.

    */

    $situation = new Situation();

    // Create waypoints.
    $master_branch = $situation->setUpMasterBranch();

    // If the master branch is not current, abort.
    // TODO: support updating an existing feature branch.
    if (!$master_branch->isCurrentBranch()) {
      print strtr("Detected master branch !branch, but it is not the current branch. Aborting.", [
        '!branch' => $master_branch->getBranchName(),
      ]);
      exit();
    }

    $feature_branch = $situation->setUpFeatureBranch();

    // Check whether feature branch exists.
    if ($feature_branch->exists()) {
      // TEMPORARY: we don't yet support updates...
      throw new \Exception("The feature branch already exists. Updating an existing branch is not yet supported.");
    }
    else {
      // If feature branch doens't exist, create it in git.
      // Check we are on the master branch -- if not, throw exception.
      if (!$master_branch->isCurrentBranch()) {
        throw new \Exception("The master branch is not current.");
      }

      $feature_branch->gitCreate();
    }
  }

}
