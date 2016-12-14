<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

class LocalUpdate {

  public function execute() {
    $situation = new Situation();

    // Check git is clean.
    $clean = $situation->GitStatus()->gitIsClean();
    if (!$clean) {
      print "Git repository is not clean. Aborting.\n";
      exit();
    }

    // Create branches.
    $master_branch = $situation->getMasterBranch();
    $feature_branch = $situation->getFeatureBranch();

    // If the feature branch is not current, abort.
    if (!$feature_branch->exists()) {
      print "Could not find a feature branch. Aborting.";
      exit();
    }
    if (!$feature_branch->isCurrentBranch()) {
      print strtr("Detected feature branch !branch, but it is not the current branch. Aborting.", [
        '!branch' => $feature_branch->getBranchName(),
      ]);
      exit();
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
      // !!!!! TODO! don't commit patches that have commits already!!!!!!!!!!

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
