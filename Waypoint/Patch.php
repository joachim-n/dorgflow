<?php

namespace Dorgflow\Waypoint;

/**
 * Represents a patch on an issue.
 *
 * This will typically be attached to a comment, but patches can also be
 * uploaded to the node itself when it is first created.
 */
#[\AllowDynamicProperties]
class Patch {

  /*

    - getSHA
    - getFileEntity
    - getPatchFile
    - getPatchFilename
    - getPatchFileFid
    - getPatchFileIndex
    - getPatchFileCid
    - hasCommit
    - commitPatch
    - applyPatchFile


  different kinds of patch:

    - file from dorg only. --> becomes git commit -- PatchFile
      - getFileEntity
      - getPatchFile
      - getPatchFilename


    - file from dorg AND git commit --> doesn't do much -- PatchFileCommitted

    - git commit only --> becomes dorg file -- LocalPatch

    - local file for dorg AND git commit --> doesn't do much -- LocalPatchFile
  */

  /**
   * The file entity ID for this patch.
   */
  protected $fid;

  /**
   * The comment ID for this patch.
   */
  protected $cid;

  /**
   * The comment index for this patch.
   *
   * This is the number of the comment that was added to the node when the file
   * was added. Comment numbers start at 1 and increment for each comment.
   */
  protected $index;

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
   *  The file field item from the issue node for the patch file, if there is
   *  one.
   * @param $sha = NULL
   *  The SHA for the patch's commit, if there is a commit.
   * @param $commit_message_data = NULL
   *  The parsed commit message data, if there is a commit.
   */
  function __construct($drupal_org, $waypoint_manager_branches, $git_executor, $commit_message, $analyser, $file_field_item = NULL, $sha = NULL, $commit_message_data = NULL) {
    $this->drupal_org = $drupal_org;
    $this->waypoint_manager_branches = $waypoint_manager_branches;
    $this->git_executor = $git_executor;
    $this->commit_message = $commit_message;

    // Set the file ID and index.
    if (isset($file_field_item)) {
      $this->fid = $file_field_item->file->id;
      $this->index = $file_field_item->index;
      $this->cid = $file_field_item->file->cid ?? NULL;
    }
    elseif (isset($commit_message_data)) {
      // If there is no file item, then try the commit message data.
      $this->fid = $commit_message_data['fid'] ?? NULL;
      $this->index = $commit_message_data['comment_index'] ?? NULL;
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

  public function getPatchFileFid() {
    return $this->fid;
  }

  /**
   * Gets the comment index for the patch file.
   *
   * This is the number of the comment that was added to the node when the file
   * was added. Comment numbers start at 1 and increment for each comment.
   *
   * @return int
   *  The comment index.
   */
  public function getPatchFileIndex() {
    return $this->index;
  }

  /**
   * Gets the comment ID for the patch file.
   *
   * @return int|null
   *   The comment ID, or NULL if the file is on the node itself.
   */
  public function getPatchFileCid(): ?int {
    return $this->cid;
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

  /**
   * Create a commit for this patch on the current feature branch.
   *
   * @return bool
   *  TRUE if git was able to apply the patch, FALSE if it was not.
   */
  public function commitPatch() {
    // Set the files back to the master branch, without changing the current
    // commit.
    $this->waypoint_manager_branches->getMasterBranch()->checkOutFiles();

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

  /**
   * Creates a commit message to use for the patch.
   *
   * @return string
   *  The commit message.
   */
  protected function getCommitMessage() {
    return $this->commit_message->createCommitMessage($this);
  }

  protected function makeGitCommit() {
    $message = $this->getCommitMessage();
    $this->git_executor->commit($message);
  }

}
