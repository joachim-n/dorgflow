<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

class CreatePatch {

  public function execute() {
    $situation = new Situation();

    // Create branches.
    $master_branch = $situation->setUpMasterBranch();
    $feature_branch = $situation->setUpFeatureBranch();

    // If the feature branch doesn't exist or is not current, abort.
    if (!$feature_branch->exists()) {
      throw new \Exception("Feature branch does not exist.");
    }
    if (!$feature_branch->isCurrentBranch()) {
      throw new \Exception("Feature branch is not the current branch.");
    }

    /*
      run the analyer to get stuff
      - check git is clean
      - get the issue number from the branch
      - get the next comment number from the issue
      - make a patch
      - figure out interdiff: what was the previous patch.
        get the patch list. get the commit for the most recent patch.
        diff from that commit

    */

  }

}
