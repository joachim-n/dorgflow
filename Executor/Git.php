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

  // checkoutFfiles =- see MasterBranch

  // apply patch file

  // make commit

}
