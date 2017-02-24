<?php

namespace Dorgflow\Service;

// TODO: consider replacing this with a library.
class GitExecutor {

  // we need this for:
    // a: createf feature branch (HEAD, CHECKOUT!)
    // b: create side-branch for update with local commits: HEAD, NO CHECKOUT.
  public function createNewBranch($branch_short_name, $checkout = FALSE) {
    // TODO: check $branch_short_name does not exist yet!

    // Create a new branch at the given commit.
    exec("git update-ref refs/heads/{$branch_short_name} HEAD");

    // Switch to the new branch if requested.
    if ($checkout) {
      exec("git symbolic-ref HEAD refs/heads/{$branch_short_name}");
    }
  }

  /**
   * Checks out the given branch.
   *
   * @param $branch_name
   *  The short name of a branch, e.g. 'master'.
   */
  public function checkOutBranch($branch_name) {
    // @todo change this to use git plumbing command.
    exec("git checkout $branch_name");
  }

  /**
   * Resets the tip of a given branch.
   *
   * @param $branch_name
   *  The short name of a branch, e.g. 'master'.
   * @param $sha
   *  An SHA to set the branch tip to.
   */
  public function moveBranch($branch_name, $sha) {
    shell_exec("git update-ref refs/heads/$branch_name $sha");
  }

  /**
   * Checks out the files of the given commit, without moving branches.
   *
   * This causes both the working directory and the staging to look like the
   * files in the commit given by $treeish, without changing the actual commit
   * or branch that git is currently on.
   *
   * The end result is that git has changes staged which take the current branch
   * back to $treeish. The point of this is a patch which is against $treeish
   * can now be applied.
   *
   * See http://stackoverflow.com/questions/13896246/reset-git-to-commit-without-changing-head-to-detached-state
   *
   * (The porcelain equivalent of this is:
   *   $ git reset --hard $treeish;
   *   $ git reset --soft $current_sha
   * )
   *
   * @param $treeish
   *  A valid commit identifier, such as an SHA or branch name.
   */
  public function checkOutFiles($treeish) {
    // Read the tree for the given commit into the index.
    // Use the -m option as this might be what causes false negatives for the
    // git clean check on subsequent commands.
    shell_exec("git read-tree -m $treeish");

    // Check out the index.
    shell_exec('git checkout-index -f -a -u');

    // ARGH, we have to call this for weird git reasons that are weird, all the
    // more so that doing these commands manually doesn't require this, but when
    // run in this script, causes an error of 'does not match index' when trying
    // to apply patches.
    // See http://git.661346.n2.nabble.com/quot-git-apply-check-quot-successes-but-git-am-says-quot-does-not-match-index-quot-td6684646.html
    // for possible clues.
    shell_exec('git update-index -q --refresh');
  }

  // Porcelain version.
  // TODO: remove in due course.
  public function checkOutFilesPorcelain($treeish) {
    $current_sha = shell_exec("git rev-parse HEAD");

    // Reset the feature branch to the master branch tip commit. This puts the
    // files in the same state as the master branch.
    shell_exec("git reset --hard $treeish");

    // Move the branch reference back to where it was, but without changing the
    // files.
    shell_exec("git reset --soft $current_sha");
  }

  /**
   * Performs a squash merge of a given branch.
   */
  public function squashMerge($branch_name) {
    // @todo change this to use git plumbing command.
    exec("git merge --squash $branch_name");
  }

  /**
   * Apply a patch to the staging area.
   *
   * @param $patch_text
   *  The text of the patch file.
   *
   * @return
   *  TRUE if the patch applied, FALSE if it did not.
   */
  public function applyPatch($patch_text) {
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
    fwrite($pipes[0], $patch_text);
    fclose($pipes[0]);


    $out = stream_get_contents($pipes[1]);
    //dump('OUT:');
    //dump($out);

    $errors = stream_get_contents($pipes[2]);
    //dump('ERROR:');
    //dump($errors);

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

  /**
   * Commit the currently staged changes.
   *
   * @param $message
   *  The message for the commit.
   */
  public function commit($message) {
    // Allow empty commits, for local patches and also in case two sequential
    // patches are identical.
    shell_exec("git commit  --allow-empty --message='$message'");
  }

  /**
   * Writes a patch file based on a git diff.
   *
   * @param $treeish
   *  The commit to take the diff from.
   * @param $patch_name
   *  The filename to write for the patch.
   * @param $sequential
   *  (optional) If TRUE, the patch is sequential: composed of multiple
   *  changesets, one for each commit from $treeish to HEAD. Defaults to FALSE.
   */
  public function createPatch($treeish, $patch_name, $sequential = FALSE) {
    // Select the diff command to use.
    if ($sequential) {
      $command = 'format-patch --stdout';
    }
    else {
      $command = 'diff';
    }

    shell_exec("git $command $treeish > $patch_name");
  }

}
