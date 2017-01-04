<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

/**
 * Applies the current feature branch to the master branch as a squash merge.
 */
class Apply extends CommandBase {

  public function execute() {
    $situation = $this->situation;

    // Check git is clean.
    $clean = $situation->GitStatus()->gitIsClean();
    if (!$clean) {
      throw new \Exception("Git repository is not clean. Aborting.");
    }

    // Create branches.
    $master_branch = $situation->getMasterBranch();
    $feature_branch = $situation->getFeatureBranch();

    // If the feature branch is not current, abort.
    if (!$feature_branch->exists()) {
      throw new \Exception("Could not find a feature branch. Aborting.");
    }
    if (!$feature_branch->isCurrentBranch()) {
      throw new \Exception(strtr("Detected feature branch !branch, but it is not the current branch. Aborting.", [
        '!branch' => $feature_branch->getBranchName(),
      ]));
    }

    // @todo check that the feature branch tip is the same as the most recent patch
    // from d.org

    // Check out the master branch.
    $this->git->checkOutBranch($master_branch->getBranchName());
    // Perform a squash merge from the feature branch: in other words, all the
    // changes on the feature branch are now staged on master.
    $this->git->squashMerge($feature_branch->getBranchName());

    print strtr("Changes from feature branch !feature-branch are now applied and staged on branch !master-branch.\n", [
      '!feature-branch' => $feature_branch->getBranchName(),
      '!master-branch'  => $master_branch->getBranchName(),
    ]);
    print strtr("You should now commit this, using the command from the issue on drupal.org: https://www.drupal.org/node/!id#drupalorg-issue-credit-form.\n", [
      '!id' => $situation->getIssueNumber(),
    ]);
  }

}
