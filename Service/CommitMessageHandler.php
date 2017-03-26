<?php

namespace Dorgflow\Service;

use Dorgflow\Waypoint\Patch;
use Dorgflow\Waypoint\LocalPatch;

/**
 * Handles commit messages for our automatic git commits.
 *
 * This both creates messages and parses them. This class exists simply to keep
 * both sides of this process in the same place.
 */
class CommitMessageHandler {

  function __construct($analyser) {
    $this->analyser = $analyser;
  }

  /**
   * Creates a commit message to use for a patch from a d.org file.
   *
   * @param \Dorgflow\Waypoint\Patch $patch
   *  The patch object.
   *
   * @return string
   *  The commit message.
   */
  public function createCommitMessage(Patch $patch) {
    // TODO: throw or bail if the patch object is already committed.
    $filename = $patch->getPatchFilename();
    $fid = $patch->getPatchFileFid();
    $index = $patch->getPatchFileIndex();

    // Construct the anchor URL for the comment on the issue node where the
    // patch was added.
    $url = strtr('https://www.drupal.org/node/:nid#comment-:cid', [
      ':nid' => $this->analyser->deduceIssueNumber(),
      ':cid' => $patch->getPatchFileCid(),
    ]);

    return "Patch from Drupal.org. Comment: $index; URL: $url; file: $filename; fid: $fid. Automatic commit by dorgflow.";
  }

  /**
   * Creates a commit message to use for a local patch.
   *
   * @param \Dorgflow\Waypoint\LocalPatch $local_patch
   *  The patch object.
   *
   * @return string
   *  The commit message.
   */
  public function createLocalCommitMessage(LocalPatch $local_patch) {
    $patch_name = $local_patch->getPatchFilename();
    return "Patch for Drupal.org. File: $patch_name. Automatic commit by dorgflow.";
  }

  /**
   * Extract data from a commit message previously created by Dorgflow.
   *
   * @param $message
   *  The message string.
   *
   * @return
   *  Either FALSE if no data can be found in the message, or an array of data.
   *  The following keys may be present:
   *    - 'filename': The filename of the commit's patch.
   *    - 'fid': The file entity ID. This will be absent in the case of a commit
   *      made by the CreatePatch command, that is, for a patch the user is
   *      creating to be uploaded to drupal.org.
   *    - 'comment_index': The comment index.
   *    - 'local': Is set and TRUE if the commit is for a local patch, i.e. one
   *      that the user generated to upload themselves to Drupal.org.
   */
  public function parseCommitMessage($message) {
    // Bail if not a dorgflow commit.
    if (!preg_match('@Automatic commit by dorgflow.$@', $message)) {
      return FALSE;
    }

    $return = [];

    $matches = [];
    // Allow for pre-1.0.0 format, where the file is the first item in the list
    // and has a capital letter.
    if (preg_match('@[Ff]ile: (?P<filename>.+\.patch)@', $message, $matches)) {
      $return['filename'] = $matches['filename'];
    }

    $matches = [];
    // Allow for pre-1.1.0 format, where the ':' after 'fid' is missing.
    if (preg_match('@fid:? (?P<fid>\d+)@', $message, $matches)) {
      $return['fid'] = $matches['fid'];
    }

    $matches = [];
    if (preg_match('@Comment:? (?P<comment_index>\d+)@', $message, $matches)) {
      $return['comment_index'] = $matches['comment_index'];
    }

    if (preg_match('@Patch for Drupal.org@', $message)) {
      $return['local'] = TRUE;
    }

    if (empty($return)) {
      // We shouldn't come here, but just in case, return the right thing.
      return FALSE;
    }

    return $return;
  }

}
