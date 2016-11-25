<?php

namespace Dorgflow\Waypoint;

class Patch {

  protected static $generator;

  public $cancel;

  function __construct(\Dorgflow\Situation $situation) {
    // The generator must be stored, otherwise getting it again takes us back
    // to the start of the iterator.
    if (!isset(static::$generator)) {
      static::$generator = $situation->DrupalOrgIssueNode->getNextIssueFile();
    }


    $file = static::$generator->current();

    if (empty($file)) {
      // Cancel this.
      $this->cancel = TRUE;
    }

    // Advance the generator for the next use.
    static::$generator->next();
  }

  public static function getNextPatch(\Dorgflow\Situation $situation) {
    // Get from situation:
    // - next patch
    // - feature branch log

    // Need:
    // git log
    // file list

    $feature_branch_log = $situation->getFeatureBranchLog($feature_branch);

    // Get file list from d.org
    //....


    //create a patch object for the first file and patch-ish commit
  }

}