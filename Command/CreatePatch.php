<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

class CreatePatch {

  public function execute() {
    $this->situation = new Situation();

    // TODO: check git is clean!

    // Create branches.
    $master_branch = $this->situation->setUpMasterBranch();
    $feature_branch = $this->situation->setUpFeatureBranch();

    // If the feature branch doesn't exist or is not current, abort.
    if (!$feature_branch->exists()) {
      throw new \Exception("Feature branch does not exist.");
    }
    if (!$feature_branch->isCurrentBranch()) {
      throw new \Exception("Feature branch is not the current branch.");
    }

    // TODO: get this from user input.
    $sequential = FALSE;

    // Select the diff command to use.
    if ($sequential) {
      $command = 'format-patch --stdout';
    }
    else {
      $command = 'diff';
    }

    $master_branch_name = $master_branch->getBranchName();
    $patch_name = $this->getPatchName($feature_branch);

    shell_exec("git $command $master_branch_name > $patch_name");

    drush_print_r("Written patch $patch_name with diff from $master_branch to local branch.");


    /*
    TODO:
      - figure out interdiff: what was the previous patch.
        get the patch list. get the commit for the most recent patch.
        diff from that commit

    */

  }

  protected function getPatchName($feature_branch) {
    $issue_number = $this->situation->getIssueNumber();
    $comment_number = $this->situation->DrupalOrgIssueNode()->getNextCommentIndex();
    $patch_number = "$issue_number-$comment_number";
    $current_project = $this->situation->CurrentProject()->getCurrentProjectName();
    $branch_description = $feature_branch->getBranchDescription();

    return "$patch_number.$current_project.$branch_description.patch";
  }

}
