<?php

namespace Dorgflow\Command;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class CreatePatch extends SymfonyCommand implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('patch')
      ->setDescription('Creates a patch for the current feature branch.')
      ->setHelp('Creates a patch for the diff between the current feature branch and the master branch, and also an interdiff if a patch has previously been made.');
  }

  protected function setServices() {
    $this->git_info = $this->container->get('git.info');
    $this->git_log = $this->container->get('git.log');
    $this->analyser = $this->container->get('analyser');
    $this->waypoint_manager_branches = $this->container->get('waypoint_manager.branches');
    $this->waypoint_manager_patches = $this->container->get('waypoint_manager.patches');
    $this->drupal_org = $this->container->get('drupal_org');
    $this->git_executor = $this->container->get('git.executor');
    $this->commit_message = $this->container->get('commit_message');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setServices();

    // Check git is clean.
    $clean = $this->git_info->gitIsClean();
    if (!$clean) {
      throw new \Exception("Git repository is not clean. Aborting.");
    }

    // Create branches.
    $master_branch = $this->waypoint_manager_branches->getMasterBranch();
    $feature_branch = $this->waypoint_manager_branches->getFeatureBranch();

    // If the feature branch doesn't exist or is not current, abort.
    if (!$feature_branch->exists()) {
      throw new \Exception("Feature branch does not exist.");
    }
    if (!$feature_branch->isCurrentBranch()) {
      throw new \Exception("Feature branch is not the current branch.");
    }

    // TODO: get this from user input.
    $sequential = FALSE;

    $master_branch_name = $master_branch->getBranchName();

    $local_patch = $this->waypoint_manager_patches->getLocalPatch();

    $patch_name = $local_patch->getPatchFilename();

    $this->git_executor->createPatch($master_branch_name, $patch_name, $sequential);

    print("Written patch $patch_name with diff from $master_branch_name to local branch.\n");

    // Make an interdiff from the most recent patch.
    // (Before we make a recording patch, of course!)
    $last_patch = $this->waypoint_manager_patches->getMostRecentPatch();
    if (!empty($last_patch)) {
      $interdiff_name = $this->getInterdiffName($feature_branch, $last_patch);
      $last_patch_sha = $last_patch->getSHA();

      $this->git_executor->createPatch($last_patch_sha, $interdiff_name);

      print("Written interdiff $interdiff_name with diff from $last_patch_sha to local branch.\n");
    }

    // Write out a log of changes since the last patch.
    print("The following may be useful for the comment on d.org:\n");
    print("------------------------------------------------\n");
    if (empty($last_patch)) {
      $log = $this->git_log->getPartialFeatureBranchLog($master_branch_name);
      print("Changes in this patch:\n");
    }
    else {
      $log = $this->git_log->getPartialFeatureBranchLog($last_patch->getSHA());
      print("Changes since the last patch:\n");
    }
    foreach ($log as $log_item) {
      print("- {$log_item['message']}\n");
    }
    // Blow our own trumpet ;)
    if (empty($last_patch)) {
      print("Patch created by Dorgflow.\n");
    }
    else {
      print("Patch and interdiff created by Dorgflow.\n");
    }
    print("------------------------------------------------\n");

    // Make an empty commit to record the patch.
    $local_patch_commit_message = $this->commit_message->createLocalCommitMessage($local_patch);
    $this->git_executor->commit($local_patch_commit_message);

    return 0;
  }

  protected function getInterdiffName($feature_branch, $last_patch) {
    $issue_number = $this->analyser->deduceIssueNumber();
    $last_patch_comment_number = $last_patch->getPatchFileIndex();
    $next_comment_number = $this->drupal_org->getNextCommentIndex();

    // Allow for local patches that won't have a comment index.
    if (empty($last_patch_comment_number)) {
      $interdiff_name = "interdiff.$issue_number.$next_comment_number.txt";
    }
    else {
      $interdiff_name = "interdiff.$issue_number.$last_patch_comment_number-$next_comment_number.txt";
    }
    return $interdiff_name;
  }

}
