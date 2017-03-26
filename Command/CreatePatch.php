<?php

namespace Dorgflow\Command;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class CreatePatch extends CommandBase {

  /**
   * Creates an instance of this command, injecting services from the container.
   */
  static public function create(ContainerBuilder $container) {
    return new static(
      $container->get('git.info'),
      $container->get('analyser'),
      $container->get('waypoint_manager.branches'),
      $container->get('waypoint_manager.patches'),
      $container->get('drupal_org'),
      $container->get('git.executor'),
      $container->get('commit_message')
    );
  }

  function __construct($git_info, $analyser, $waypoint_manager_branches, $waypoint_manager_patches, $drupal_org, $git_executor, $commit_message) {
    $this->git_info = $git_info;
    $this->analyser = $analyser;
    $this->waypoint_manager_branches = $waypoint_manager_branches;
    $this->waypoint_manager_patches = $waypoint_manager_patches;
    $this->drupal_org = $drupal_org;
    $this->git_executor = $git_executor;
    $this->commit_message = $commit_message;
  }

  public function execute() {
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

    // Make an empty commit to record the patch.
    $local_patch_commit_message = $this->commit_message->createLocalCommitMessage($patch_name);
    $this->git_executor->commit($local_patch_commit_message);
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
