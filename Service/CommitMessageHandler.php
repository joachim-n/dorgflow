<?php

namespace Dorgflow\Service;

use Dorgflow\Waypoint\Patch;

/**
 * Parses commit messages for our automatic git commits.
 */
class CommitMessageHandler {

  /**
   * Creates a commit message to use for a patch..
   *
   * @return string
   *  The commit message.
   */
  public function createCommitMessage(Patch $patch) {
    // TODO: throw or bail if the patch object is already committed.
    $filename = $patch->getPatchFilename();
    $fid = $patch->getPatchFileFid();
    $index = $patch->getPatchFileIndex();
    return "Patch from Drupal.org. Comment: $index; file: $filename; fid $fid. Automatic commit by dorgflow.";
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
   */
  public function parseCommitMessage($message) {
    $pattern_remote = "Patch from Drupal.org. Comment: (?P<index>\d+); file: (?P<filename>.+\.patch); fid (?P<fid>\d+). Automatic commit by dorgflow.";
    $patern_local   = "Patch for Drupal.org. File: (?P<filename>.+\.patch). Automatic commit by dorgflow.";

    $matches = [];
    $matched = preg_match("@^$pattern_remote@", $message, $matches);
    if (!$matched) {
      $matched = preg_match("@^$patern_local@", $message, $matches);
    }

    if (!empty($matches)) {
      $return = [
        'filename' => $matches['filename'],
      ];
      if (isset($matches['fid'])) {
        $return['fid'] = $matches['fid'];
      }
      // TODO: 'comment_index'
    }
    else {
      $return = FALSE;
    }

    return $return;
  }

}
