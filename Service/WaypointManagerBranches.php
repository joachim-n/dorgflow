<?php

namespace Dorgflow\Service;

use Dorgflow\Waypoint\MasterBranch;
use Dorgflow\Waypoint\FeatureBranch;
use Dorgflow\Waypoint\Patch;

/**
 * Creates objects that represent branch waypoints in the workflow.
 */
#[\AllowDynamicProperties]
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
        $this->git_executor
      );
    }

    return $this->feature_branch;
  }

  /**
   * Determines whether the feature branch is fully merged with master.
   *
   * @param \Dorgflow\Waypoint\FeatureBranch $feature_branch
   *  The feature branch.
   *
   * @return bool
   *  TRUE if the feature descends from master, that is, the tip of master is
   *  reachable from the feature branch. FALSE otherwise, that is, if the
   *  feature branch needs to be rebased.
   */
  public function featureBranchIsUpToDateWithMaster(FeatureBranch $feature_branch) {
    if (!$feature_branch->exists()) {
      // If the branch doesn't exist, then it's not up to date.
      return FALSE;
    }

    return $this->git_info->isAncestor(
      $this->getMasterBranch()->getBranchName(),
      $this->getFeatureBranch()->getBranchName()
    );
  }

}
