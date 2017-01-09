<?php

namespace Dorgflow\Waypoint;

use \Dorgflow\Executor\Git;

class Patch {

  /**
   * The file entity ID for this patch.
   */
  protected $fid;

  /**
   * The filename for this patch.
   */
  protected $patchFile;

  /**
   * The SHA for this patch, if it is already committed to the feature branch.
   */
  protected $sha;

  /**
   * Constructor.
   *
   * @param $file_field_item = NULL
   *  The file field item from the issue node for the patch file, if there is one.
   * @param $sha = NULL
   *  The SHA for the patch's commit, if there is a commit.
   */
  function __construct($drupal_org, $waypoint_manager, $git_executor, $file_field_item = NULL, $sha = NULL) {
    $this->drupal_org = $drupal_org;
    $this->waypoint_manager = $waypoint_manager;
    $this->git_executor = $git_executor;

    // Set the file ID.
    if (isset($file_field_item)) {
      $this->fid = $file_field_item->file->id;
    }

    $this->sha = $sha;
  }

  /**
   * Returns the SHA for the commit for this patch, or NULL if not committed.
   *
   * @return string|null
   *  The SHA, or NULL if this patch has no corresponding commit.
   */
  public function getSHA() {
    return $this->sha;
  }

  /**
   * Returns the Drupal file entity for this patch.
   *
   * @return \StdClass
   *  The Drupal file entity, downloaded from drupal.org.
   */
  public function getFileEntity() {
    // Lazy fetch the file entity for the patch.
    if (empty($this->fileEntity)) {
      $this->fileEntity = $this->drupal_org->getFileEntity($this->fid);
    }
    return $this->fileEntity;
  }

  /**
   * Returns the patch file for this patch.
   *
   * @return string
   *  The text of the patch file.
   */
  public function getPatchFile() {
    // Lazy fetch the patch file.
    if (empty($this->patchFile)) {
      $file_entity = $this->getFileEntity();

      $this->patchFile = $this->drupal_org->getPatchFile($file_entity->url);
    }
    return $this->patchFile;
  }

  public function getPatchFilename() {
    $file_entity = $this->getFileEntity();
    $file_url = $file_entity->url;
    return pathinfo($file_url, PATHINFO_BASENAME);
  }

  /**
   * Returns whether this patch already has a feature branch commit.
   *
   * @return bool
   *  TRUE if this patch has a commit; FALSE if not.
   */
  public function hasCommit() {
    return !empty($this->sha);
  }

  public function commitPatch() {
    // Set the files back to the master branch, without changing the current
    // commit.
    $this->waypoint_manager->getMasterBranch()->checkOutFiles();

    $patch_status = $this->applyPatchFile();

    if ($patch_status) {
      $this->makeGitCommit();
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Apply the file for this patch.
   *
   * This assumes that the filesystem is in a state that is ready to accept the
   * patch -- that is, the master branch files are checked out.
   *
   * @return
   *  TRUE if the patch applied, FALSE if it did not.
   */
  public function applyPatchFile() {
    $patch_file = $this->getPatchFile();
    return $this->git_executor->applyPatch($patch_file);
  }

  protected function getCommitMessage() {
    // TODO: include comment index!!!!!!!!!
    $filename = $this->getPatchFilename();
    return "Patch from Drupal.org. File: $filename; fid $this->fid. Automatic commit by dorgflow.";
  }

  protected function makeGitCommit() {
    $message = $this->getCommitMessage();
    $this->git_executor->commit($message);
  }

}
