<?php

namespace Dorgflow\Command;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class LocalUpdate extends CommandBase {

  /**
   * Creates an instance of this command, injecting services from the container.
   */
  static public function create(ContainerBuilder $container) {
    return new static(
      $container->get('git.info'),
      $container->get('waypoint_manager.branches'),
      $container->get('waypoint_manager.patches'),
      $container->get('git.executor')
    );
  }

  function __construct($git_info, $waypoint_manager_branches, $waypoint_manager_patches, $git_executor) {
    $this->git_info = $git_info;
    $this->waypoint_manager_branches = $waypoint_manager_branches;
    $this->waypoint_manager_patches = $waypoint_manager_patches;
    $this->git_executor = $git_executor;
  }

  public function execute() {
    // Check git is clean.
    $clean = $this->git_info->gitIsClean();
    if (!$clean) {
      throw new \Exception("Git repository is not clean. Aborting.");
    }

    // Create branches.
    $feature_branch = $this->waypoint_manager_branches->getFeatureBranch();

    // If the feature branch is not current, abort.
    if (!$feature_branch->exists()) {
      throw new \Exception("Could not find a feature branch. Aborting.");
    }
    if (!$feature_branch->isCurrentBranch()) {
      throw new \Exception(strtr("Detected feature branch !branch, but it is not the current branch. Aborting.", [
        '!branch' => $feature_branch->getBranchName(),
      ]));
    }

    // Get the patches and create them.
    $patches = $this->waypoint_manager_patches->setUpPatches();
    //dump($patches);

    // If no patches, we're done.
    if (empty($patches)) {
      print "No patches to apply.\n";
      return;
    }

    $patches_uncommitted = [];
    $last_committed_patch = NULL;

    // Find the first new, uncommitted patch.
    foreach ($patches as $patch) {
      if ($patch->hasCommit()) {
        // Any patches prior to a committed patch don't count as uncomitted:
        // they have presumably been examined before and a commit attempted and
        // failed. Hence, if we've found a committed patch, zap the array of
        // uncomitted patches, as what's come before should be ignored.
        $patches_uncommitted = [];

        // Keep updating this, so the last time it's set gives us the last
        // committed patch.
        $last_committed_patch = $patch;
      }
      else {
        $patches_uncommitted[] = $patch;
      }
    }

    // If no uncommitted patches, we're done.
    if (empty($patches_uncommitted)) {
      print "No patches to apply; existing patches are already applied to this feature branch.\n";
      return;
    }

    // If the feature branch's SHA is not the same as the last committed patch
    // SHA, then that means there are local commits on the branch that are
    // newer than the patch.
    // @todo: bug: if the tip if MY patch (ie empty dorgflow commit), then this
    // is triggering incorrectly!!
    if (isset($last_committed_patch) && $last_committed_patch->getSHA() != $feature_branch->getSHA()) {
      // Create a new branch at the tip of the feature branch.
      $forked_branch_name = $feature_branch->createForkBranchName();
      $this->git_executor->createNewBranch($forked_branch_name);

      // Reposition the FeatureBranch tip to the last committed patch.
      $this->git_executor->moveBranch($feature_branch->getBranchName(), $last_committed_patch->getSHA());

      print strtr("Moved your work at the tip of the feature branch to new branch !forkedbranchname. You should manually merge this into the feature branch to preserve your work.\n", [
        '!forkedbranchname' => $forked_branch_name,
      ]);

      // We're now ready to apply the patches.
    }

    // Output the patches.
    $patches_committed = [];
    foreach ($patches_uncommitted as $patch) {
      // Commit the patch.
      $patch_committed = $patch->commitPatch();

      // Message.
      if ($patch_committed) {
        // Keep a list of the patches that we commit.
        $patches_committed[] = $patch;

        print strtr("Applied and committed patch !patchname.\n", [
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
