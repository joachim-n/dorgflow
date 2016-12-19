<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

class LocalSetup {

  public function __construct(Situation $situation) {
    $this->situation = $situation;
  }

  public function execute() {
    $situation = $this->situation;

    // Check git is clean.
    $clean = $situation->GitStatus()->gitIsClean();
    if (!$clean) {
      print "Git repository is not clean. Aborting.\n";
      exit();
    }

    // Create branches.
    $master_branch = $situation->getMasterBranch();

    // If the master branch is not current, abort.
    if (!$master_branch->isCurrentBranch()) {
      print strtr("Detected master branch !branch, but it is not the current branch. Aborting.\n", [
        '!branch' => $master_branch->getBranchName(),
      ]);
      exit();
    }

    print strtr("Detected master branch !branch.\n", [
      '!branch' => $master_branch->getBranchName(),
    ]);

    $feature_branch = $situation->getFeatureBranch();

    // Check whether feature branch exists.
    // TODO: necessary???
    if ($feature_branch->exists()) {
      throw new \Exception("The feature branch already exists. Use the update command.");
    }
    else {
      // If feature branch doens't exist, create it in git.
      // Check we are on the master branch -- if not, throw exception.
      if (!$master_branch->isCurrentBranch()) {
        throw new \Exception("The master branch is not current.");
      }

      $feature_branch->gitCreate();

      print strtr("Feature branch !branch created.\n", [
        '!branch' => $feature_branch->getBranchName(),
      ]);
    }

    // Get the patches and create them.
    // TODO: currently only the most recent patch is used.
    $patches = $situation->setUpPatches();
    //dump($patches);

    // If no patches, we're done.
    if (empty($patches)) {
      print "No patches to apply.\n";
      return;
    }

    // Output the patches.
    foreach ($patches as $patch) {
      $patch_committed = $patch->commitPatch();

      // Message.
      if ($patch_committed) {
        print strtr("Applied patch !patchname.\n", [
          '!patchname' => $patch->getPatchFilename(),
        ]);
      }
      else {
        print strtr("Patch !patchname did not apply.\n", [
          '!patchname' => $patch->getPatchFilename(),
        ]);
      }
    }

    // If final patch didn't apply, then output a message: the latest patch
    // has rotted. Save the patch file to disk and give the filename in the
    // message.
    if (!$patch_committed) {
      // Save the file so the user can apply it manually.
      file_put_contents($patch->getPatchFilename(), $patch->getPatchFile());

      print strtr("The most recent patch, !patchname, did not apply. You should attempt to apply it manually. The patch file has been saved to the working directory.\n", [
        '!patchname' => $patch->getPatchFilename(),
      ]);
    }
  }

}
