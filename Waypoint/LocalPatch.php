<?php

namespace Dorgflow\Waypoint;

/**
 * Represents a patch the user is creating locally.
 *
 * Note that at the time of its construction, this does not yet exist in git.
 */
class LocalPatch {

  /**
   * The filename for this patch.
   */
  protected $patchFile;

  /**
   * Constructor.
   */
  function __construct($drupal_org, $waypoint_manager_branches, $git_executor, $commit_message, $analyser) {
    $this->drupal_org = $drupal_org;
    $this->waypoint_manager_branches = $waypoint_manager_branches;
    $this->git_executor = $git_executor;
    $this->commit_message = $commit_message;
    $this->analyser = $analyser;
  }

  /**
   * Gets the filename for this waypoint's patch file.
   *
   * @return string
   *  The patch filename.
   */
  public function getPatchFilename() {
    if (!isset($this->patchFile)) {
      $this->patchFile = $this->makePatchFilename();
    }

    return $this->patchFile;
  }

  /**
   * Gets the (expected) comment index for the patch file.
   *
   * This is the number of the comment that will to the node when the file
   * was added. Comment numbers start at 1 and increment for each comment.
   * (Obviously, if another user comments on the issue in between this patch
   * being created and the Dorgflow user uploading it, the number will be
   * wrong.)
   *
   * @return int
   *  The comment index.
   */
  public function getPatchFileIndex() {
    if (!isset($this->index)) {
      $this->index = $this->drupal_org->getNextCommentIndex();
    }
    return $this->index;
  }

  /**
   * Creates a filename for this waypoint's patch file.
   *
   * @return string
   *  The patch filename.
   */
  protected function makePatchFilename() {
    $issue_number = $this->analyser->deduceIssueNumber();
    $comment_number = $this->getPatchFileIndex();
    $patch_number = "$issue_number-$comment_number";

    $current_project = $this->analyser->getCurrentProjectName();

    $branch_description = $this->waypoint_manager_branches->getFeatureBranch()->getBranchDescription();

    return "$patch_number.$current_project.$branch_description.patch";
  }

  public function commitPatch() {
    $this->makeGitCommit();
  }

  public function makeCommitMessage() {
    // TODO
  }

  protected function makeGitCommit() {
    $message = $this->makeCommitMessage();
    $this->git_executor->commit($message);
  }

}
