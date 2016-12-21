<?php

namespace Dorgflow\Executor;

// TODO: consider replacing this with a library.
class Git {

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
   * @param $treeish
   *  A valid commit identifier, such as an SHA or branch name.
   */
  public function checkOutFiles($treeish) {
    // Read the tree for the given commit into the index.
    exec("git read-tree $treeish");

    // Check out the index.
    exec('git checkout-index -f -a');
  }

  // Porcelain version.
  public function checkOutFilesPorcelain($treeish) {
    $current_sha = shell_exec("git rev-parse HEAD");

    // Reset the feature branch to the master branch tip commit. This puts the
    // files in the same state as the master branch.
    shell_exec("git reset --hard $treeish");

    // Move the branch reference back to where it was, but without changing the
    // files.
    shell_exec("git reset --soft $current_sha");
  }

  // apply patch file

  // make commit

}
