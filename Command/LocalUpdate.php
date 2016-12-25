<?php

namespace Dorgflow\Command;

use Dorgflow\Situation;

class LocalUpdate {

  public function __construct(Situation $situation) {
    $this->situation = $situation;
  }

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
    $patches = $situation->setUpPatches();
    //dump($patches);

    // If no patches, we're done.
    if (empty($patches)) {
      print "No patches to apply.\n";
      return;
    }

    // Output the patches.
    $patches_committed = [];
    foreach ($patches as $patch) {
      // Skip a patch with an existing commit.
      if ($patch->hasCommit()) {
        continue;
      }


      // Commit the patch.
      $patch_committed = $patch->commitPatch();

      // Message.
      if ($patch_committed) {
        // Keep a list of the patches that we commit.
        $patches_committed[] = $patch;

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

    // If all the patches were already committed, we're done.
    if (empty($patches_committed)) {
      print "No new patches to apply.\n";
      return;
    }

    // If final patch didn't apply, then output a message: the latest patch
    // has rotted. Save the patch file to disk and give the filename in the
    // message.
    if (!$patch_committed) {
      // Save the file so the user can apply it manually.
      file_put_contents($patch->getPatchFilename(), $patch->getPatchFile());

      print strtr("The most recent patch, !patchname, did not apply. You should attempt to apply it manually. "
        . "The patch file has been saved to the working directory.\n", [
        '!patchname' => $patch->getPatchFilename(),
      ]);
    }
  }

}
