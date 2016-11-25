<?php

namespace Dorgflow\Waypoint;

class Patch {

  protected static $generator;

  /**
   * Indicates that the patch is not a viable object and should be destroyed.
   */
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
      // (Would throwing an exception be cleaner?)
      $this->cancel = TRUE;
    }

    // Advance the generator for the next use.
    static::$generator->next();
  }

}