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
    $master_branch = new \Dorgflow\Waypoint\MasterBranch($situation);
    $feature_branch = new \Dorgflow\Waypoint\FeatureBranch($situation);

    // Check whether feature branch exists.
    // If not, create it in git.
    if (!$feature_branch->exists()) {
      // Check we are on the master branch -- if not, throw exception.
      if (!$master_branch->isCurrentBranch()) {
        throw new \Exception("The master branch is not current.");
      }

      $feature_branch->gitCreate();
    }
  }

}
