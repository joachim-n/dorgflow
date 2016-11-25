<?php

namespace Dorgflow\Waypoint;

class Patch {

  protected static $generator;

  /**
   * Indicates that the patch is not a viable object and should be destroyed.
   */
  public $cancel;

  protected $fid;

  protected $patchFile;

  function __construct(\Dorgflow\Situation $situation) {
    $this->situation = $situation;

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

    // Set our properties.
    $this->fid = $file->file->id;

    // Try to find a commit.
    // TODO: can't do this until we have index numbers.
    // (well, we could use fids, but then we'd have a backwards compatibility
    // issue in the future...)
  }

  public function getPatchFile() {
    // Lazy fetch the patch file.
    if (empty($this->patchFile)) {
      $this->patchFile = $this->situation->DrupalOrgPatchFile->getPatchFile($this->fid);
    }
    return $this->patchFile;
  }

}
