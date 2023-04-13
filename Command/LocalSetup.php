<?php

namespace Dorgflow\Command;

use Dorgflow\Console\ItemList;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LocalSetup extends SymfonyCommand implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('setup')
      ->setDescription('Sets up a feature branch.')
      ->setHelp('Sets up a feature branch based on a drupal.org issue node, and downloads and applies any patches.');
  }

  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
    $this->waypoint_manager_patches = $this->container->get('waypoint_manager.patches');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setServices();

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

      $output->writeln(strtr("<info>The feature branch !branch already exists and has been checked out.</info>", [
        '!branch' => $feature_branch->getBranchName(),
      ]));

      if ($this->waypoint_manager_branches->featureBranchIsUpToDateWithMaster($feature_branch)) {
        $output->writeln("This branch is up to date with master.");
        $output->writeln("You should use the update command to get new patches from drupal.org.");
      }
      else {
        $output->writeln(strtr("This branch is not up to date with master. You should do 'git rebase !master --keep-empty'.", [
          '!master' => $master_branch->getBranchName(),
        ]));
        $output->writeln("Afterwards, you should use the update command to get new patches from drupal.org.");
      }

      return 0;
    }

    // If the master branch is not current, abort.
    if (!$master_branch->isCurrentBranch()) {
      throw new \Exception(strtr("Detected master branch !branch, but it is not the current branch. Aborting.\n", [
        '!branch' => $master_branch->getBranchName(),
      ]));
    }

    $output->writeln(strtr("Detected master branch !branch.", [
      '!branch' => $master_branch->getBranchName(),
    ]));

    $feature_branch->gitCreate();

    $output->writeln(strtr("Created feature branch !branch.", [
      '!branch' => $feature_branch->getBranchName(),
    ]));

    // Get the patches and create them.
    $patches = $this->waypoint_manager_patches->setUpPatches();

    // If no patches, we're done.
    if (empty($patches)) {
      $output->writeln("There are no patches to apply.");
      return 0;
    }

    // Output the patches.
    $list = new ItemList($output);
    $list->setProgressive();
    foreach ($patches as $patch) {
      $patch_committed = $patch->commitPatch();

      // Message.
      if ($patch_committed) {
        $list->addItem(strtr("Applied and committed patch !patchname.", [
          '!patchname' => $patch->getPatchFilename(),
        ]));
      }
      else {
        $list->addItem(strtr("Patch !patchname did not apply.", [
          '!patchname' => $patch->getPatchFilename(),
        ]));
      }
    }

    // If final patch didn't apply, then output a message: the latest patch
    // has rotted. Save the patch file to disk and give the filename in the
    // message.
    if (!$patch_committed) {
      // Save the file so the user can apply it manually.
      file_put_contents($patch->getPatchFilename(), $patch->getPatchFile());

      $output->writeln(strtr("The most recent patch, !patchname, did not apply. You should attempt to apply it manually. The patch file has been saved to the working directory.", [
        '!patchname' => $patch->getPatchFilename(),
      ]));
    }
  }

}
