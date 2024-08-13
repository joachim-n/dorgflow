<?php

namespace Dorgflow\Command;

use Dorgflow\Console\ItemList;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Dorgflow\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Console\Input\InputOption;

#[\AllowDynamicProperties]
class LocalUpdate extends SymfonyCommand {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('update')
      ->setDescription('Updates a feature branch.')
      ->setHelp('Updates an existing feature branch, and downloads and applies any new patches.');
      // Does not work yet -- comment indexes are not reliable, e.g. see
      // the jump in index numbers at #28 on
      // https://www.drupal.org/project/drupal/issues/66183.
      // ->addOption(
      //   'start',
      //   's',
      //   // this is the type of option (e.g. requires a value, can be passed more than once, etc.)
      //   InputOption::VALUE_OPTIONAL,
      //   'The natural comment index at which to start taking patches.',
      //   0,
      // );
  }

  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
    $this->waypoint_manager_patches = $this->container->get('waypoint_manager.patches');
    $this->git_executor = $this->container->get('git.executor');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->setServices();

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
    // $first_comment = $input->getOption('start');
    $first_comment = 0;
    $patches = $this->waypoint_manager_patches->setUpPatches($first_comment);
    //dump($patches);

    // If no patches, we're done.
    if (empty($patches)) {
      print "No patches to apply.\n";
      return 0;
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
      return 0;
    }

    // If the feature branch's SHA is not the same as the last committed patch
    // SHA, then that means there are local commits on the branch that are
    // newer than the patch.
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
    $list = new ItemList($output);
    $list->setProgressive();
    foreach ($patches_uncommitted as $patch) {
      // Commit the patch.
      $patch_committed = $patch->commitPatch();

      // Message.
      if ($patch_committed) {
        // Keep a list of the patches that we commit.
        $patches_committed[] = $patch;

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

    // If all the patches were already committed, we're done.
    if (empty($patches_committed)) {
      print "No new patches to apply.\n";
      return 0;
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

    return 0;
  }

}
