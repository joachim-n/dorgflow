<?php

namespace Dorgflow\Service;

use Dorgflow\Waypoint\MasterBranch;
use Dorgflow\Waypoint\Patch;

/**
 * Creates objects that represent waypoints in the workflow.
 */
class WaypointManager {

  function __construct($git_info, $git_log, $drupal_org, $git_executor) {
    $this->git_info = $git_info;
    $this->git_log = $git_log;
    $this->drupal_org = $drupal_org;
    $this->git_executor = $git_executor;
  }

  public function getMasterBranch() {
    if (empty($this->masterBranch)) {
      $this->masterBranch = new MasterBranch(
        $this->git_info,
        $this->git_log,
        $this->git_executor
      );
    }

    return $this->masterBranch;
  }

  public function getFeatureBranch() {

  }

  public function setUpPatches() {

  }

  /**
   * Creates a patch object.
   *
   * This takes care of injecting the services.
   *
   * @param $file_field_item = NULL
   *  The file field item from the issue node for the patch file, if there is one.
   * @param $sha = NULL
   *  The SHA for the patch's commit, if there is a commit.
   *
   * @return
   *  The new patch object.
   */
  public function getPatch($file_field_item = NULL, $sha = NULL) {
    $patch = new Patch(
      $drupal_org,
      $this,
      $this->git_executor,
      $file_field_item,
      $sha
    );
  }

}
