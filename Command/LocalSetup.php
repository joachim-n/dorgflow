<?php

namespace Dorgflow\Command;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class LocalSetup extends CommandBase {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('setup')
      ->setDescription('Sets up a feature branch.')
      ->setHelp('Sets up a feature branch based on a drupal.org issue node, and downloads and applies any patches.');
  }

  /**
   * Creates an instance of this command, injecting services from the container.
   */
  static public function create(ContainerBuilder $container) {
    return new static(
      $container->get('git.info'),
      $container->get('waypoint_manager.branches'),
      $container->get('waypoint_manager.patches')
    );
  }

  function __construct($git_info, $waypoint_manager_branches, $waypoint_manager_patches) {
    $this->git_info = $git_info;
    $this->waypoint_manager_branches = $waypoint_manager_branches;
    $this->waypoint_manager_patches = $waypoint_manager_patches;
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    // Check git is clean.
    $clean = $this->git_info->gitIsClean();
    if (!$clean) {
      throw new \Exception("Git repository is not clean. Aborting.");
    }

    // Create branch objects.
    $feature_branch = $this->waypoint_manager_branches->getFeatureBranch();
    $master_branch = $this->waypoint_manager_branches->getMasterBranch();

    // Check whether feature branch exists (whether reachable or not).
    if ($feature_branch->exists()) {
      // If the feature branch already exists, check it out, and stop.
      $feature_branch->gitCheckout();

      print strtr("The feature branch !branch already exists and has been checked out.\n", [
        '!branch' => $feature_branch->getBranchName(),
      ]);

      if ($this->waypoint_manager_branches->featureBranchIsUpToDateWithMaster($feature_branch)) {
        print "This branch is up to date with master.\n";
        print "You should use the update command to get new patches from drupal.org.\n";
      }
      else {
        print strtr("This branch is not up to date with master. You should do 'git rebase !master --keep-empty'.\n", [
          '!master' => $master_branch->getBranchName(),
        ]);
        print "Afterwards, you should use the update command to get new patches from drupal.org.\n";
      }

      return;
    }

    // If the master branch is not current, abort.
    if (!$master_branch->isCurrentBranch()) {
      throw new \Exception(strtr("Detected master branch !branch, but it is not the current branch. Aborting.\n", [
        '!branch' => $master_branch->getBranchName(),
      ]));
    }

    print strtr("Detected master branch !branch.\n", [
      '!branch' => $master_branch->getBranchName(),
    ]);

    $feature_branch->gitCreate();

    print strtr("Created feature branch !branch.\n", [
      '!branch' => $feature_branch->getBranchName(),
    ]);

    // Get the patches and create them.
    $patches = $this->waypoint_manager_patches->setUpPatches();

    // If no patches, we're done.
    if (empty($patches)) {
      print "There are no patches to apply.\n";
      return;
    }

    // Output the patches.
    foreach ($patches as $patch) {
      $patch_committed = $patch->commitPatch();

      // Message.
      if ($patch_committed) {
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
