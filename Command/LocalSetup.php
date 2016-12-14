<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

class LocalSetup {

  public function execute() {
    $situation = new Situation();

    // Check git is clean.
    $clean = $situation->GitStatus()->gitIsClean();
    if (!$clean) {
      print "Git repository is not clean. Aborting.\n";
      exit();
    }

    // Create branches.
    $master_branch = $situation->setUpMasterBranch();

    // If the master branch is not current, abort.
    // TODO: support updating an existing feature branch.
    if (!$master_branch->isCurrentBranch()) {
      print strtr("Detected master branch !branch, but it is not the current branch. Aborting.", [
        '!branch' => $master_branch->getBranchName(),
      ]);
      exit();
    }

    $feature_branch = $situation->setUpFeatureBranch();

    // Get the patches and create them.
    // TODO: currently only the most recent patch is used.
    $patches = $situation->setUpPatches();
    //dump($patches);

    // Check whether feature branch exists.
    if ($feature_branch->exists()) {
      // TEMPORARY: we don't yet support updates...
      throw new \Exception("The feature branch already exists. Updating an existing branch is not yet supported.");
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
