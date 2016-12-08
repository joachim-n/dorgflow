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

    $file_field_item = static::$generator->current();
    dump($file_field_item);
    if (empty($file_field_item)) {
      // Cancel this.
      // (Would throwing an exception be cleaner?)
      $this->cancel = TRUE;
      $this->status = 'end';
      return;
    }

    // Advance the generator for the next use.
    static::$generator->next();

    // Skip a patch file that is set to not be displayed.
    if (!$file_field_item->display) {
      $this->cancel = TRUE;
      $this->status = 'skip';
      return;
    }

    // Set the file ID.
    $this->fid = $file_field_item->file->id;

    // Skip if it's not a patch file.
    // Unfortunately, we have to retrieve the file entity from d.org API to
    // know the file's URL and its extension.
    $file_entity = $this->getFileEntity();
    $file_url = $file_entity->url;
    if (pathinfo($file_url, PATHINFO_EXTENSION) != 'patch') {
      $this->cancel = TRUE;
      $this->status = 'skip';
      return;
    }

    // Try to find a commit.
    // TODO: can't do this until we have index numbers.
    // (well, we could use fids, but then we'd have a backwards compatibility
    // issue in the future...)
    // ARGH but we need this for interdiffs!
  }

  public function getFileEntity() {
    // Lazy fetch the file entity for the patch.
    if (empty($this->fileEntity)) {
      $this->fileEntity = $this->situation->DrupalOrgFileEntity(['fid' => $this->fid])->getFileEntity();
    }
    return $this->fileEntity;
  }

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
    $cmd = "git apply --index --verbose -";

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
    return "patch #??TODO; fid $this->fid. Automatic commit by dorgflow.";
  }

  protected function makeGitCommit() {
    $message = $this->getCommitMessage();
    shell_exec("git commit --message='$message'");
  }

}
