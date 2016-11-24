<?php

namespace Dorgflow\Waypoint;

class Patch {

  public static function getNextPatch(\Dorgflow\Situation $situation, $parent) {
    
    
    // Need:
    // git log 
    // file list
    
    $feature_branch_log = $situation->getFeatureBranchLog($feature_branch);
    
    // Get file list from d.org
    ....
    
    
    //create a patch object for the first file and patch-ish commit
  }

}