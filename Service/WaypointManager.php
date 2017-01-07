<?php

namespace Dorgflow\Service;

// needs:
// - git executor
// - situation ERM?
class WaypointManager {

  public function getMasterBranch() {
    if (empty($this->masterBranch)) {
      $this->masterBranch = new \Dorgflow\Waypoint\MasterBranch(
      // !!!!!!!!!!
      );
    }

    return $this->masterBranch;
  }

  public function getFeatureBranch() {

  }

  public function setUpPatches() {

  }

}
