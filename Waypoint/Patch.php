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
    $this->situation->masterBranch->checkOutFiles();

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
    // See https://www.sitepoint.com/proc-open-communicate-with-the-outside-world/
    $desc = [
      0 => array('pipe', 'r'), // 0 is STDIN for process
      1 => array('pipe', 'w'), // 1 is STDOUT for process
      2 => array('pipe', 'w'), // 2 is STDERR for process
    ];

    // The command.
    $cmd = "git apply --index -";

    // Spawn the process.
    $pipes = [];
    $process = proc_open($cmd, $desc, $pipes);

    // Send the patch to command as input, the close the input pipe so the
    // command knows to start processing.
    $patch_file = $this->getPatchFile();
    dump($patch_file);
    fwrite($pipes[0], $patch_file);
    fclose($pipes[0]);


    $out = stream_get_contents($pipes[1]);
    dump($out);

    $errors = stream_get_contents($pipes[2]);
    dump($errors);

    // all done! Clean up
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    // Check STDERR for errors.
    if (!empty($errors)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  protected function getCommitMessage() {
    // TODO: include comment index!!!!!!!!!
    $filename = $this->getPatchFilename();
    return "Patch from Drupal.org. File: $filename; fid $this->fid. Automatic commit by dorgflow.";
  }

  static public function parseCommitMessage($message) {
    $pattern = "Patch from Drupal.org. File: (?P<filename>.+\.patch); fid (?P<fid>\d+). Automatic commit by dorgflow.";
    $matches = [];
    preg_match("@^$pattern@", $message, $matches);
    if (!empty($matches)) {
      $return = [
        'filename' => $matches['filename'],
        'fid' => $matches['fid'],
        // TODO: 'comment_index'
      ];
    }
    else {
      return FALSE;
    }
  }

  protected function makeGitCommit() {
    $message = $this->getCommitMessage();
    // Allow empty commits, in case two sequential patches are identical.
    shell_exec("git commit  --allow-empty --message='$message'");
  }

}
