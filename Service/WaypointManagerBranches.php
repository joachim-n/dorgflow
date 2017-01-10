<?php

namespace Dorgflow\Service;

use Dorgflow\Waypoint\MasterBranch;
use Dorgflow\Waypoint\FeatureBranch;
use Dorgflow\Waypoint\Patch;

/**
 * Creates objects that represent branch waypoints in the workflow.
 */
class WaypointManagerBranches {

  function __construct($git_info, $drupal_org, $git_executor, $analyser) {
    $this->git_info = $git_info;
    $this->drupal_org = $drupal_org;
    $this->git_executor = $git_executor;
    $this->analyser = $analyser;
  }

  public function getMasterBranch() {
    if (empty($this->masterBranch)) {
      $this->masterBranch = new MasterBranch(
        $this->git_info,
        $this->git_executor
      );
    }

    return $this->masterBranch;
  }

  public function getFeatureBranch() {
    if (empty($this->feature_branch)) {
      $this->feature_branch = new FeatureBranch(
        $this->git_info,
        $this->analyser,
        $this->drupal_org,
        $this->git_exec
      );
    }

    return $this->feature_branch;
  }

}
