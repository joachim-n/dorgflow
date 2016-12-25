<?php

namespace Dorgflow\Waypoint;

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

  function __construct(\Dorgflow\Situation $situation, $file_field_item, $sha = NULL) {
    $this->situation = $situation;

    // Set the file ID.
    $this->fid = $file_field_item->file->id;

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
      $this->fileEntity = $this->situation->DrupalOrgFileEntity(['fid' => $this->fid])->getFileEntity();
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

      $this->patchFile = $this->situation->DrupalOrgPatchFile(['url' => $file_entity->url])->getPatchFile($this->fid);
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
    $this->situation->getMasterBranch()->checkOutFiles();

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
    return $this->situation->git()->applyPatch($patch_file);
  }

  protected function getCommitMessage() {
    // TODO: include comment index!!!!!!!!!
    $filename = $this->getPatchFilename();
    return "Patch from Drupal.org. File: $filename; fid $this->fid. Automatic commit by dorgflow.";
  }

  protected function makeGitCommit() {
    $message = $this->getCommitMessage();
    $this->situation->git()->commit($message);
  }

}
